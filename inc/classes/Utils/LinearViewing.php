<?php
/**
 * 線性觀看核心演算法
 * 管理章節解鎖狀態計算，採「最遠進度模式」
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class LinearViewing
 * 線性觀看功能的核心演算法類別
 *
 * 解鎖規則：
 * - 所有章節平攤為一維順序（按 menu_order）
 * - 以「最遠已完成章節」位置為基準
 * - 解鎖範圍：位置 0 到 min(最遠位置+1, 最後位置)
 * - 管理員預覽模式免除所有限制
 */
final class LinearViewing {

	/**
	 * Per-request 快取，避免同一請求中重複計算解鎖狀態
	 *
	 * @var array<string, array{enabled: bool, unlocked_chapter_ids: int[], current_chapter_id: int|null, locked_hints: array<int, array{prerequisite_chapter_id: int, prerequisite_chapter_title: string, message: string}>}>
	 */
	private static array $state_cache = [];

	/**
	 * 取得課程的線性觀看解鎖狀態
	 *
	 * @param int      $course_id 課程 ID
	 * @param int|null $user_id   用戶 ID（預設使用當前登入用戶）
	 * @return array{
	 *     enabled: bool,
	 *     unlocked_chapter_ids: int[],
	 *     current_chapter_id: int|null,
	 *     locked_hints: array<int, array{prerequisite_chapter_id: int, prerequisite_chapter_title: string, message: string}>
	 * }
	 */
	public static function get_unlock_state( int $course_id, ?int $user_id = null ): array {
		$user_id   = $user_id ?? \get_current_user_id();
		$cache_key = "{$course_id}_{$user_id}";

		if ( isset( self::$state_cache[ $cache_key ] ) ) {
			return self::$state_cache[ $cache_key ];
		}

		// 未開啟線性觀看 → 全部解鎖（early return）
		if ( !self::is_enabled( $course_id ) ) {
			$state                            = self::build_all_unlocked( $course_id, false );
			self::$state_cache[ $cache_key ] = $state;
			return $state;
		}

		// 管理員預覽模式 → 全部解鎖（early return）
		if ( CourseUtils::is_admin_preview( $course_id ) ) {
			$state                            = self::build_all_unlocked( $course_id, true );
			self::$state_cache[ $cache_key ] = $state;
			return $state;
		}

		// 取得平攤章節 ID 列表（按 menu_order 排序）
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );
		if ( empty( $flat_ids ) ) {
			$state = [
				'enabled'             => true,
				'unlocked_chapter_ids' => [],
				'current_chapter_id'  => null,
				'locked_hints'        => [],
			];
			self::$state_cache[ $cache_key ] = $state;
			return $state;
		}

		// 取得已完成章節 IDs（array of int）
		/** @var array<int> $finished_ids */
		$finished_ids = array_values(
			array_map(
				'intval',
				CourseUtils::get_finished_sub_chapters( $course_id, $user_id, true )
			)
		);

		// 找最遠完成位置
		$max_pos = -1;
		foreach ( $finished_ids as $fid ) {
			$pos = array_search( (int) $fid, $flat_ids, true );
			if ( $pos !== false && (int) $pos > $max_pos ) {
				$max_pos = (int) $pos;
			}
		}

		// 解鎖範圍：位置 0 到 min(max_pos + 1, last_pos)
		$last_pos       = count( $flat_ids ) - 1;
		$unlock_boundary = min( $max_pos + 1, $last_pos );

		/** @var array<int> $unlocked_ids */
		$unlocked_ids = array_slice( $flat_ids, 0, $unlock_boundary + 1 );

		// current_chapter_id：第一個解鎖且未完成的章節
		$current_chapter_id = null;
		foreach ( $unlocked_ids as $uid ) {
			if ( !in_array( (int) $uid, $finished_ids, true ) ) {
				$current_chapter_id = (int) $uid;
				break;
			}
		}

		// locked_hints：鎖定章節的提示資訊
		// prerequisite 為解鎖邊界處的章節（即需要完成的章節）
		$prerequisite_id    = (int) ( $flat_ids[ $unlock_boundary ] ?? $flat_ids[0] );
		$prerequisite_title = (string) \get_the_title( $prerequisite_id );
		$locked_hints       = [];

		/** @var array<int> $locked_ids */
		$locked_ids = array_slice( $flat_ids, $unlock_boundary + 1 );
		foreach ( $locked_ids as $lid ) {
			$lid_int              = (int) $lid;
			$locked_hints[ $lid_int ] = [
				'prerequisite_chapter_id'    => $prerequisite_id,
				'prerequisite_chapter_title' => $prerequisite_title,
				'message'                    => sprintf( '請先完成『%s』才能觀看本章節', $prerequisite_title ),
			];
		}

		$state = [
			'enabled'             => true,
			'unlocked_chapter_ids' => $unlocked_ids,
			'current_chapter_id'  => $current_chapter_id,
			'locked_hints'        => $locked_hints,
		];

		self::$state_cache[ $cache_key ] = $state;
		return $state;
	}

	/**
	 * 清除指定課程+用戶的快取（章節完成/取消完成後需呼叫）
	 *
	 * @param int      $course_id 課程 ID
	 * @param int|null $user_id   用戶 ID
	 */
	public static function clear_cache( int $course_id, ?int $user_id = null ): void {
		$user_id   = $user_id ?? \get_current_user_id();
		$cache_key = "{$course_id}_{$user_id}";
		unset( self::$state_cache[ $cache_key ] );
	}

	/**
	 * 檢查課程是否啟用線性觀看
	 *
	 * @param int $course_id 課程 ID
	 * @return bool
	 */
	public static function is_enabled( int $course_id ): bool {
		$product = \wc_get_product( $course_id );
		if ( !$product ) {
			return false;
		}

		return ( (string) $product->get_meta( 'enable_linear_viewing' ) ?: 'no' ) === 'yes';
	}

	/**
	 * 檢查特定章節是否已解鎖
	 *
	 * @param int      $chapter_id 章節 ID
	 * @param int      $course_id  課程 ID
	 * @param int|null $user_id    用戶 ID（預設使用當前登入用戶）
	 * @return bool
	 */
	public static function is_chapter_unlocked( int $chapter_id, int $course_id, ?int $user_id = null ): bool {
		$state = self::get_unlock_state( $course_id, $user_id );
		return in_array( $chapter_id, $state['unlocked_chapter_ids'], true );
	}

	/**
	 * 取得已解鎖的章節 IDs
	 *
	 * @param int      $course_id 課程 ID
	 * @param int|null $user_id   用戶 ID（預設使用當前登入用戶）
	 * @return array<int>
	 */
	public static function get_unlocked_chapter_ids( int $course_id, ?int $user_id = null ): array {
		$state = self::get_unlock_state( $course_id, $user_id );
		return $state['unlocked_chapter_ids'];
	}

	/**
	 * 建立「全部解鎖」狀態（線性觀看關閉或管理員預覽時使用）
	 *
	 * @param int  $course_id 課程 ID
	 * @param bool $enabled   是否為「已啟用但全部解鎖」（管理員預覽）
	 * @return array{
	 *     enabled: bool,
	 *     unlocked_chapter_ids: int[],
	 *     current_chapter_id: int|null,
	 *     locked_hints: array<int, array{prerequisite_chapter_id: int, prerequisite_chapter_title: string, message: string}>
	 * }
	 */
	private static function build_all_unlocked( int $course_id, bool $enabled ): array {
		/** @var array<int> $flat_ids */
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );

		return [
			'enabled'             => $enabled,
			'unlocked_chapter_ids' => $flat_ids,
			'current_chapter_id'  => null,
			'locked_hints'        => [],
		];
	}
}
