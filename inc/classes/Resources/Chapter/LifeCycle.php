<?php
/**
 * Chapter 生命週期相關
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Chapter;

use J7\PowerCourse\Resources\Chapter\Utils as ChapterUtils;

/**
 * Class LifeCycle
 */
final class LifeCycle {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {

		\add_action( 'power_course_before_classroom_render', [ __CLASS__, 'register_enter_chapter' ] );
		// 進入章節時要註記
		\add_action( 'power_course_enter_chapter', [ __CLASS__, 'enter_chapter' ] );

		// 上完章節後要註記
	}

	/**
	 * 註冊進入章節的動作
	 */
	public static function register_enter_chapter() {
		global $chapter;
		if ( ! $chapter ) {
			return;
		}

		$is_avl = ChapterUtils::is_avl();

		if ( !$is_avl ) {
			return;
		}

		\do_action( 'power_course_enter_chapter', $chapter );
	}

	/**
	 * 進入章節時要註記
	 *
	 * @param \WP_Post $chapter 章節文章物件
	 */
	public static function enter_chapter( $chapter ) {
		$meta_key = 'first_visit_at';
		$user_id  = \get_current_user_id();

		$enter_time = MetaCRUD::get( $chapter->ID, $user_id, $meta_key, true );

		if ( $enter_time ) {
			return;
		}

		MetaCRUD::update( $chapter->ID, $user_id, $meta_key, \wp_date( 'Y-m-d H:i:s' ) );
	}
}
