<?php
/**
 * Chapter 生命週期相關
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Chapter;

use J7\PowerCourse\Resources\Chapter\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Course\MetaCRUD as AVLCourseMeta;

/**
 * Class LifeCycle
 */
final class LifeCycle {
	use \J7\WpUtils\Traits\SingletonTrait;

	const CHAPTER_ENTER_ACTION = 'power_course_visit_chapter';

	/**
	 * Constructor
	 */
	public function __construct() {

		\add_action( 'power_course_before_classroom_render', [ __CLASS__, 'register_visit_chapter' ] );
		\add_action( 'power_course_before_classroom_render', [ __CLASS__, 'register_visit_chapter' ] );

		// 進入章節時要註記
		\add_action( self::CHAPTER_ENTER_ACTION, [ __CLASS__, 'save_first_visit_time' ], 10, 2 );
		\add_action( self::CHAPTER_ENTER_ACTION, [ __CLASS__, 'save_last_visit_info' ], 10, 2 );

		// 上完章節後要註記
	}

	/**
	 * 註冊進入章節的動作
	 */
	public static function register_visit_chapter() {
		global $product, $chapter;
		if ( ! $product || ! $chapter ) {
			return;
		}

		$is_avl = ChapterUtils::is_avl();

		if ( !$is_avl ) {
			return;
		}

		\do_action( 'power_course_visit_chapter', $chapter, $product );
	}

	/**
	 * 進入章節時要註記
	 *
	 * @param \WP_Post    $chapter 章節文章物件
	 * @param \WC_Product $product 課程
	 */
	public static function save_first_visit_time( $chapter, $product ) {
		$meta_key = 'first_visit_at';
		$user_id  = \get_current_user_id();

		$enter_time = MetaCRUD::get( $chapter->ID, $user_id, $meta_key, true );

		if ( $enter_time ) {
			return;
		}

		MetaCRUD::update( $chapter->ID, $user_id, $meta_key, \wp_date( 'Y-m-d H:i:s' ) );
	}

	/**
	 * 註冊離開章節的動作
	 *
	 * @param \WP_Post    $chapter 章節文章物件
	 * @param \WC_Product $product 課程
	 */
	public static function save_last_visit_info( $chapter, $product ) {
		$meta_key   = 'last_visit_info';
		$meta_value = [
			'chapter_id'    => $chapter->ID,
			'last_visit_at' => \wp_date( 'Y-m-d H:i:s' ),
		];
		$user_id    = \get_current_user_id();

		AVLCourseMeta::update( $product->get_id(), $user_id, $meta_key, $meta_value );
	}
}
