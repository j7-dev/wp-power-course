<?php

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Chapter\Utils;

use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearViewing
 * 線性觀看核心邏輯類別
 * 處理課程章節的線性觀看模式：判斷鎖定狀態、取得應完成章節等
 */
abstract class LinearViewing {

	/**
	 * 判斷章節是否被鎖定
	 *
	 * 解鎖規則（按優先順序）：
	 * 1. 課程未啟用線性觀看 → 不鎖定
	 * 2. 用戶為管理員（manage_woocommerce）→ 不鎖定
	 * 3. 課程無章節（flat_ids 為空）→ 不鎖定
	 * 4. 是序列中第一個章節 → 不鎖定
	 * 5. 章節自身已完成（has finished_at）→ 不鎖定
	 * 6. 章節不在序列中 → 不鎖定（fail open）
	 * 7. 前一個章節已完成 → 不鎖定
	 * 8. 否則 → 鎖定
	 *
	 * @param int $chapter_id 章節 ID.
	 * @param int $course_id  課程 ID.
	 * @param int $user_id    用戶 ID.
	 * @return bool 是否鎖定
	 */
	public static function is_chapter_locked( int $chapter_id, int $course_id, int $user_id ): bool {
		// 規則 1：課程未啟用線性觀看
		$enable_linear = (string) \get_post_meta( $course_id, 'enable_linear_viewing', true );
		if ( 'yes' !== $enable_linear ) {
			return false;
		}

		// 規則 2：管理員不受限
		$user = \get_userdata( $user_id );
		if ( $user && $user->has_cap( 'manage_woocommerce' ) ) {
			return false;
		}

		// 規則 3：課程無章節
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );
		if ( empty( $flat_ids ) ) {
			return false;
		}

		// 規則 4：是序列中第一個章節
		if ( $chapter_id === $flat_ids[0] ) {
			return false;
		}

		// 規則 5：章節自身已完成
		$finished_at = AVLChapterMeta::get( $chapter_id, $user_id, 'finished_at', true );
		if ( ! empty( $finished_at ) ) {
			return false;
		}

		// 規則 6：章節不在序列中（fail open）
		/** @var int|false $index */
		$index = array_search( $chapter_id, $flat_ids, true );
		if ( false === $index ) {
			return false;
		}

		// 規則 7：前一個章節已完成
		$prev_id     = $flat_ids[ $index - 1 ] ?? null;
		if ( null === $prev_id ) {
			// 沒有前一個章節（不應發生，因為第一個已在規則 4 處理）
			return false;
		}

		$prev_finished_at = AVLChapterMeta::get( $prev_id, $user_id, 'finished_at', true );
		if ( ! empty( $prev_finished_at ) ) {
			return false;
		}

		// 規則 8：鎖定
		return true;
	}

	/**
	 * 取得第一個「應完成但未完成」的章節 ID（用於重導向目標）
	 *
	 * 掃描扁平化序列，回傳第一個未完成且非第一個已解鎖之後的鎖定章節。
	 * 實際上是「找到學員下一個該完成的章節」。
	 *
	 * @param int $course_id 課程 ID.
	 * @param int $user_id   用戶 ID.
	 * @return int|null 章節 ID，若所有章節都已完成則回傳 null
	 */
	public static function get_first_locked_chapter_id( int $course_id, int $user_id ): ?int {
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );
		if ( empty( $flat_ids ) ) {
			return null;
		}

		foreach ( $flat_ids as $chapter_id ) {
			$finished_at = AVLChapterMeta::get( $chapter_id, $user_id, 'finished_at', true );
			if ( empty( $finished_at ) ) {
				return $chapter_id;
			}
		}

		// 所有章節都已完成
		return null;
	}

	/**
	 * 取得完成指定章節後新解鎖的下一個章節 ID
	 *
	 * 當學員完成某章節後，找出緊接在後面且尚未完成的下一個章節。
	 * 此方法假設 chapter_id 已被標記為完成。
	 *
	 * @param int $chapter_id 剛完成的章節 ID.
	 * @param int $course_id  課程 ID.
	 * @param int $user_id    用戶 ID.
	 * @return int|null 下一個解鎖的章節 ID，若無更多章節則回傳 null
	 */
	public static function get_next_unlocked_chapter_id( int $chapter_id, int $course_id, int $user_id ): ?int {
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );
		if ( empty( $flat_ids ) ) {
			return null;
		}

		/** @var int|false $index */
		$index = array_search( $chapter_id, $flat_ids, true );
		if ( false === $index ) {
			return null;
		}

		// 取得下一個章節
		$next_id = $flat_ids[ $index + 1 ] ?? null;
		if ( null === $next_id ) {
			return null;
		}

		// 確認下一個章節尚未完成（才算是「新解鎖」）
		$next_finished_at = AVLChapterMeta::get( $next_id, $user_id, 'finished_at', true );
		if ( ! empty( $next_finished_at ) ) {
			// 下一個已完成，尋找更後面的未完成章節
			return null;
		}

		return $next_id;
	}

	/**
	 * 批量取得課程所有章節的鎖定狀態
	 *
	 * 一次查詢所有章節，避免 N+1 問題。適合在渲染章節列表時使用。
	 *
	 * @param int $course_id 課程 ID.
	 * @param int $user_id   用戶 ID.
	 * @return array<int, bool> 章節 ID => 是否鎖定
	 */
	public static function get_chapters_lock_map( int $course_id, int $user_id ): array {
		$flat_ids = ChapterUtils::get_flatten_post_ids( $course_id );
		if ( empty( $flat_ids ) ) {
			return [];
		}

		$lock_map = [];

		// 預先取得所有章節的 finished_at 狀態（減少重複 DB 查詢）
		$finished_map = [];
		foreach ( $flat_ids as $cid ) {
			$finished_at = AVLChapterMeta::get( $cid, $user_id, 'finished_at', true );
			$finished_map[ $cid ] = ! empty( $finished_at );
		}

		// 若未啟用線性觀看，全部回傳 false
		$enable_linear = (string) \get_post_meta( $course_id, 'enable_linear_viewing', true );
		if ( 'yes' !== $enable_linear ) {
			foreach ( $flat_ids as $cid ) {
				$lock_map[ $cid ] = false;
			}
			return $lock_map;
		}

		// 管理員不受限
		$user = \get_userdata( $user_id );
		if ( $user && $user->has_cap( 'manage_woocommerce' ) ) {
			foreach ( $flat_ids as $cid ) {
				$lock_map[ $cid ] = false;
			}
			return $lock_map;
		}

		// 計算每個章節的鎖定狀態
		foreach ( $flat_ids as $idx => $cid ) {
			// 第一個章節永遠解鎖
			if ( 0 === $idx ) {
				$lock_map[ $cid ] = false;
				continue;
			}

			// 自身已完成 → 解鎖
			if ( $finished_map[ $cid ] ) {
				$lock_map[ $cid ] = false;
				continue;
			}

			// 前一個已完成 → 解鎖
			$prev_id = $flat_ids[ $idx - 1 ] ?? null;
			if ( null !== $prev_id && $finished_map[ $prev_id ] ) {
				$lock_map[ $cid ] = false;
				continue;
			}

			// 否則鎖定
			$lock_map[ $cid ] = true;
		}

		return $lock_map;
	}
}
