<?php
/**
 * 課程線性觀看存取控制
 *
 * @license GPL-2.0+
 */

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Chapter\Utils;

use J7\PowerCourse\Resources\Chapter\Model\Chapter;

defined( 'ABSPATH' ) || exit;

/**
 * 線性觀看模式的存取控制工具類別
 *
 * 提供判斷章節是否鎖定、是否啟用線性模式、是否可繞過等靜態方法。
 * 核心公式：locked = linear_mode_enabled AND NOT can_bypass AND NOT (is_first OR self_finished OR prev_finished)
 */
final class LinearAccess {

	/**
	 * 判斷課程是否啟用線性觀看模式
	 *
	 * @param int $course_id 課程（商品）ID
	 *
	 * @return bool 是否啟用線性觀看模式
	 */
	public static function is_linear_mode_enabled( int $course_id ): bool {
		$product = \wc_get_product( $course_id );
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		return 'yes' === (string) $product->get_meta( 'enable_linear_mode' );
	}

	/**
	 * 判斷使用者是否可繞過線性鎖定
	 *
	 * 管理員（具有 manage_woocommerce 權限）或課程作者可繞過。
	 *
	 * @param int      $course_id 課程（商品）ID
	 * @param int|null $user_id   使用者 ID，null 時使用當前使用者
	 *
	 * @return bool 是否可繞過線性鎖定
	 */
	public static function can_bypass_linear( int $course_id, ?int $user_id = null ): bool {
		$user_id = $user_id ?? \get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// 管理員可繞過
		if ( \user_can( $user_id, 'manage_woocommerce' ) ) {
			return true;
		}

		// 課程作者可繞過
		$course_author_id = (int) \get_post_field( 'post_author', $course_id );
		if ( $course_author_id > 0 && $course_author_id === $user_id ) {
			return true;
		}

		return false;
	}

	/**
	 * 判斷章節是否被線性鎖定
	 *
	 * 核心公式：locked = linear_mode_enabled AND NOT can_bypass AND NOT (is_first OR self_finished OR prev_finished)
	 *
	 * @param int      $chapter_id 章節 ID
	 * @param int|null $user_id    使用者 ID，null 時使用當前使用者
	 * @param int|null $course_id  課程 ID，null 時自動從章節推導
	 *
	 * @return bool 章節是否被鎖定
	 */
	public static function is_chapter_locked( int $chapter_id, ?int $user_id = null, ?int $course_id = null ): bool {
		$user_id = $user_id ?? \get_current_user_id();

		// 取得課程 ID
		if ( null === $course_id || 0 === $course_id ) {
			$course_id = Utils::get_course_id( $chapter_id );
		}

		// 找不到課程，無法判斷，不鎖定
		if ( null === $course_id || 0 === $course_id ) {
			return false;
		}

		// 未啟用線性模式，不鎖定
		if ( ! self::is_linear_mode_enabled( $course_id ) ) {
			return false;
		}

		// 可繞過的使用者，不鎖定
		if ( self::can_bypass_linear( $course_id, $user_id ) ) {
			return false;
		}

		// 取得扁平排序的所有章節 ID
		/** @var array<int> $flatten_ids */
		$flatten_ids = Utils::get_flatten_post_ids( $course_id );

		if ( empty( $flatten_ids ) ) {
			return false;
		}

		// 找到當前章節在扁平排序中的位置
		$current_index = \array_search( $chapter_id, $flatten_ids, true );

		// 章節不在列表中，不鎖定（容錯）
		if ( false === $current_index ) {
			return false;
		}

		// 第一個章節永遠不鎖定
		if ( 0 === $current_index ) {
			return false;
		}

		// 自己已完成，不鎖定
		try {
			$self_chapter = new Chapter( $chapter_id, $user_id ?: null );
			if ( $self_chapter->finished_at ) {
				return false;
			}
		} catch ( \Exception $e ) {
			// 無法建立 Chapter（例如 user_id 為 0），視為鎖定
			return true;
		}

		// 前一章已完成，不鎖定
		$prev_chapter_id = $flatten_ids[ $current_index - 1 ];

		try {
			$prev_chapter = new Chapter( $prev_chapter_id, $user_id ?: null );
			if ( $prev_chapter->finished_at ) {
				return false;
			}
		} catch ( \Exception $e ) {
			// 無法建立前一章的 Chapter，視為鎖定
			return true;
		}

		// 前一章未完成，且自己也未完成，鎖定
		return true;
	}

	/**
	 * 取得前一個必須完成的章節 ID
	 *
	 * @param int $chapter_id 章節 ID
	 * @param int $course_id  課程 ID
	 *
	 * @return int|null 前一個章節 ID，若為第一個章節則回傳 null
	 */
	public static function get_prev_required_chapter_id( int $chapter_id, int $course_id ): ?int {
		/** @var array<int> $flatten_ids */
		$flatten_ids   = Utils::get_flatten_post_ids( $course_id );
		$current_index = \array_search( $chapter_id, $flatten_ids, true );

		if ( false === $current_index || 0 === $current_index ) {
			return null;
		}

		return $flatten_ids[ $current_index - 1 ] ?? null;
	}
}
