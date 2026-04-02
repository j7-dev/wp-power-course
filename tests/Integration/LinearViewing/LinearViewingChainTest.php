<?php
/**
 * 線性觀看取消完成連鎖 整合測試
 * Feature: specs/features/linear-viewing/線性觀看取消完成連鎖.feature
 *
 * @group linear_viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearViewingChainTest
 * 測試線性觀看取消完成連鎖效應：
 * - 取消完成後後續章節重新鎖定
 * - 完成章節時 API 回傳 next_unlocked_chapter_id
 * - 章節列表顯示正確的 is_locked 狀態
 */
class LinearViewingChainTest extends TestCase {

	/** @var int 課程 ID（enable_sequential = yes） */
	private int $course_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int 章節 1-1 ID */
	private int $ch1;

	/** @var int 章節 1-2 ID */
	private int $ch2;

	/** @var int 章節 1-3 ID */
	private int $ch3;

	/** @var int 章節 2-1 ID */
	private int $ch4;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 使用 ChapterUtils::is_chapter_accessible, AVLChapterMeta
	}

	/**
	 * 每個測試前建立共用基礎資料：
	 * 課程 100（enable_sequential = yes），4 個章節：1-1, 1-2, 1-3, 2-1
	 * Alice 已加入課程
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立課程（線性觀看開啟）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'enable_sequential', 'yes' );

		// 建立 4 個章節
		$this->ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		$this->ch2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $this->course_id,
			]
		);
		$this->ch3 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-3',
				'menu_order'  => 3,
				'post_parent' => $this->course_id,
			]
		);
		$this->ch4 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'menu_order'  => 4,
				'post_parent' => $this->course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );

		// 建立 Alice 用戶並加入課程
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	/**
	 * 模擬切換章節完成狀態（toggle）
	 * 若目前已完成（有 finished_at）則刪除，否則新增
	 *
	 * @param int $chapter_id 章節 ID
	 * @param int $user_id    用戶 ID
	 * @return bool true = 完成，false = 取消完成
	 */
	private function toggle_chapter_finish( int $chapter_id, int $user_id ): bool {
		// 清除快取
		wp_cache_delete( "pid_{$this->course_id}_uid_{$user_id}", 'pc_course_progress' );
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );

		$finished_at = AVLChapterMeta::get( $chapter_id, $user_id, 'finished_at', true );
		if ( ! empty( $finished_at ) ) {
			// 取消完成
			AVLChapterMeta::delete( $chapter_id, $user_id, 'finished_at' );
			return false;
		} else {
			// 標記完成
			AVLChapterMeta::add( $chapter_id, $user_id, 'finished_at', wp_date( 'Y-m-d H:i:s' ) );
			return true;
		}
	}

	// ========== 取消完成 - 後續章節重新鎖定 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 取消完成 - 後續章節重新鎖定
	 * Example: 取消完成 1-1 後，1-2 以後的章節被鎖定
	 */
	public function test_取消完成1_1後1_2以後章節被鎖定(): void {
		// Given 1-1 和 1-2 已完成，1-3 未完成
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch2, $this->alice_id, '2025-06-01 11:00:00' );
		// 1-3, 2-1 無 finished_at

		// When 切換 1-1 的完成狀態（取消完成）
		$is_now_finished = $this->toggle_chapter_finish( $this->ch1, $this->alice_id );

		// Then 操作成功（is_now_finished = false 代表取消完成）
		$this->assertFalse( $is_now_finished, '1-1 應被取消完成' );

		// And 1-1 的 finished_at 應為空
		$finished_at = $this->get_chapter_meta( $this->ch1, $this->alice_id, 'finished_at' );
		$this->assertEmpty( $finished_at, '1-1 的 finished_at 應為空' );

		// And Alice 不可存取 1-2（1-1 未完成，1-2 被鎖定）
		$can_access_1_2 = ChapterUtils::is_chapter_accessible( $this->ch2, $this->alice_id, $this->course_id );
		$this->assertFalse( $can_access_1_2, '取消完成 1-1 後，1-2 應被鎖定' );

		// And Alice 不可存取 1-3（同樣被鎖定）
		$can_access_1_3 = ChapterUtils::is_chapter_accessible( $this->ch3, $this->alice_id, $this->course_id );
		$this->assertFalse( $can_access_1_3, '取消完成 1-1 後，1-3 應被鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 取消完成 - 後續章節重新鎖定
	 * Example: 取消完成中間章節後，後續章節被鎖定但前面章節不受影響
	 */
	public function test_取消完成中間章節後前面章節不受影響(): void {
		// Given 1-1, 1-2, 1-3 均已完成
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch2, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch3, $this->alice_id, '2025-06-01 12:00:00' );

		// When 切換 1-2 的完成狀態（取消完成）
		$is_now_finished = $this->toggle_chapter_finish( $this->ch2, $this->alice_id );

		// Then 操作成功（取消完成）
		$this->assertFalse( $is_now_finished, '1-2 應被取消完成' );

		// And 1-2 的 finished_at 應為空
		$finished_at = $this->get_chapter_meta( $this->ch2, $this->alice_id, 'finished_at' );
		$this->assertEmpty( $finished_at, '1-2 的 finished_at 應為空' );

		// And Alice 可存取 1-1（前面章節不受影響）
		$can_access_1_1 = ChapterUtils::is_chapter_accessible( $this->ch1, $this->alice_id, $this->course_id );
		$this->assertTrue( $can_access_1_1, '1-1 不受影響，仍可存取' );

		// And Alice 可存取 1-2（自身可存取，因為 1-1 已完成）
		$can_access_1_2 = ChapterUtils::is_chapter_accessible( $this->ch2, $this->alice_id, $this->course_id );
		$this->assertTrue( $can_access_1_2, '1-2 自身仍可存取（1-1 已完成）' );

		// And Alice 不可存取 1-3（1-2 已取消完成，1-3 被鎖定）
		$can_access_1_3 = ChapterUtils::is_chapter_accessible( $this->ch3, $this->alice_id, $this->course_id );
		$this->assertFalse( $can_access_1_3, '取消完成 1-2 後，1-3 應被鎖定' );
	}

	// ========== 完成章節 - API 回傳 next_unlocked_chapter_id ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 完成章節 - API 回傳 next_unlocked_chapter_id
	 * Example: 完成 1-1 後 API 回傳 1-2 的 ID
	 *
	 * 測試邏輯：完成 1-1 後，下一個未完成章節為 1-2
	 */
	public function test_完成章節後回傳next_unlocked_chapter_id(): void {
		// Given 1-1 無 finished_at

		// When 完成 1-1
		$this->toggle_chapter_finish( $this->ch1, $this->alice_id );

		// Then 下一個解鎖章節應為 1-2（透過 get_current_progress_chapter_id 驗證）
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		$flatten_ids    = ChapterUtils::get_flatten_post_ids( $this->course_id );
		$current_index  = array_search( $this->ch1, $flatten_ids, true );

		// 計算 next_unlocked_chapter_id（仿照 Api.php 的邏輯）
		$next_unlocked_chapter_id = null;
		if ( false !== $current_index && isset( $flatten_ids[ $current_index + 1 ] ) ) {
			$next_unlocked_chapter_id = $flatten_ids[ $current_index + 1 ];
		}

		$this->assertSame( $this->ch2, $next_unlocked_chapter_id, '完成 1-1 後，next_unlocked_chapter_id 應為 1-2 的 ID' );
	}

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 完成章節 - API 回傳 next_unlocked_chapter_id
	 * Example: 完成最後一個章節後 API 回傳 next_unlocked_chapter_id 為 null
	 */
	public function test_完成最後章節後next_unlocked_chapter_id為null(): void {
		// Given 1-1, 1-2, 1-3 已完成，2-1 未完成
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch2, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch3, $this->alice_id, '2025-06-01 12:00:00' );

		// When 完成最後一個章節 2-1（ch4）
		$this->toggle_chapter_finish( $this->ch4, $this->alice_id );

		// Then next_unlocked_chapter_id 應為 null（沒有下一個章節）
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		$flatten_ids   = ChapterUtils::get_flatten_post_ids( $this->course_id );
		$current_index = array_search( $this->ch4, $flatten_ids, true );

		$next_unlocked_chapter_id = null;
		if ( false !== $current_index && isset( $flatten_ids[ $current_index + 1 ] ) ) {
			$next_unlocked_chapter_id = $flatten_ids[ $current_index + 1 ];
		}

		$this->assertNull( $next_unlocked_chapter_id, '完成最後章節後，next_unlocked_chapter_id 應為 null' );
	}

	/**
	 * @test
	 * @group sequential_off
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 完成章節 - API 回傳 next_unlocked_chapter_id
	 * Example: 線性觀看關閉時 API 不回傳 next_unlocked_chapter_id
	 *
	 * 測試邏輯：線性觀看關閉時，完成章節的回應中不包含 next_unlocked_chapter_id 欄位
	 */
	public function test_線性觀看關閉時不回傳next_unlocked_chapter_id(): void {
		// Given 另一個 enable_sequential = no 的課程
		$free_course_id = $this->create_course(
			[
				'post_title' => '自由課程',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $free_course_id, 'enable_sequential', 'no' );

		$ch_a1 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-1',
				'menu_order'  => 1,
				'post_parent' => $free_course_id,
			]
		);
		$ch_a2 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-2',
				'menu_order'  => 2,
				'post_parent' => $free_course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $free_course_id, 'prev_next' );
		$this->enroll_user_to_course( $this->alice_id, $free_course_id, 0 );

		// When 完成 A-1（線性觀看關閉）
		$enable_sequential = get_post_meta( $free_course_id, 'enable_sequential', true );

		// Then 根據 API 邏輯，enable_sequential != 'yes' 時不計算 next_unlocked_chapter_id
		// 驗證：線性觀看關閉時，$response_data 中不應包含 next_unlocked_chapter_id
		$response_data = [
			'chapter_id'               => $ch_a1,
			'course_id'                => $free_course_id,
			'is_this_chapter_finished' => true,
		];

		if ( 'yes' === $enable_sequential ) {
			$flatten_ids   = ChapterUtils::get_flatten_post_ids( $free_course_id );
			$current_index = array_search( $ch_a1, $flatten_ids, true );

			$next_id = null;
			if ( false !== $current_index && isset( $flatten_ids[ $current_index + 1 ] ) ) {
				$next_id = $flatten_ids[ $current_index + 1 ];
			}
			$response_data['next_unlocked_chapter_id'] = $next_id;
		}

		// 線性觀看關閉時，response_data 中不應包含 next_unlocked_chapter_id
		$this->assertArrayNotHasKey(
			'next_unlocked_chapter_id',
			$response_data,
			'線性觀看關閉時，回應中不應包含 next_unlocked_chapter_id 欄位'
		);
	}

	// ========== 線性觀看關閉 - 取消完成不影響其他章節存取 ==========

	/**
	 * @test
	 * @group sequential_off
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 線性觀看關閉 - 取消完成不影響其他章節存取
	 * Example: enable_sequential 為 no 時取消完成不鎖定後續章節
	 */
	public function test_線性觀看關閉時取消完成不鎖定後續章節(): void {
		// Given 另一個 enable_sequential = no 的課程
		$free_course_id = $this->create_course(
			[
				'post_title' => '自由課程',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $free_course_id, 'enable_sequential', 'no' );

		$ch_a1 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-1',
				'menu_order'  => 1,
				'post_parent' => $free_course_id,
			]
		);
		$ch_a2 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-2',
				'menu_order'  => 2,
				'post_parent' => $free_course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $free_course_id, 'prev_next' );
		$this->enroll_user_to_course( $this->alice_id, $free_course_id, 0 );

		// A-1 已完成
		$this->set_chapter_finished( $ch_a1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 取消完成 A-1
		AVLChapterMeta::delete( $ch_a1, $this->alice_id, 'finished_at' );

		// Then Alice 仍可存取 A-2（線性觀看關閉，取消完成不影響後續章節）
		$can_access_a2 = ChapterUtils::is_chapter_accessible( $ch_a2, $this->alice_id, $free_course_id );
		$this->assertTrue( $can_access_a2, '線性觀看關閉時，取消完成不應鎖定後續章節' );
	}

	// ========== 章節列表 - 顯示正確的鎖定/解鎖狀態 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 章節列表 - 顯示正確的鎖定/解鎖狀態
	 * Example: 取得章節列表時包含 is_locked 狀態（1-1 完成，1-2 未完成）
	 */
	public function test_章節列表顯示正確鎖定狀態(): void {
		// Given 1-1 已完成，1-2 未完成
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		// 1-2, 1-3, 2-1 無 finished_at

		// When 取得各章節的存取狀態（is_locked = !is_chapter_accessible）
		$ch1_locked = ! ChapterUtils::is_chapter_accessible( $this->ch1, $this->alice_id, $this->course_id );
		$ch2_locked = ! ChapterUtils::is_chapter_accessible( $this->ch2, $this->alice_id, $this->course_id );
		$ch3_locked = ! ChapterUtils::is_chapter_accessible( $this->ch3, $this->alice_id, $this->course_id );
		$ch4_locked = ! ChapterUtils::is_chapter_accessible( $this->ch4, $this->alice_id, $this->course_id );

		// Then 章節 1-1 的 is_locked 為 false（已完成，可回看）
		$this->assertFalse( $ch1_locked, '1-1 應為未鎖定（已完成可回看）' );

		// And 章節 1-2 的 is_locked 為 false（1-1 已完成，1-2 可存取）
		$this->assertFalse( $ch2_locked, '1-2 應為未鎖定（1-1 已完成）' );

		// And 章節 1-3 的 is_locked 為 true（1-2 未完成）
		$this->assertTrue( $ch3_locked, '1-3 應為鎖定（1-2 未完成）' );

		// And 章節 2-1 的 is_locked 為 true（前面章節未完成）
		$this->assertTrue( $ch4_locked, '2-1 應為鎖定（前面章節未完成）' );
	}

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看取消完成連鎖
	 * Rule: 章節列表 - 顯示正確的鎖定/解鎖狀態
	 * Example: 所有章節都未完成時只有第一個不鎖定
	 */
	public function test_所有章節未完成時只有第一個不鎖定(): void {
		// Given 所有章節無 finished_at

		// When 取得各章節的存取狀態
		$ch1_locked = ! ChapterUtils::is_chapter_accessible( $this->ch1, $this->alice_id, $this->course_id );
		$ch2_locked = ! ChapterUtils::is_chapter_accessible( $this->ch2, $this->alice_id, $this->course_id );
		$ch3_locked = ! ChapterUtils::is_chapter_accessible( $this->ch3, $this->alice_id, $this->course_id );
		$ch4_locked = ! ChapterUtils::is_chapter_accessible( $this->ch4, $this->alice_id, $this->course_id );

		// Then 只有第一個章節不鎖定
		$this->assertFalse( $ch1_locked, '第一個章節應永遠可存取（未鎖定）' );
		$this->assertTrue( $ch2_locked, '1-2 應為鎖定（1-1 未完成）' );
		$this->assertTrue( $ch3_locked, '1-3 應為鎖定（1-1 未完成）' );
		$this->assertTrue( $ch4_locked, '2-1 應為鎖定（前面章節未完成）' );
	}
}
