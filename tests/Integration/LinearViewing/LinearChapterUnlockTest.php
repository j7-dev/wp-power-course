<?php
/**
 * 線性觀看章節解鎖判定 整合測試
 * Feature: specs/features/linear-viewing/線性觀看章節解鎖判定.feature
 *
 * @group linear-viewing
 * @group read-model
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;

/**
 * Class LinearChapterUnlockTest
 * 測試線性觀看模式下章節解鎖判定邏輯
 *
 * 解鎖公式：章節 N 已解鎖 = (N 是第一章) OR (N 本身已完成) OR (N-1 已完成)
 *
 * TODO: 實作時需要 J7\PowerCourse\Resources\Chapter\Utils\LinearAccess::is_chapter_locked()
 */
class LinearChapterUnlockTest extends TestCase {

	/** @var int 測試課程 ID（enable_linear_mode = yes） */
	private int $course_id;

	/** @var int 章節 ID：第一章（menu_order 10） */
	private int $ch_201;

	/** @var int 章節 ID：1-1（menu_order 20） */
	private int $ch_202;

	/** @var int 章節 ID：1-2（menu_order 30） */
	private int $ch_203;

	/** @var int 章節 ID：第二章（menu_order 40） */
	private int $ch_204;

	/** @var int 章節 ID：2-1（menu_order 50） */
	private int $ch_205;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int Admin 用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	protected function configure_dependencies(): void {
		// 使用 LinearAccess 靜態方法
	}

	/**
	 * 每個測試前建立 Background 資料
	 *
	 * Background:
	 *   Given 系統中有以下用戶
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

		// 建立 5 個章節（含父章節結構）
		// 第一章（父章節，menu_order 10）
		$this->ch_201 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 10,
			]
		);

		// 1-1（子章節，parent = 第一章，menu_order 20）
		$this->ch_202 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'post_parent' => $this->ch_201,
				'menu_order'  => 20,
			]
		);

		// 1-2（子章節，parent = 第一章，menu_order 30）
		$this->ch_203 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'post_parent' => $this->ch_201,
				'menu_order'  => 30,
			]
		);

		// 第二章（父章節，menu_order 40）
		$this->ch_204 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 40,
			]
		);

		// 2-1（子章節，parent = 第二章，menu_order 50）
		$this->ch_205 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'post_parent' => $this->ch_204,
				'menu_order'  => 50,
			]
		);

		// Alice 加入課程（永久存取）
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	// ========== Rule: 第一個章節永遠解鎖 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 第一個章節（menu_order 最小）永遠解鎖
	 * Example: 無任何完成記錄時第一章為解鎖
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 * TODO: 需要 LinearAccess::is_chapter_locked($chapter_id, $user_id, $course_id)
	 */
	public function test_無任何完成記錄時第一章為解鎖(): void {
		// Given 用戶 "Alice" 無任何章節完成記錄（預設狀態）

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 201（第一章）應為「已解鎖」
		// And 章節 202（1-1）應為「已鎖定」
		// And 章節 203（1-2）應為「已鎖定」
		// And 章節 204（第二章）應為「已鎖定」
		// And 章節 205（2-1）應為「已鎖定」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 前一章已完成時下一章解鎖 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 前一章已完成時下一章解鎖
	 * Example: 完成第一章後 1-1 解鎖
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成第一章後1_1解鎖(): void {
		// Given 用戶 "Alice" 已完成章節 201（第一章）
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 201（第一章）應為「已解鎖」
		// And 章節 202（1-1）應為「已解鎖」
		// And 章節 203（1-2）應為「已鎖定」

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 前一章已完成時下一章解鎖
	 * Example: 依次完成後逐步解鎖
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_依次完成後逐步解鎖(): void {
		// Given 用戶 "Alice" 已完成章節 201, 202, 203
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-01-01 11:00:00' );
		$this->set_chapter_finished( $this->ch_203, $this->alice_id, '2025-01-01 12:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 204（第二章）應為「已解鎖」
		// And 章節 205（2-1）應為「已鎖定」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 已完成的章節永遠保持解鎖 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 已完成的章節永遠保持解鎖（不受順序影響）
	 * Example: 中途開啟線性觀看，已完成的跳序章節仍可存取
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_跳序完成章節仍可存取(): void {
		// Given 用戶 "Alice" 已完成章節 201, 203（跳過 202）
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_203, $this->alice_id, '2025-01-01 12:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 201（第一章）應為「已解鎖」
		// And 章節 202（1-1）應為「已解鎖」（因前一章 201 已完成）
		// And 章節 203（1-2）應為「已解鎖」（因本身已完成）
		// And 章節 204（第二章）應為「已鎖定」（203 雖已完成但解鎖條件不滿足——見 spec 說明）

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 解鎖判定公式完整驗證 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 解鎖判定公式：章節 N 已解鎖 = (N 是第一章) OR (N 本身已完成) OR (N-1 已完成)
	 * Example: 完整順序完成所有章節
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完整順序完成所有章節後全部解鎖(): void {
		// Given 用戶 "Alice" 已完成章節 201, 202, 203, 204, 205
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-01-01 11:00:00' );
		$this->set_chapter_finished( $this->ch_203, $this->alice_id, '2025-01-01 12:00:00' );
		$this->set_chapter_finished( $this->ch_204, $this->alice_id, '2025-01-01 13:00:00' );
		$this->set_chapter_finished( $this->ch_205, $this->alice_id, '2025-01-01 14:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 所有章節均為「已解鎖」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 所有章節（含父章節）皆參與線性順序 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 所有章節（含父章節）皆參與線性順序，不區分是否有子章節
	 * Example: 父章節未完成時其子章節被鎖定
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_父章節未完成時子章節被鎖定(): void {
		// Given 用戶 "Alice" 無任何章節完成記錄

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 201（第一章，父章節）應為「已解鎖」
		// And 章節 202（1-1，子章節）應為「已鎖定」

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 所有章節（含父章節）皆參與線性順序，不區分是否有子章節
	 * Example: 父章節完成後其子章節解鎖
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_父章節完成後子章節解鎖(): void {
		// Given 用戶 "Alice" 已完成章節 201（第一章，父章節）
		$this->set_chapter_finished( $this->ch_201, $this->alice_id, '2025-01-01 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態

		// Then 章節 202（1-1，子章節）應為「已解鎖」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 管理員繞過所有鎖定 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 具有 manage_woocommerce 能力的用戶繞過所有鎖定
	 * Example: 管理員可存取所有鎖定章節
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 * TODO: 需要 LinearAccess::can_bypass_linear()
	 */
	public function test_管理員可存取所有鎖定章節(): void {
		// Given 用戶 "Admin" 無任何章節完成記錄

		// When 查詢用戶 "Admin" 在課程 100 的章節解鎖狀態

		// Then 所有章節均為「已解鎖」（因 can_bypass_linear = true）

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 課程作者（講師）繞過鎖定 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 課程作者（講師）繞過鎖定
	 * Example: 講師可存取所有鎖定章節
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 * TODO: 需要 LinearAccess::can_bypass_linear()
	 */
	public function test_講師可存取所有鎖定章節(): void {
		// Given 系統中有以下用戶（Teacher, author role）
		$teacher_id = $this->factory()->user->create(
			[
				'user_login' => 'teacher_' . uniqid(),
				'user_email' => 'teacher_' . uniqid() . '@test.com',
				'role'       => 'author',
			]
		);
		$this->ids['Teacher'] = $teacher_id;

		// And 用戶 "Teacher" 為課程 100 的作者（設定 post_author）
		\wp_update_post(
			[
				'ID'          => $this->course_id,
				'post_author' => $teacher_id,
			]
		);

		// When 查詢用戶 "Teacher" 在課程 100 的章節解鎖狀態

		// Then 所有章節均為「已解鎖」（因 can_bypass_linear = true）

		$this->markTestIncomplete('尚未實作');
	}

	// ========== Rule: 未開啟線性觀看的課程，所有章節均為已解鎖 ==========

	/**
	 * Feature: 線性觀看章節解鎖判定
	 * Rule: 未開啟線性觀看的課程，所有章節均為已解鎖
	 * Example: 一般課程不受線性限制
	 *
	 * TODO: [事件風暴部位: Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_未開啟線性觀看的課程全部解鎖(): void {
		// Given 系統中有以下課程（enable_linear_mode = no）
		$course_300 = $this->create_course(
			[
				'post_title'  => 'CSS 進階課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		// enable_linear_mode 預設為 'no'，不額外設定

		// And 課程 300 有以下章節
		$ch_301 = $this->create_chapter( $course_300, [ 'post_title' => '基礎篇', 'menu_order' => 10 ] );
		$ch_302 = $this->create_chapter( $course_300, [ 'post_title' => '進階篇', 'menu_order' => 20 ] );

		// And 用戶 "Alice" 已被加入課程 300
		$this->enroll_user_to_course( $this->alice_id, $course_300, 0 );

		// And 用戶 "Alice" 無任何章節完成記錄

		// When 查詢用戶 "Alice" 在課程 300 的章節解鎖狀態

		// Then 所有章節均為「已解鎖」（is_linear_mode_enabled = false）

		$this->markTestIncomplete('尚未實作');
	}
}
