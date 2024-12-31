<?php
/**
 * Course UserTrait
 * 包含 user, student 相關 callback
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Api\Course;

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
	 * @throws \Exception 缺少 user_ids 或 course_ids
	 */
	public function post_courses_add_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params =WP::sanitize_text_field_deep($body_params, false );

		$user_ids    = $body_params['user_ids'] ?? [];
		$course_ids  = $body_params['course_ids'] ?? [];
		$expire_date = $body_params['expire_date'] ?? 0;

		try {
			if (empty($user_ids) || empty($course_ids)) {
				throw new \Exception('新增學員失敗，缺少 user_ids 或 course_ids');
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
		} catch (\Throwable $th) {
			return new \WP_REST_Response(
				[
					'code'    => 'add_students_failed',
					'message' => $th->getMessage(),
					'data'    => [
						'user_ids'   => \implode(',', $user_ids),
						'course_ids' => \implode(',', $course_ids),
					],
				],
				400
			);
		}
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
		$timestamp   = (int) ( $body_params['timestamp'] ?? 0 ); // 一般為 10 位數字，如果是0就是無期限 //TODO 可能會跟隨訂閱!?
		$course_ids  = $body_params['course_ids'] ?? [];

		try {
			foreach ($course_ids as $course_id) {
				foreach ($user_ids as  $user_id) {
					\do_action(LifeCycle::AFTER_UPDATE_STUDENT_FROM_COURSE_ACTION, $user_id, $course_id, $timestamp);
				}
			}

			return new \WP_REST_Response(
				[
					'code'    => 'update_students_success',
					'message' => '批量調整觀看期限成功',
					'data'    => [
						'user_ids'   => \implode(',', $user_ids),
						'course_ids' => \implode(',', $course_ids),
						'timestamp'  => $timestamp,
					],
				],
				200
			);
		} catch (\Throwable $th) {
			return new \WP_REST_Response(
				[
					'code'    => 'update_students_failed',
					'message' => $th->getMessage(),
					'data'    => [
						'user_ids'   => \implode(',', $user_ids),
						'course_ids' => \implode(',', $course_ids),
						'timestamp'  => $timestamp,
					],
				],
				400
			);
		}
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

		try {
			foreach ($course_ids as $course_id) {
				foreach ($user_ids as $user_id) {
					\do_action(LifeCycle::AFTER_REMOVE_STUDENT_FROM_COURSE_ACTION, $user_id, $course_id);
				}
			}

			return new \WP_REST_Response(
				[
					'code'    => 'remove_students_success',
					'message' => '移除學員成功',
					'data'    => [
						'user_ids'   => \implode(',', $user_ids),
						'course_ids' => \implode(',', $course_ids),
					],
				],
				200
			);
		} catch (\Throwable $th) {
			return new \WP_REST_Response(
				[
					'code'    => 'remove_students_failed',
					'message' => $th->getMessage(),
					'data'    => [
						'user_ids'   => \implode(',', $user_ids),
						'course_ids' => \implode(',', $course_ids),
					],
				],
				400
			);
		}
	}
}
