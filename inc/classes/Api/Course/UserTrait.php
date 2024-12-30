<?php
/**
 * Course UserTrait
 * 包含 user, student 相關 callback
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Api\Course;

use J7\PowerCourse\Resources\Course\MetaCRUD as AVLCourseMeta;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Resources\Course\LifeCycle;
use J7\PowerCourse\Resources\StudentLog\CRUD as StudentLogCRUD;

/**
 * Trait UserTrait
 */
trait UserTrait {

	/**
	 * 取得學員課程紀錄
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	final public function get_courses_student_logs_callback( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore
		$params = $request->get_query_params();
		$params = WP::sanitize_text_field_deep( $params, false );

		$user_id   = $params['user_id'] ?? 0;
		$course_id = $params['course_id'] ?? 0;

		$crud = StudentLogCRUD::instance();
		$logs = $crud->get_list(
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
			]
			);

		return new \WP_REST_Response($logs);
	}

	/**
	 * 新增學員
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_add_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params =WP::sanitize_text_field_deep($body_params, false );

		$user_ids    = $body_params['user_ids'] ?? [];
		$course_ids  = $body_params['course_ids'] ?? [];
		$expire_date = $body_params['expire_date'] ?? 0;

		if (empty($user_ids) || empty($course_ids)) {
			return new \WP_REST_Response(
				[
					'code'    => 'add_students_failed',
					'message' => '新增學員失敗，缺少 user_ids 或 course_ids',
				],
				400
			);
		}

		foreach ($course_ids as $course_id) {
			foreach ($user_ids as  $user_id) {
				\do_action( LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, (int) $user_id, (int) $course_id, $expire_date, null );
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => 'add_students_success',
				'message' => '新增學員成功',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
				],
			],
			200
		);
	}

	/**
	 * 更新學員觀看時間
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_update_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params = WP::sanitize_text_field_deep( $body_params, false );
		$user_ids    = $body_params['user_ids'] ?? [];
		$timestamp   = $body_params['timestamp'] ?? 0; // 一般為 10 位數字，如果是0就是無期限
		$course_ids  = $body_params['course_ids'] ?? [];

		$success = true;
		foreach ($course_ids as $course_id) {
			foreach ($user_ids as  $user_id) {
				$success = AVLCourseMeta::update( (int) $course_id, (int) $user_id, 'expire_date', $timestamp );
				if (false === $success ) {
					break;
				}
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => $success ? 'update_students_success' : 'update_students_failed',
				'message' => $success ? '批量調整觀看期限成功' : '批量調整觀看期限失敗',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
					'timestamp'  => $timestamp,
				],
			],
			$success ? 200 : 400
		);
	}


	/**
	 * 移除學員
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_remove_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params = WP::sanitize_text_field_deep( $body_params, false );
		$user_ids    = $body_params['user_ids'] ?? [];
		$course_ids  = $body_params['course_ids'] ?? [];

		$success = true;
		foreach ($course_ids as $course_id) {
			foreach ($user_ids as $user_id) {
				$success1 = \delete_user_meta( $user_id, 'avl_course_ids', $course_id );
				// 移除上課權限時，也把 avl_course_meta 相關資料刪除
				$success2 = AVLCourseMeta::delete( (int) $course_id, (int) $user_id );
				\do_action(LifeCycle::AFTER_REMOVE_STUDENT_FROM_COURSE_ACTION, $user_id, $course_id);

				if (false === $success1 || false === $success2) {
					$success = false;
					break;
				}
			}
		}
		return new \WP_REST_Response(
			[
				'code'    => $success ? 'remove_students_success' : 'remove_students_failed',
				'message' => $success ? '移除學員成功' : '移除學員失敗',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
				],
			],
			$success ? 200 : 400
		);
	}
}
