<?php
/**
 * 用戶註冊自動開通課程服務
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Course;

use J7\PowerCourse\Resources\Settings\Model\Settings;

/** 用戶註冊後自動開通指定課程的服務類別 */
final class AutoGrant {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** Constructor */
	public function __construct() {
		\add_action( 'user_register', [ $this, 'grant_on_register' ], 10, 2 );
	}

	/**
	 * 用戶註冊後自動開通課程
	 *
	 * @param int   $user_id  用戶 ID
	 * @param array $userdata 用戶資料（WordPress hook 要求的第二個參數，此處不使用）
	 * @return void
	 */
	public function grant_on_register( int $user_id, array $userdata ): void {
		$settings           = Settings::instance();
		$auto_grant_courses = $settings->auto_grant_courses;

		if ( empty( $auto_grant_courses ) ) {
			return;
		}

		// $auto_grant_courses 已由 Settings::set_properties() 正規化，欄位型別已確保
		foreach ( $auto_grant_courses as $item ) {
			$course_id  = $item['course_id'];
			$limit_type = $item['limit_type'];

			if ( ! $course_id ) {
				continue;
			}

			// follow_subscription 在 user_register 無 order/subscription context，跳過
			if ( 'follow_subscription' === $limit_type ) {
				continue;
			}

			$limit       = new Limit( $limit_type, $item['limit_value'], $item['limit_unit'] );
			$expire_date = $limit->calc_expire_date( null );

			\do_action(
				LifeCycle::ADD_STUDENT_TO_COURSE_ACTION,
				$user_id,
				$course_id,
				$expire_date,
				null
			);
		}
	}
}
