<?php
/**
 * 切換章節完成狀態（線性觀看擴展）整合測試
 * Feature: specs/features/progress/切換章節完成狀態.feature（線性觀看 Rules）
 *
 * @group chapter
 * @group sequential
 * @group toggle-finish
 */

declare( strict_types=1 );

namespace Tests\Integration\Chapter;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class ToggleFinishSequentialTest
 * 測試線性觀看模式下切換章節完成狀態的 API 行為
 *
 * 此測試類別聚焦於線性觀看模式的特殊行為：
 * 1. 鎖定章節的 403 保護
 * 2. 完成後 next_unlocked_chapter_id 的回傳
 */
class ToggleFinishSequentialTest extends TestCase {

	/** @var int 課程 ID */
	private int $course_id;

	/** @var int 章節 1 ID（順序第 1） */
	private int $chapter_1_id;

	/** @var int 章節 2 ID（順序第 2） */
	private int $chapter_2_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var \WP_REST_Request 最後的 REST 請求 */
	private ?\WP_REST_Request $lastRequest = null;

	/** @var \WP_REST_Response|\WP_Error|null 最後的 REST 回應 */
	private mixed $lastResponse = null;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 REST API 測試工具
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立課程（is_sequential = true）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'is_sequential', 'yes' );

		// 建立 2 個章節（依序）
		$this->chapter_1_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 1,
			]
		);
		$this->chapter_2_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 2,
			]
		);

		// 建立 Alice 用戶並加入課程
		$this->alice_id     = $this->factory()->user->create(
			[
				'user_login' => 'alice_seq_' . uniqid(),
				'user_email' => 'alice_seq_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	// ========== Rule: 線性觀看模式下不可切換被鎖定章節的完成狀態 ==========

	/**
	 * @test
	 * @group sequential
	 * @group error
	 * Feature: 切換章節完成狀態
	 * Rule: 前置（狀態）- 線性觀看模式下不可切換被鎖定章節的完成狀態
	 * Example: 線性觀看模式下嘗試完成鎖定章節時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish，驗證鎖定狀態]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When（REST API 呼叫）
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_線性觀看模式下嘗試完成鎖定章節時操作失敗(): void {
		// Given 課程 100 的 is_sequential 為 true（已在 set_up 設定）
		// And 用戶 "Alice" 無任何章節完成紀錄

		// When 用戶 "Alice" 切換章節 201（第二章，被鎖定）的完成狀態
		// 呼叫 REST API: POST /power-course/v2/toggle-finish-chapters/{chapter_2_id}

		// Then 操作失敗，錯誤為「請先完成前面的章節」
		// HTTP status code 應為 403

		$this->markTestIncomplete('尚未實作：需要 Toggle Finish API 的鎖定驗證 + ChapterUtils::is_chapter_locked()');
	}

	// ========== Rule: 線性觀看模式下完成章節時回傳 next_unlocked_chapter_id ==========

	/**
	 * @test
	 * @group sequential
	 * @group happy
	 * Feature: 切換章節完成狀態
	 * Rule: 後置（回應）- 線性觀看模式下完成章節時回傳下一個解鎖的章節 ID
	 * Example: 完成章節後回傳 next_unlocked_chapter_id
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish + ReadModel - next_unlocked_chapter_id]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成章節後回傳next_unlocked_chapter_id(): void {
		// Given 課程 100 的 is_sequential 為 true（已在 set_up 設定）
		// And 用戶 "Alice" 在章節 200（第一章）無 finished_at

		// When 用戶 "Alice" 切換章節 200（第一章）的完成狀態
		// 呼叫 REST API: POST /power-course/v2/toggle-finish-chapters/{chapter_1_id}

		// Then 操作成功
		// And 回應中 next_unlocked_chapter_id 應為 chapter_2_id（第二章 ID）

		$this->markTestIncomplete('尚未實作：需要 Toggle Finish API 增加 next_unlocked_chapter_id + ChapterUtils::get_next_available_chapter_id()');
	}

	/**
	 * @test
	 * @group sequential
	 * @group happy
	 * Feature: 切換章節完成狀態
	 * Rule: 後置（回應）- 線性觀看模式下完成章節時回傳下一個解鎖的章節 ID
	 * Example: 完成最後一個章節時 next_unlocked_chapter_id 為 null
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish（最後章節）]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成最後章節時next_unlocked_chapter_id為null(): void {
		// Given 課程 100 的 is_sequential 為 true（已在 set_up 設定）
		// And 用戶 "Alice" 在章節 200（第一章）的 finished_at 已設定
		$this->set_chapter_finished( $this->chapter_1_id, $this->alice_id, '2025-06-01 10:00:00' );
		// And 用戶 "Alice" 在章節 201（第二章）無 finished_at

		// When 用戶 "Alice" 切換章節 201（第二章，最後章節）的完成狀態
		// 呼叫 REST API: POST /power-course/v2/toggle-finish-chapters/{chapter_2_id}

		// Then 操作成功
		// And 回應中 next_unlocked_chapter_id 應為 null

		$this->markTestIncomplete('尚未實作：需要 Toggle Finish API 增加 next_unlocked_chapter_id + ChapterUtils::get_next_available_chapter_id()');
	}

	// ========== Rule: 非線性觀看模式下不回傳 next_unlocked_chapter_id ==========

	/**
	 * @test
	 * @group sequential
	 * @group happy
	 * Feature: 切換章節完成狀態
	 * Rule: 後置（回應）- 非線性觀看模式下不回傳 next_unlocked_chapter_id
	 * Example: 非線性觀看模式下回應不含 next_unlocked_chapter_id
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish（is_sequential=false）]
	 */
	public function test_非線性模式下回應不含next_unlocked_chapter_id(): void {
		// Given 課程 100 的 is_sequential 為 false
		update_post_meta( $this->course_id, 'is_sequential', 'no' );
		// And 用戶 "Alice" 在章節 200（第一章）無 finished_at

		// When 用戶 "Alice" 切換章節 200（第一章）的完成狀態
		// 呼叫 REST API: POST /power-course/v2/toggle-finish-chapters/{chapter_1_id}

		// Then 操作成功
		// And 回應中 next_unlocked_chapter_id 應為 null

		$this->markTestIncomplete('尚未實作：需要 Toggle Finish API 增加 next_unlocked_chapter_id（非線性模式返回 null）');
	}
}
