<?php
/**
 * 查詢章節鎖定狀態 整合測試
 * Feature: specs/features/linear-viewing/查詢章節鎖定狀態.feature
 *
 * @group linear-viewing
 * @group query
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class ChapterLockStatusTest
 * 測試章節鎖定狀態查詢邏輯
 * 驗證 is_chapter_locked() 及 get_all_chapters_lock_status() 方法
 */
class ChapterLockStatusTest extends TestCase {

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int 測試課程 ID（啟用線性觀看）*/
	private int $course_id;

	/** @var int 第一章（depth 0, menu_order 10）*/
	private int $ch_200;

	/** @var int 1-1 小節（depth 1, menu_order 10）*/
	private int $ch_201;

	/** @var int 1-2 小節（depth 1, menu_order 20）*/
	private int $ch_202;

	/** @var int 第二章（depth 0, menu_order 20）*/
	private int $ch_203;

	/** @var int 2-1 小節（depth 1, menu_order 10）*/
	private int $ch_204;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// ChapterUtils 使用靜態方法
	}

	/**
	 * 每個測試前建立測試資料
	 * 對應 Feature Background：課程 100 + 5 個章節 + Alice 學員
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立 Alice
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		// 建立啟用線性觀看的課程
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );

		// 建立章節（扁平序列：200 > 201 > 202 > 203 > 204）
		// 第一章（depth 0）
		$this->ch_200 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 10,
			]
		);

		// 1-1 小節（depth 1，parent = 第一章）
		$this->ch_201 = $this->factory()->post->create(
			[
				'post_title'  => '1-1 小節',
				'post_type'   => 'pc_chapter',
				'post_status' => 'publish',
				'post_parent' => $this->ch_200,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $this->ch_201, 'parent_course_id', $this->course_id );

		// 1-2 小節（depth 1，parent = 第一章）
		$this->ch_202 = $this->factory()->post->create(
			[
				'post_title'  => '1-2 小節',
				'post_type'   => 'pc_chapter',
				'post_status' => 'publish',
				'post_parent' => $this->ch_200,
				'menu_order'  => 20,
			]
		);
		update_post_meta( $this->ch_202, 'parent_course_id', $this->course_id );

		// 第二章（depth 0）
		$this->ch_203 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 20,
			]
		);

		// 2-1 小節（depth 1，parent = 第二章）
		$this->ch_204 = $this->factory()->post->create(
			[
				'post_title'  => '2-1 小節',
				'post_type'   => 'pc_chapter',
				'post_status' => 'publish',
				'post_parent' => $this->ch_203,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $this->ch_204, 'parent_course_id', $this->course_id );

		// 加入學員
		$this->enroll_user_to_course( $this->alice_id, $this->course_id );
	}

	// ========== 前置（狀態）==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 前置（狀態）- 未啟用線性觀看的課程，所有章節皆不鎖定
	 * Example: 自由觀看模式下所有章節解鎖
	 */
	public function test_自由觀看模式下所有章節解鎖(): void {
		// Given 課程的 enable_linear_viewing 為 "no"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );
		// 清除快取
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then 所有章節的 is_locked 應為 false
		foreach ($lock_status as $chapter_id => $is_locked) {
			$this->assertFalse( $is_locked, "未啟用線性觀看時，章節 {$chapter_id} 不應被鎖定" );
		}
	}

	// ========== 後置（回應）- 基礎解鎖邏輯 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 第一個章節（扁平序列第一個）永遠解鎖
	 * Example: 新學員僅第一個章節解鎖
	 */
	public function test_新學員僅第一個章節解鎖(): void {
		// Given 用戶 "Alice" 無任何章節完成紀錄（set_up 已確保）
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then 各章節的鎖定狀態
		$this->assertFalse( $lock_status[ $this->ch_200 ], '第一章應解鎖' );
		$this->assertTrue( $lock_status[ $this->ch_201 ], '1-1 小節應鎖定' );
		$this->assertTrue( $lock_status[ $this->ch_202 ], '1-2 小節應鎖定' );
		$this->assertTrue( $lock_status[ $this->ch_203 ], '第二章應鎖定' );
		$this->assertTrue( $lock_status[ $this->ch_204 ], '2-1 小節應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 完成前一章節後，下一章節解鎖
	 * Example: 完成第一章後 1-1 解鎖
	 */
	public function test_完成第一章後1_1解鎖(): void {
		// Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then
		$this->assertFalse( $lock_status[ $this->ch_200 ], '第一章應解鎖（已完成）' );
		$this->assertFalse( $lock_status[ $this->ch_201 ], '1-1 小節應解鎖（前一章已完成）' );
		$this->assertTrue( $lock_status[ $this->ch_202 ], '1-2 小節應鎖定' );
		$this->assertTrue( $lock_status[ $this->ch_203 ], '第二章應鎖定' );
		$this->assertTrue( $lock_status[ $this->ch_204 ], '2-1 小節應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 父章節納入線性序列，必須標記完成才能解鎖子章節
	 * Example: 依序完成到 1-2 小節後，第二章解鎖
	 */
	public function test_依序完成到1_2小節後第二章解鎖(): void {
		// Given 用戶 "Alice" 依序完成前三章
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-06-01 10:10:00' );
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-06-01 10:20:00' );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then
		$this->assertFalse( $lock_status[ $this->ch_200 ], '第一章應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_201 ], '1-1 小節應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_202 ], '1-2 小節應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_203 ], '第二章應解鎖（前一章 1-2 已完成）' );
		$this->assertTrue( $lock_status[ $this->ch_204 ], '2-1 小節應鎖定' );
	}

	// ========== 後置（回應）- 取消完成的連鎖影響 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 取消完成某章節後，後續未完成的章節重新鎖定
	 * Example: 取消 1-1 完成後，1-2 重新鎖定
	 */
	public function test_取消1_1完成後1_2重新鎖定(): void {
		// Given 用戶 "Alice" 完成第一章，但 1-1 與 1-2 無 finished_at
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		// ch_201 無 finished_at
		// ch_202 無 finished_at
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then
		$this->assertFalse( $lock_status[ $this->ch_201 ], '1-1 小節應解鎖（前一章 200 已完成）' );
		$this->assertTrue( $lock_status[ $this->ch_202 ], '1-2 小節應鎖定（前一章 201 未完成）' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 已完成的章節即使前方有未完成章節，仍然解鎖（已完成=已解鎖）
	 * Example: 1-2 已完成但 1-1 被取消完成，1-2 仍可觀看
	 */
	public function test_已完成章節即使前方有未完成仍解鎖(): void {
		// Given 200 完成、201 無 finished_at、202 已完成
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		// ch_201 無 finished_at
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-06-01 10:20:00' );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then
		$this->assertFalse( $lock_status[ $this->ch_200 ], '第一章應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_201 ], '1-1 小節應解鎖（200 完成所以解鎖）' );
		$this->assertFalse( $lock_status[ $this->ch_202 ], '1-2 小節應解鎖（自身已完成）' );
		$this->assertTrue( $lock_status[ $this->ch_203 ], '第二章應鎖定（202 後的未完成且 201 未完成）' );
		$this->assertTrue( $lock_status[ $this->ch_204 ], '2-1 小節應鎖定' );
	}

	// ========== 後置（回應）- 章節重排序 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 重排序後，已完成章節不受影響，未完成章節依新順序判定
	 * Example: 重排序後已完成章節維持解鎖
	 */
	public function test_重排序後已完成章節維持解鎖(): void {
		// Given 用戶 "Alice" 完成 200 和 201
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-06-01 10:10:00' );

		// And 管理員將章節 203 的 menu_order 改為 5（排到最前面）
		wp_update_post( [ 'ID' => $this->ch_203, 'menu_order' => 5 ] );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then 已完成章節維持解鎖
		$this->assertFalse( $lock_status[ $this->ch_200 ], '章節 200 已完成，應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_201 ], '章節 201 已完成，應解鎖' );
	}

	// ========== 後置（回應）- 邊界情境 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 只有一個章節的課程，該章節始終解鎖
	 * Example: 單章節課程
	 */
	public function test_單章節課程始終解鎖(): void {
		// Given 建立一個啟用線性觀看的單章節課程
		$single_course_id = $this->create_course(
			[
				'post_title'  => '單章節課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		update_post_meta( $single_course_id, 'enable_linear_viewing', 'yes' );

		$only_chapter_id = $this->create_chapter( $single_course_id, [ 'post_title' => '唯一章節' ] );
		$this->enroll_user_to_course( $this->alice_id, $single_course_id );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $single_course_id, $this->alice_id );

		// Then 唯一章節應解鎖
		$this->assertArrayHasKey( $only_chapter_id, $lock_status, '唯一章節應在鎖定狀態結果中' );
		$this->assertFalse( $lock_status[ $only_chapter_id ], '唯一章節應解鎖' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 查詢章節鎖定狀態
	 * Rule: 後置（回應）- 中途啟用線性觀看時，已完成章節的進度被保留
	 * Example: 中途啟用線性觀看，已完成章節仍解鎖
	 */
	public function test_中途啟用線性觀看已完成章節仍解鎖(): void {
		// Given 課程的 enable_linear_viewing 為 "no"（先自由觀看）
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// And 用戶 "Alice" 已完成 200 和 201
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-06-01 10:10:00' );

		// And 管理員啟用線性觀看
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢鎖定狀態
		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		// Then
		$this->assertFalse( $lock_status[ $this->ch_200 ], '章節 200 已完成，應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_201 ], '章節 201 已完成，應解鎖' );
		$this->assertFalse( $lock_status[ $this->ch_202 ], '章節 202 應解鎖（201 已完成）' );
		$this->assertTrue( $lock_status[ $this->ch_203 ], '第二章應鎖定（202 未完成）' );
	}
}
