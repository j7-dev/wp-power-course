<?php
/**
 * User API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\WpUtils\Classes\WP;
use J7\PowerBundleProduct\BundleProduct;
use J7\PowerCourse\Utils\AVLCourseMeta;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class Api
 */
final class User {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ApiRegisterTrait;

	/**
	 * APIs
	 *
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
		[
			'endpoint'            => 'users',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'users/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => null,
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_products' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_products(): void {
		$this->register_apis(
			apis: $this->apis,
			namespace: Plugin::$kebab,
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}


	/**
	 * Get users callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * $params
	 *  - meta_key avl_course_ids 如果要找用戶可以上的課程
	 *  - meta_value
	 * - count_total 是否要計算總數
	 *
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function get_users_callback( $request ): \WP_REST_Response {

		$params = $request->get_query_params();

		$params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $params );

		$default_args = [
			'search_columns' => [ 'ID', 'user_login', 'user_email', 'user_nicename', 'display_name' ],
			'number'         => 10,
			'orderby'        => 'registered',
			'order'          => 'DESC',
			'offset'         => 0,
			'paged'          => 1,
			'count_total'    => true,
		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		if (isset($args['search'])) {
			$args['search'] = '*' . $args['search'] . '*'; // 模糊搜尋
		}

		// Create the WP_User_Query object
		$wp_user_query = new \WP_User_Query($args);

		/**
		 * @var \WP_User[] $users
		 */
		$users = $wp_user_query->get_results();

		$total       = $wp_user_query->get_total();
		$total_pages = \floor( $total / $args['number'] ) + 1;

		$formatted_users = array_map( [ $this, 'format_user_details' ], $users );

		$response = new \WP_REST_Response( $formatted_users );

		// // set pagination in header
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Format user details
	 *
	 * @param \WP_User $user  User.
	 * @return array{id: string,user_login: string,user_email: string,display_name: string,user_registered: string,user_registered_human: string,user_avatar: string,avl_courses: array<string, mixed>}[]
	 */
	public function format_user_details( \WP_User $user ): array {

		if ( ! ( $user instanceof \WP_User ) ) {
			return [];
		}
		$user_id         = (int) $user->get( 'ID' );
		$user_registered = $user->get( 'user_registered' );
		$user_avatar_url = \get_avatar_url( $user_id  );

		$avl_course_ids =\get_user_meta($user_id, 'avl_course_ids');
		$avl_course_ids = \is_array($avl_course_ids) ? $avl_course_ids : [];

		$avl_courses = [];
		foreach ($avl_course_ids as $i => $course_id) {
			$course_id                        = (int) $course_id;
			$avl_courses[ $i ]['id']          = (string) $course_id;
			$avl_courses[ $i ]['name']        = \get_the_title($course_id);
			$avl_courses[ $i ]['expire_date'] = AVLCourseMeta::get( $course_id, $user_id, 'expire_date', true);
			$all_chapter_ids                  = CourseUtils::get_sub_chapters($course_id, return_ids :true);
			$finished_chapter_ids             = AVLCourseMeta::get( $course_id, $user_id, 'finished_chapter_ids');
			foreach ($all_chapter_ids as $j => $chapter_id) {
				$avl_courses[ $i ]['chapters'][ $j ]['id']             = (string) $chapter_id;
				$avl_courses[ $i ]['chapters'][ $j ]['name']           = \get_the_title($chapter_id);
				$avl_courses[ $i ]['chapters'][ $j ]['bunny_video_id'] = \get_the_title($chapter_id);
				$avl_courses[ $i ]['chapters'][ $j ]['is_finished']    = \in_array( (string) $chapter_id, $finished_chapter_ids, true);
			}
		}

		$base_array = [
			'id'                    => (string) $user_id,
			'user_login'            => $user->get( 'user_login' ),
			'user_email'            => $user->get( 'user_email' ),
			'display_name'          => $user->get( 'display_name' ),
			'user_registered'       => $user_registered,
			'user_registered_human' => \human_time_diff( \strtotime( $user_registered ) ),
			'user_avatar_url'       => $user_avatar_url,
			'avl_courses'           => $avl_courses,
		];

		return $base_array;
	}



	/**
	 * Post user callback
	 * 修改 user
	 * 用 form-data 方式送出
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function post_users_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id     = $request['id'];
		$body_params = $request->get_body_params();
		$file_params = $request->get_file_params();

		$body_params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $body_params );

		[
			'data' => $data,
			'meta_data' => $meta_data,
			] = WP::separator( args: $body_params, obj: 'user', files: $file_params['files'] ?? [] );

		$data['ID']         = $user_id;
		$update_user_result = \wp_update_user( $data );

		$update_success = \is_numeric($update_user_result);

		foreach ( $meta_data as $key => $value ) {
			$update_success = \update_user_meta($user_id, $key, $value );
			if (!$update_success) {
				break;
			}
		}

		if ( !!$update_success ) {
			return new \WP_REST_Response(
				[
					'code'    => 'post_user_success',
					'message' => '修改成功',
					'data'    => [
						'id' => (string) $user_id,
					],
				]
			);
		} else {
			return new \WP_REST_Response(
				[
					'code'    => 'post_user_error',
					'message' => '修改失敗',
					'data'    => [
						'id' => (string) $user_id,
					],
				],
				400
			);
		}
	}
}

User::instance();
