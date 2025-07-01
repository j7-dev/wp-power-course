<?php

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Student\Core;

use J7\PowerCourse\Resources\Student\Services\ExportCSV;
use J7\PowerCourse\Resources\Student\Services\Query;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Course\ExpireDate;
use J7\PowerCourse\Utils\Course as CourseUtils;

use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\General;
use J7\WpUtils\Classes\ApiBase;
use J7\Powerhouse\Domains\User\Model\User;


/** Class Api */
final class Api extends ApiBase {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string 命名空間 */
	protected $namespace = 'power-course';

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
			'endpoint'            => 'students/export/(?P<id>\d+)',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'students',
			'method'              => 'get',
			'permission_callback' => null,
		],
	];

	/** Constructor */
	public function __construct() {
		parent::__construct();
		\add_filter('powerhouse/user/get_meta_keys_array', [ $this, 'extend_meta_keys' ], 10, 2);
	}

	/**
	 * 匯出學員名單
	 *
	 * @param \WP_REST_Request $request 包含課程 ID 的 REST 請求對象。
	 * @return \WP_REST_Response
	 */
	public function get_students_export_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$course_id = (int) $request['id'];
		$export    = new ExportCSV($course_id);
		$export->export();
		return new \WP_REST_Response(
			[
				'code'    => 'get_students_export_success',
				'message' => '匯出成功',
				'data'    => null,
			]
			);
	}

	/**
	 * 取得學員
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
	public function get_students_callback( $request ): \WP_REST_Response {
		$params = $request->get_query_params();
		$params = WP::sanitize_text_field_deep( $params, false );

		[$meta_keys, $rest_params] = General::destruct($params, 'meta_keys');
		$meta_keys                 = is_array($meta_keys) ? $meta_keys : [];

		$query      = new Query($rest_params);
		$user_ids   = $query->user_ids;
		$pagination = $query->get_pagination();

		$formatted_users = [];
		foreach ($user_ids as $user_id) {
			$formatted_users[] = User::instance( (int) $user_id )->to_array('list', $meta_keys);
		}

		$response = new \WP_REST_Response( $formatted_users );

		$response->header( 'X-WP-Total', (string) $pagination->total );
		$response->header( 'X-WP-TotalPages', (string) $pagination->total_pages );

		return $response;
	}

	/**
	 * 擴充 meta keys
	 *
	 * @param array<string, mixed> $meta_keys_array Meta keys array.
	 * @param \WP_User             $user          User.
	 * @return array<string, mixed>
	 */
	public function extend_meta_keys( array $meta_keys_array, \WP_User $user ): array {
		if (isset($meta_keys_array['is_teacher'])) {
			$meta_keys_array['is_teacher'] = \wc_string_to_bool(\get_user_meta($user->ID, 'is_teacher', true));
		}

		if (isset($meta_keys_array['avl_courses'])) {
			$avl_course_ids =\get_user_meta($user->ID, 'avl_course_ids');
			$avl_course_ids = \is_array($avl_course_ids) ? $avl_course_ids : [];

			$avl_courses = [];
			foreach ($avl_course_ids as $i => $course_id) {
				$course_id               = (int) $course_id;
				$total_chapters_count    = count(ChapterUtils::get_flatten_post_ids($course_id));
				$finished_chapters_count = count(CourseUtils::get_finished_sub_chapters($course_id, $user->ID, true));

				$avl_courses[ $i ]['id']                      = (string) $course_id;
				$avl_courses[ $i ]['name']                    = \get_the_title($course_id);
				$avl_courses[ $i ]['progress']                = CourseUtils::get_course_progress( $course_id, $user->ID );
				$avl_courses[ $i ]['finished_chapters_count'] = $finished_chapters_count;
				$avl_courses[ $i ]['total_chapters_count']    = $total_chapters_count;
				$avl_courses[ $i ]['expire_date']             = ExpireDate::instance($course_id, $user->ID)->to_array();
			}
			$meta_keys_array['avl_courses'] = $avl_courses;
		}

		return $meta_keys_array;
	}
}
