<?php
/**
 * 設定課程線性觀看模式 整合測試
 * Feature: specs/features/linear-viewing/設定課程線性觀看模式.feature
 *
 * @group linear-viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;

/**
 * Class LinearModeSettingTest
 * 測試管理員設定課程線性觀看模式的業務邏輯
 *
 * TODO: 實作時需要：
 * TODO: - J7\PowerCourse\Resources\Chapter\Utils\LinearAccess 靜態工具類
 * TODO: - WC Product Meta 'enable_linear_mode' 讀寫
 */
class LinearModeSettingTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int 管理員 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WooCommerce Product Meta 和 LinearAccess
	}

	/**
	 * 每個測試前建立 Background 資料
	 */
	public function set_up(): void {
		parent::set_up();

		// Background: 系統中有以下用戶（Admin）
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;

		// Background: 系統中有以下課程（enable_linear_mode = no）
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
	}

	// ========== 前置（狀態）- 操作者必須具有 manage_woocommerce 能力 ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 前置（狀態）- 操作者必須具有 manage_woocommerce 能力
	 * Example: 無管理權限時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_無管理權限時操作失敗(): void {
		// Given 系統中有以下用戶（Bob, subscriber）
		$bob_id = $this->factory()->user->create(
			[
				'user_login' => 'bob_' . uniqid(),
				'user_email' => 'bob_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Bob'] = $bob_id;

		// When 用戶 "Bob" 更新課程 100 的 enable_linear_mode 為 "yes"
		// （嘗試以非管理員身份更新 product meta）

		// Then 操作失敗，錯誤為「權限不足」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 前置（狀態）- 課程必須存在且 _is_course 為 yes ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes
	 * Example: 課程不存在時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_課程不存在時操作失敗(): void {
		// Given 管理員 "Admin"
		\wp_set_current_user( $this->admin_id );

		// When 管理員 "Admin" 更新課程 9999 的 enable_linear_mode 為 "yes"
		// （課程 ID 9999 不存在）

		// Then 操作失敗，錯誤為「課程不存在」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 前置（參數）- enable_linear_mode 值必須為 "yes" 或 "no" ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 前置（參數）- enable_linear_mode 值必須為 "yes" 或 "no"
	 * Example: 非法值時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_非法值時操作失敗(): void {
		// Given 管理員 "Admin"
		\wp_set_current_user( $this->admin_id );

		// When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "maybe"
		// （非法值）

		// Then 操作失敗

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 後置（狀態）- 成功開啟線性觀看模式 ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 成功開啟線性觀看模式
	 * Example: 開啟線性觀看
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_開啟線性觀看(): void {
		// Given 管理員 "Admin"
		\wp_set_current_user( $this->admin_id );

		// When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "yes"

		// Then 操作成功
		// And 課程 100 的 product meta "enable_linear_mode" 應為 "yes"

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 後置（狀態）- 成功關閉線性觀看模式 ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 成功關閉線性觀看模式
	 * Example: 關閉線性觀看
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_關閉線性觀看(): void {
		// Given 課程 100 的 enable_linear_mode 為 "yes"（已開啟）
		\update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And 管理員 "Admin"
		\wp_set_current_user( $this->admin_id );

		// When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "no"

		// Then 操作成功
		// And 課程 100 的 product meta "enable_linear_mode" 應為 "no"

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 後置（狀態）- 預設值為 "no"（關閉）==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 預設值為 "no"（關閉）
	 * Example: 未設定時預設為關閉
	 *
	 * TODO: [事件風暴部位: Read Model - GetLinearMode]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_未設定時預設為關閉(): void {
		// Given 課程 100（從未設定 enable_linear_mode）

		// When 查詢課程 100 的 enable_linear_mode

		// Then 值應為 "no"

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 後置（狀態）- 關閉後所有學員立即恢復自由觀看 ==========

	/**
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 關閉後所有學員立即恢復自由觀看
	 * Example: 關閉線性觀看後鎖定解除
	 *
	 * TODO: [事件風暴部位: Command - UpdateLinearMode + Read Model - IsChapterLocked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 * TODO: 需要 LinearAccess::is_chapter_locked() 方法
	 */
	public function test_關閉線性觀看後鎖定解除(): void {
		// Given 課程 100 的 enable_linear_mode 為 "yes"
		\update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And 課程 100 有以下章節（扁平 menu_order 排序）
		$chapter_201 = $this->create_chapter( $this->course_id, [ 'post_title' => '1-1', 'menu_order' => 10 ] );
		$chapter_202 = $this->create_chapter( $this->course_id, [ 'post_title' => '1-2', 'menu_order' => 20 ] );
		$chapter_203 = $this->create_chapter( $this->course_id, [ 'post_title' => '1-3', 'menu_order' => 30 ] );

		// And 用戶 "Alice" 已被加入課程 100
		$alice_id = $this->factory()->user->create( [ 'user_login' => 'alice_' . uniqid(), 'user_email' => 'alice_' . uniqid() . '@test.com' ] );
		$this->ids['Alice'] = $alice_id;
		$this->enroll_user_to_course( $alice_id, $this->course_id, 0 );

		// And 用戶 "Alice" 僅完成章節 201
		$this->set_chapter_finished( $chapter_201, $alice_id, '2025-01-01 10:00:00' );

		// And 管理員 "Admin"
		\wp_set_current_user( $this->admin_id );

		// When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "no"

		// Then 操作成功
		// And 用戶 "Alice" 可存取章節 201, 202, 203（全部解鎖）
		// （LinearAccess::is_chapter_locked() 對所有章節均回傳 false）

		$this->markTestIncomplete('尚未實作');
	}
}
