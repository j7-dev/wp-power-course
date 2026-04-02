<?php
/**
 * 線性觀看完成章節限制 整合測試
 * Feature: specs/features/linear-viewing/線性觀看完成章節限制.feature
 *
 * @group linear-viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;

/**
 * Class LinearFinishRestrictionTest
 * 測試線性觀看模式下完成章節的限制行為：
 * 1. 禁止取消已完成的章節
 * 2. 完成後 API 回傳下一章解鎖資訊
 * 3. 鎖定章節不可被完成
 *
 * TODO: 實作時需要：
 * TODO: - J7\PowerCourse\Resources\Chapter\Utils\LinearAccess
 * TODO: - 修改 Chapter Core Api::post_toggle_finish_chapters_with_id_callback()
 */
class LinearFinishRestrictionTest extends TestCase {

	/** @var int 測試課程 ID（enable_linear_mode = yes） */
	private int $course_id;

	/** @var int 章節 201 ID（menu_order 10） */
	private int $ch_201;

	/** @var int 章節 202 ID（menu_order 20） */
	private int $ch_202;

	/** @var int 章節 203 ID（menu_order 30） */
	private int $ch_203;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int Admin 用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When（toggle-finish）
	 */
	protected function configure_dependencies(): void {
		// 使用 LinearAccess + AVLChapterMeta
	}

	/**
	 * 每個測試前建立 Background 資料
	 *
	 * Background:
	 *   Given 系統中有以下用戶（Admin, Alice）
	 *   And 系統中有以下課程（enable_linear_mode = yes）
	 *   And 課程有以下章節（扁平 menu_order 排序）
	 *   And 用戶 "Alice" 已被加入課程，expire_date 0
	 */
	public function set_up(): void {
		parent::set_up();

		// Admin 用戶
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;

		// Alice 訂閱者
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		// 課程（線性觀看模式開啟）
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		\update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// 建立 3 個章節（扁平 menu_order 排序）
		$this->ch_201 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'post_parent' => $this->course_id,
				'menu_order'  => 10,
			]
		);
		$this->ch_202 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'post_parent' => $this->course_id,
				'menu_order'  => 20,
			]
		);
		$this->ch_203 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-3',
				'post_parent' => $this->course_id,
				'menu_order'  => 30,
			]
		);

		// Alice 加入課程（永久存取）
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	// ========== Rule: 線性觀看模式下，已完成的章節不可取消完成 ==========

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 線性觀看模式下，已完成的章節不可取消完成
	 * Example: 學員嘗試取消完成被拒絕
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When（toggle-finish API 或 LinearAccess 直接調用）
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 * TODO: 修改 Api::post_toggle_finish_chapters_with_id_callback() 加入攔截
	 */
	public function test_學員嘗試取消完成被拒絕(): void {
		// Given 用戶 "Alice" 已完成章節 201
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );
		\wp_set_current_user( $this->alice_id );

		// When 用戶 "Alice" 對章節 201 執行 toggle-finish（嘗試取消完成）
		// （is_this_chapter_finished = true → 進入取消完成分支）

		// Then 操作失敗
		// And 錯誤訊息為「線性觀看模式下無法取消已完成的章節」
		// And 章節 201 的 finished_at 應維持不變

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 線性觀看模式下，已完成的章節不可取消完成
	 * Example: 管理員可以取消完成（繞過限制）
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_管理員可以取消完成(): void {
		// Given 用戶 "Alice" 已完成章節 201
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );

		// When 管理員 "Admin" 代替用戶 "Alice" 取消章節 201 的完成狀態
		// （管理員有 manage_woocommerce 能力，繞過線性限制）
		\wp_set_current_user( $this->admin_id );

		// Then 操作成功

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 未開啟線性觀看的課程，取消完成正常運作 ==========

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 未開啟線性觀看的課程，取消完成正常運作
	 * Example: 一般課程可正常取消完成
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_一般課程可正常取消完成(): void {
		// Given 系統中有以下課程（enable_linear_mode = no）
		$course_300 = $this->create_course(
			[
				'post_title'  => 'CSS 進階課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		// enable_linear_mode 預設 'no'

		// And 課程 300 有以下章節
		$ch_301 = $this->create_chapter( $course_300, [ 'post_title' => '基礎篇', 'menu_order' => 10 ] );

		// And 用戶 "Alice" 已被加入課程 300
		$this->enroll_user_to_course( $this->alice_id, $course_300, 0 );

		// And 用戶 "Alice" 已完成章節 301
		$this->set_chapter_finished( $ch_301, $this->alice_id, '2025-01-01 10:00:00' );
		\wp_set_current_user( $this->alice_id );

		// When 用戶 "Alice" 對章節 301 執行 toggle-finish（取消完成）
		// （is_this_chapter_finished = true → 一般取消，不攔截）

		// Then 操作成功
		// And 章節 301 的 finished_at 應為空

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 完成章節後，toggle-finish API 回傳下一章解鎖資訊 ==========

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 完成章節後，toggle-finish API 回傳下一章解鎖資訊
	 * Example: API 回傳包含下一章解鎖狀態
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then（API 回傳欄位）
	 * TODO: 修改 Api::post_toggle_finish_chapters_with_id_callback() 加入 next_chapter_id 等欄位
	 */
	public function test_完成章節後API回傳下一章解鎖狀態(): void {
		// Given 用戶 "Alice" 已完成章節 201
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );

		// And 用戶 "Alice" 在章節 202 無 finished_at（預設狀態）
		\wp_set_current_user( $this->alice_id );

		// When 用戶 "Alice" 對章節 202 執行 toggle-finish（標記完成）
		// （章節 202 已解鎖，因前一章 201 已完成）

		// Then 操作成功
		// And API 回應 data 應包含：
		//   is_this_chapter_finished = true
		//   next_chapter_id          = ch_203
		//   next_chapter_unlocked    = true

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 完成最後一個章節時，不回傳下一章資訊 ==========

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 完成最後一個章節時，不回傳下一章資訊
	 * Example: 完成最後章節
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成最後章節不回傳下一章資訊(): void {
		// Given 用戶 "Alice" 已完成章節 201, 202
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-01-01 11:00:00' );
		\wp_set_current_user( $this->alice_id );

		// When 用戶 "Alice" 對章節 203 執行 toggle-finish（標記完成）

		// Then 操作成功
		// And API 回應 data 應包含：
		//   is_this_chapter_finished = true
		//   next_chapter_id          = null
		//   next_chapter_unlocked    = false

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 學員不可對鎖定的章節執行 toggle-finish ==========

	/**
	 * Feature: 線性觀看完成章節限制
	 * Rule: 學員不可對鎖定的章節執行 toggle-finish
	 * Example: 嘗試完成鎖定章節被拒絕
	 *
	 * TODO: [事件風暴部位: Command - ToggleFinishChapter]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 * TODO: 修改 Api::post_toggle_finish_chapters_with_id_callback() 加入鎖定章節攔截
	 */
	public function test_嘗試完成鎖定章節被拒絕(): void {
		// Given 用戶 "Alice" 無任何章節完成記錄（章節 202 已鎖定）
		\wp_set_current_user( $this->alice_id );

		// When 用戶 "Alice" 對章節 202（已鎖定）執行 toggle-finish
		// （章節 202 未解鎖：第一章 201 未完成）

		// Then 操作失敗
		// And 錯誤訊息為「此章節尚未解鎖，請先完成前面的章節」

		$this->markTestIncomplete('尚未實作');
	}
}
