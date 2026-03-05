<?php
/**
 * 註冊成功後自動開通設定課程
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Course;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Settings\Model\Settings;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class AutoGrant
 */
final class AutoGrant {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** Constructor */
	public function __construct() {
		\add_action( 'user_register', [ $this, 'auto_grant_courses_callback' ] );
	}

	/**
	 * 新用戶註冊後，依照設定自動開通課程
	 *
	 * @param int $user_id 用戶 id
	 * @return void
	 */
	public function auto_grant_courses_callback( int $user_id ): void {
		$auto_grant_courses = Settings::instance()->auto_grant_courses;
		if ( !\is_array( $auto_grant_courses ) || [] === $auto_grant_courses ) {
			return;
		}

		foreach ( $auto_grant_courses as $auto_grant_course ) {
			if ( !\is_array( $auto_grant_course ) ) {
				continue;
			}

			$course_id = (int) ( $auto_grant_course['course_id'] ?? 0 );
			if ( !$course_id ) {
				continue;
			}
			if ( !CourseUtils::is_course_product( $course_id ) ) {
				continue;
			}

			$limit_type   = (string) ( $auto_grant_course['limit_type'] ?? 'unlimited' );
			if ( 'follow_subscription' === $limit_type ) {
				Plugin::logger(
					"新用戶 #{$user_id} 自動開通課程 #{$course_id} 跳過：`follow_subscription` 需要訂單上下文",
					'warning'
				);
				continue;
			}
			$limit_value  = isset( $auto_grant_course['limit_value'] ) && \is_numeric( $auto_grant_course['limit_value'] )
				? (int) $auto_grant_course['limit_value']
				: null;
			$limit_unit   = isset( $auto_grant_course['limit_unit'] ) && \is_string( $auto_grant_course['limit_unit'] )
				? $auto_grant_course['limit_unit']
				: null;
			$limit        = new Limit( $limit_type, $limit_value, $limit_unit );
			$expire_date  = $limit->calc_expire_date( null );
			$expire_label = ( new ExpireDate( $expire_date ) )->expire_date_label;

			try {
				\do_action( LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, $user_id, $course_id, $expire_date, null );
				Plugin::logger(
					"新用戶 #{$user_id} 自動開通課程 #{$course_id}，到期日 {$expire_label}",
					'info',
				);
			} catch ( \Throwable $th ) {
				Plugin::logger(
					"新用戶 #{$user_id} 自動開通課程 #{$course_id} 失敗: {$th->getMessage()}",
					'critical',
				);
			}
		}
	}
}
