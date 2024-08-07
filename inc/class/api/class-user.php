<?php
/**
 * User API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\WpUtils\Classes\WP;
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
			'endpoint'            => 'users',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'users/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'users/students',
			'method'              => 'get',
			'permission_callback' => null,
		],

		[
			'endpoint'            => 'users/add-teachers', // 設定為講師
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'users/remove-teachers', // 解除講師身分
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
	 * 通用的用戶查詢
	 * TODO 還沒測過分頁功能
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

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'search_columns' => [ 'ID', 'user_login', 'user_email', 'user_nicename', 'display_name' ],
			'posts_per_page' => 10,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'offset'         => 0,
			'paged'          => 1,
			'count_total'    => true,
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		if (!empty($args['search'])) {
			$args['search'] = '*' . $args['search'] . '*'; // 模糊搜尋
		}

		// Create the WP_User_Query object
		$wp_user_query = new \WP_User_Query($args);

		/**
		 * @var \WP_User[] $users
		 */
		$users = $wp_user_query->get_results();

		$total       = $wp_user_query->get_total();
		$total_pages = \floor( $total / $args['posts_per_page'] ) + 1;

		$formatted_users = array_values(array_map( [ $this, 'format_user_details' ], $users ));

		$response = new \WP_REST_Response( $formatted_users );

		// // set pagination in header
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Get student callback
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
	public function get_users_students_callback( $request ): \WP_REST_Response {

		$params = $request->get_query_params();

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'search_columns' => [ 'ID', 'user_login', 'user_email', 'user_nicename', 'display_name' ],
			'posts_per_page' => 10,
			'order'          => 'DESC',
			'offset'         => 0,
			'paged'          => 1,
			'count_total'    => true,
			'meta_key'       => 'avl_course_ids', // phpcs:ignore
			'meta_value'     => '', // phpcs:ignore
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		if ( empty($args['meta_value']) ) {
			return new \WP_REST_Response(
			[
				'code'    => 'empty_meta_value',
				'message' => 'meta_value 不能為空，找不到 course_id',
			],
			400
			);
		}

		// 如果 $args['meta_value'] 有包含 ! 開頭，就用反查詢
		if (\str_starts_with($args['meta_value'], '!')) {
			$reverse   = true;
			$course_id = substr($args['meta_value'], 1);
		} else {
			$reverse   = false;
			$course_id = $args['meta_value'];
		}

		global $wpdb;

		if (!$reverse) {
			$sql = $wpdb->prepare(
			'SELECT u.ID
			FROM %1$s u
			INNER JOIN %2$s um ON u.ID = um.user_id',
			$wpdb->users,
			$wpdb->usermeta,
			);
		} else {
			$sql = $wpdb->prepare(
			'SELECT u.ID
			FROM %1$s u ',
			$wpdb->users,
			);
		}

		if (!$reverse) {
			$where = $wpdb->prepare(
			" WHERE um.meta_key = '%1\$s'
			AND um.meta_value = '%2\$s'",
			$args['meta_key'],
			$args['meta_value']
			);
		} else {
			$where = $wpdb->prepare(
			" WHERE u.ID NOT IN (
    SELECT DISTINCT u.ID
    FROM %1\$s u
    LEFT JOIN %2\$s um ON u.ID = um.user_id
    WHERE um.meta_key = '%3\$s' AND um.meta_value = '%4\$s'
) ",
			$wpdb->users,
			$wpdb->usermeta,
			$args['meta_key'],
			$course_id
			);
		}

		if (!empty($args['search'])) {
			$search_value = $args['search'];
			$where       .= ' AND (';
			$where       .= match ($args['search_field']) {
				'email'=> "u.user_email LIKE '%{$search_value}%'",
				'name'=> "u.user_login LIKE '%{$search_value}%' OR u.user_nicename LIKE '%{$search_value}%' OR u.display_name LIKE '%{$search_value}%'",
				'id' => \is_numeric($search_value) ? "u.ID = {$search_value}" : '',
				default => "u.user_login LIKE '%{$search_value}%' OR u.user_nicename LIKE '%{$search_value}%' OR u.display_name LIKE '%{$search_value}%' OR u.user_email LIKE '%{$search_value}%'" . ( \is_numeric($search_value) ? " OR u.ID = {$search_value}" : '' ),
			};
			$where .= ')';
		}

		$sql .= $where;
		if (!$reverse) {
			$sql .= $wpdb->prepare(
			' ORDER BY um.umeta_id DESC
			LIMIT %1$d OFFSET %2$d',
			$args['posts_per_page'],
			( ( $args['paged'] - 1 ) * $args['posts_per_page'] )
			);
		} else {
			$sql .= $wpdb->prepare(
			' ORDER BY u.ID DESC
				LIMIT %1$d OFFSET %2$d',
			$args['posts_per_page'],
			( ( $args['paged'] - 1 ) * $args['posts_per_page'] )
			);
		}

		$user_ids = $wpdb->get_col( $sql); // phpcs:ignore
		$user_ids = \array_unique($user_ids);

		$users = array_map( fn( $user_id ) => get_user_by('id', $user_id), $user_ids );
		$users = array_filter($users);

		// 查找總數
		$count_query = $wpdb->prepare(
		'SELECT DISTINCT COUNT(DISTINCT u.ID)
			FROM %1$s u
			INNER JOIN %2$s um ON u.ID = um.user_id',
			$wpdb->users,
			$wpdb->usermeta,
		) . $where;

		$total = $wpdb->get_var($count_query); // phpcs:ignore

		$total_pages = \floor( ( (int) $total )/ ( (int) $args['posts_per_page'] ) ) + 1;

		$formatted_users = array_values(array_map( [ $this, 'format_user_details' ], $users ));

		$response = new \WP_REST_Response( $formatted_users );

		// // set pagination in header
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * 新增用戶
	 *
	 * @param \WP_REST_Request $request 包含新增用戶所需資料的REST請求對象。
	 * @return \WP_REST_Response 返回包含操作結果的REST響應對象。成功時返回用戶資料，失敗時返回錯誤訊息。
	 * @phpstan-ignore-next-line
	 */
	public function post_users_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$body_params = $request->get_body_params();

		$body_params = WP::sanitize_text_field_deep( $body_params );

		[
		'data' => $data,
		'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'user' );

		$user_id = \wp_insert_user( $data );

		if (\is_wp_error($user_id)) {
			return new \WP_REST_Response(
			[
				'code'    => 'create_user_error',
				'message' => $user_id->get_error_message(),
				'data'    => null,
			],
			400
			);
		}

		foreach ( $meta_data as $key => $value ) {
			\update_user_meta($user_id, $key, $value );
		}

		return new \WP_REST_Response(
			[
				'code'    => 'post_user_success',
				'message' => '修改成功',
				'data'    => [
					'id' => (string) $user_id,
				],
			],
			200
			);
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
		$user_avatar_url = \get_user_meta($user_id, 'user_avatar_url', true);
		$user_avatar_url = !!$user_avatar_url ? $user_avatar_url : \get_avatar_url( $user_id  );

		$avl_course_ids =\get_user_meta($user_id, 'avl_course_ids');
		$avl_course_ids = \is_array($avl_course_ids) ? $avl_course_ids : [];

		// BUG: 有用戶因為章節太多，會 500 Error
		$avl_courses = [];
		foreach ($avl_course_ids as $i => $course_id) {
			$course_id                        = (int) $course_id;
			$avl_courses[ $i ]['id']          = (string) $course_id;
			$avl_courses[ $i ]['name']        = \get_the_title($course_id);
			$avl_courses[ $i ]['expire_date'] = (int) AVLCourseMeta::get( $course_id, $user_id, 'expire_date', true);
			$all_chapter_ids                  = CourseUtils::get_sub_chapters($course_id, true);
			$finished_chapter_ids             = AVLCourseMeta::get( $course_id, $user_id, 'finished_chapter_ids');
			foreach ($all_chapter_ids as $j => $chapter_id) {
				$avl_courses[ $i ]['chapters'][ $j ]['id']            = (string) $chapter_id;
				$avl_courses[ $i ]['chapters'][ $j ]['name']          = \get_the_title($chapter_id);
				$avl_courses[ $i ]['chapters'][ $j ]['chapter_video'] = \get_post_meta($chapter_id, 'chapter_video', true);
				$avl_courses[ $i ]['chapters'][ $j ]['is_finished']   = \in_array( (string) $chapter_id, $finished_chapter_ids, true);
			}
		}

		$base_array = [
			'id'                    => (string) $user_id,
			'user_login'            => $user->user_login,
			'user_email'            =>$user->user_email,
			'display_name'          => $user->display_name,
			'user_registered'       => $user_registered,
			'user_registered_human' => \human_time_diff( \strtotime( $user_registered ) ),
			'user_avatar_url'       => $user_avatar_url,
			'avl_courses'           => $avl_courses,
			'description'           => $user->description,
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

		$body_params = WP::sanitize_text_field_deep( $body_params );

		[
		'data' => $data,
		'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'user', files: $file_params['files'] ?? [] );

		$data['ID'] = $user_id;
		unset($meta_data['id']);

		$update_user_result = \wp_update_user( $data );

		$update_success = \is_numeric($update_user_result);

		foreach ( $meta_data as $key => $value ) {
			\update_user_meta($user_id, $key, $value );
		}

		return new \WP_REST_Response(
			[
				'code'    => $update_success ? 'post_user_success' : 'post_user_error',
				'message' => $update_success ? '修改成功' : '修改失敗',
				'data'    => [
					'id'                 => (string) $user_id,
					'update_user_result' => $update_user_result,
				],
			],
			$update_success ? 200 : 400
			);
	}

	/**
	 * 處理批量將用戶設定為講師的請求。
	 *
	 * @param \WP_REST_Request $request REST請求對象，包含需要處理的用戶ID。
	 * @return \WP_REST_Response 返回REST響應對象，包含操作結果的狀態碼和訊息。
	 * @phpstan-ignore-next-line
	 */
	public function post_users_add_teachers_callback( \WP_REST_Request $request ): \WP_REST_Response {

		$body_params = $request->get_body_params();

		$body_params = WP::sanitize_text_field_deep( $body_params );
		$user_ids    = $body_params['user_ids'] ?? [];

		foreach ( $user_ids as $user_id ) {
			\update_user_meta($user_id, 'is_teacher', 'yes' );
		}

		return new \WP_REST_Response(
		[
			'code'    =>'update_users_to_teachers_success',
			'message' =>'批量將用戶轉為講師成功',
			'data'    => [
				'user_ids' => \implode(',', $user_ids),
			],
		],
		200
		);
	}

	/**
	 * 將指定用戶批量移除講師身分
	 *
	 * @param \WP_REST_Request $request 包含用戶ID的REST請求。
	 * @return \WP_REST_Response 包含操作結果的響應對象。
	 * @phpstan-ignore-next-line
	 */
	public function post_users_remove_teachers_callback( \WP_REST_Request $request ): \WP_REST_Response {

		$body_params = $request->get_body_params();

		$body_params = WP::sanitize_text_field_deep( $body_params );
		$user_ids    = $body_params['user_ids'] ?? [];

		$update_success = false;
		foreach ( $user_ids as $user_id ) {
			$update_success = (bool) \delete_user_meta($user_id, 'is_teacher' );
			if (!$update_success) {
				break;
			}
		}

		return new \WP_REST_Response(
		[
			'code'    => $update_success ? 'remove_teachers_success' : 'remove_teachers_failed',
			'message' => $update_success ? '批量移除講師成功' : '批量移除講師失敗',
			'data'    => [
				'user_ids' => \implode(',', $user_ids),
			],
		],
		$update_success ? 200 : 400
		);
	}
}

User::instance();
