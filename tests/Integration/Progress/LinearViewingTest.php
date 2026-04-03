<?php
/**
 * 線性觀看章節解鎖 整合測試
 * Feature: specs/features/progress/線性觀看章節解鎖.feature
 * Feature: specs/features/progress/切換章節完成狀態.feature (線性觀看相關 scenarios)
 *
 * @group progress
 * @group linear-viewing
 */

declare( strict_types=1 );

namespace Tests\Integration\Progress;

use Tests\Integration\TestCase;
use J7\PowerCourse\Utils\LinearViewing;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearViewingTest
 * 測試線性觀看章節解鎖演算法
 *
 * 注意：LinearViewing 類別尚未建立，所有測試應處於 Red 狀態。
 */
class LinearViewingTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int 章節 1 ID（1-1 PHP 簡介，menu_order=1） */
	private int $chapter_200_id;

	/** @var int 章節 2 ID（1-2 變數與型別，menu_order=2） */
	private int $chapter_201_id;

	/** @var int 章節 3 ID（1-3 條件判斷，menu_order=3） */
	private int $chapter_202_id;

	/** @var int 章節 4 ID（2-1 迴圈，menu_order=4） */
	private int $chapter_203_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int Admin 用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用靜態方法 LinearViewing::get_unlock_state()
	}

	/**
	 * 每個測試前建立測試資料
	 * Background:
	 *   - 課程 100（PHP 基礎課，enable_linear_viewing=yes）
	 *   - 4 個章節（menu_order 1~4）
	 *   - Alice（subscriber）已加入課程
	 *   - Admin（administrator）存在
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立課程，預設開啟線性觀看
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );

		// 建立 4 個章節
		$this->chapter_200_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '1-1 PHP 簡介',
				'menu_order' => 1,
			]
		);
		$this->chapter_201_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '1-2 變數與型別',
				'menu_order' => 2,
			]
		);
		$this->chapter_202_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '1-3 條件判斷',
				'menu_order' => 3,
			]
		);
		$this->chapter_203_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '2-1 迴圈',
				'menu_order' => 4,
			]
		);

		// 建立 Alice 用戶並加入課程
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Alice'] = $this->alice_id;
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );

		// 建立 Admin 用戶
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;
	}

	// ========== 前置（狀態）- 課程未開啟線性觀看時，所有章節均為解鎖 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 前置（狀態）- 課程未開啟線性觀看時，所有章節均為解鎖
	 * Example: 線性觀看關閉時所有章節可存取
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_線性觀看關閉時所有章節可存取(): void {
		// Given 課程 100 的 enable_linear_viewing 為 "no"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202, 203 均為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖' );
	}

	// ========== 前置（狀態）- 管理員預覽模式免除線性觀看限制 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 前置（狀態）- 管理員預覽模式免除線性觀看限制
	 * Example: 管理員預覽時所有章節可存取
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_管理員預覽時所有章節可存取(): void {
		// Given 用戶 "Admin" 為管理員且處於預覽模式（管理員未購課 → is_admin_preview = true）
		wp_set_current_user( $this->admin_id );

		// When 查詢用戶 "Admin" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->admin_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202, 203 均為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖' );

		// 還原用戶
		wp_set_current_user( 0 );
	}

	// ========== 後置（回應）- 第一個章節永遠解鎖，無完成紀錄時僅第一章節解鎖 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 第一個章節永遠解鎖，無完成紀錄時僅第一章節解鎖
	 * Example: 無任何完成紀錄時僅第一章節解鎖
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_無完成紀錄時僅第一章節解鎖(): void {
		// Given 用戶 "Alice" 未完成任何章節（預設狀態）

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );
		$this->assertTrue( $state['enabled'], '線性觀看應為啟用狀態' );

		// And 章節 200 為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );

		// And 章節 201, 202, 203 為鎖定狀態
		$this->assertNotContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應鎖定' );
		$this->assertNotContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應鎖定' );
		$this->assertNotContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應鎖定' );
	}

	// ========== 後置（回應）- 以最遠已完成章節位置為基準 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 以最遠已完成章節位置為基準，位置 0 到 (最遠位置+1) 為解鎖
	 * Example: 完成第一章節後下一章節解鎖
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成第一章節後下一章節解鎖(): void {
		// Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201 為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );

		// And 章節 202, 203 為鎖定狀態
		$this->assertNotContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應鎖定' );
		$this->assertNotContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 以最遠已完成章節位置為基準，位置 0 到 (最遠位置+1) 為解鎖
	 * Example: 連續完成兩章後第三章節解鎖
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_連續完成兩章後第三章節解鎖(): void {
		// Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		// And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-02 10:00:00"
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-02 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202 為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );

		// And 章節 203 為鎖定狀態
		$this->assertNotContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 以最遠已完成章節位置為基準，位置 0 到 (最遠位置+1) 為解鎖
	 * Example: 完成所有章節後全部解鎖
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_完成所有章節後全部解鎖(): void {
		// Given 用戶 "Alice" 完成所有章節
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-02 10:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-03 10:00:00' );
		$this->set_chapter_finished( $this->chapter_203_id, $this->alice_id, '2025-06-04 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202, 203 均為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖' );
	}

	// ========== 後置（回應）- 最遠進度模式忽略中間完成缺口 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 最遠進度模式忽略中間完成缺口
	 * Example: 跳躍完成時以最遠完成為基準
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_跳躍完成時以最遠完成為基準(): void {
		// Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		// And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-02 10:00:00"（跳過 201）
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-02 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		// 最遠完成 = 202（位置 2），解鎖到位置 3
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202, 203 均為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖（最遠進度模式忽略中間缺口）' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖（最遠完成 202 + 1）' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 最遠進度模式忽略中間完成缺口
	 * Example: 僅完成非首章節時以該章節為最遠基準
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_僅完成非首章節時以該章節為最遠基準(): void {
		// Given 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-02 10:00:00"（跳過 200, 201）
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-02 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		// 最遠完成 = 202（位置 2），解鎖到位置 3
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 201, 202, 203 均為解鎖狀態
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖' );
	}

	// ========== 後置（回應）- 含巢狀子章節時按平攤順序判斷 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 含巢狀子章節時按平攤順序判斷
	 * Example: 父子章節平攤後按 menu_order 判斷解鎖
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_父子章節平攤後按menu_order判斷解鎖(): void {
		// Given 課程有以下巢狀章節（平攤排序）：
		// chapterId 200 = 第一章（父，menu_order=1）
		// chapterId 204 = 1-1 子章節A（子，parent=200，menu_order=1）
		// chapterId 205 = 1-2 子章節B（子，parent=200，menu_order=2）
		// chapterId 201 = 第二章（父，menu_order=2）

		// 先建立新的課程（避免干擾其他測試）
		$nested_course_id = $this->create_course(
			[
				'post_title' => '巢狀章節測試課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $nested_course_id, 'enable_linear_viewing', 'yes' );
		$this->enroll_user_to_course( $this->alice_id, $nested_course_id, 0 );

		$parent_200 = $this->create_chapter( $nested_course_id, [ 'post_title' => '第一章', 'menu_order' => 1 ] );
		$child_204  = $this->create_chapter(
			$nested_course_id,
			[
				'post_title'  => '1-1 子章節A',
				'post_parent' => $parent_200,
				'menu_order'  => 1,
			]
		);
		$child_205  = $this->create_chapter(
			$nested_course_id,
			[
				'post_title'  => '1-2 子章節B',
				'post_parent' => $parent_200,
				'menu_order'  => 2,
			]
		);
		$parent_201 = $this->create_chapter( $nested_course_id, [ 'post_title' => '第二章', 'menu_order' => 2 ] );

		// And 用戶 "Alice" 在章節 204 的 finished_at 為 "2025-06-01 10:00:00"
		// 平攤順序: 200 → 204 → 205 → 201，最遠完成 = 204（位置 1），解鎖到位置 2
		$this->set_chapter_finished( $child_204, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢用戶 "Alice" 在巢狀課程的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $nested_course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );

		// And 章節 200, 204, 205 為解鎖狀態
		$this->assertContains( $parent_200, $state['unlocked_chapter_ids'], '父章節 200 應解鎖' );
		$this->assertContains( $child_204, $state['unlocked_chapter_ids'], '子章節 204 應解鎖' );
		$this->assertContains( $child_205, $state['unlocked_chapter_ids'], '子章節 205 應解鎖（最遠完成 204 + 1）' );

		// And 章節 201 為鎖定狀態
		$this->assertNotContains( $parent_201, $state['unlocked_chapter_ids'], '父章節 201 應鎖定' );
	}

	// ========== 後置（回應）- 取消完成的連鎖效果 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 取消最遠完成章節後重新計算解鎖邊界
	 * Example: 取消最遠完成章節後後續章節重新鎖定
	 *
	 * TODO: [事件風暴部位: Command + Query]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_取消最遠完成章節後後續章節重新鎖定(): void {
		// Given 用戶 "Alice" 在章節 200, 201, 202 均已完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-02 10:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-03 10:00:00' );
		// 取消 202 後，最遠完成 = 201（位置 1），解鎖到位置 2

		// When 用戶 "Alice" 取消章節 202 的完成狀態
		AVLChapterMeta::delete( $this->chapter_202_id, $this->alice_id, 'finished_at' );

		// And 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 章節 200, 201, 202 為解鎖狀態（200+201已完成，202=max_pos+1）
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖（max_pos+1）' );

		// And 章節 203 為鎖定狀態
		$this->assertNotContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 取消非最遠完成章節不影響解鎖邊界
	 * Example: 取消非最遠完成章節不影響解鎖邊界
	 *
	 * TODO: [事件風暴部位: Command + Query]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_取消非最遠完成章節不影響解鎖邊界(): void {
		// Given 用戶 "Alice" 在章節 200, 201, 202 均已完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-02 10:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-03 10:00:00' );
		// 取消 201（非最遠），最遠仍為 202（位置 2），解鎖到位置 3

		// When 用戶 "Alice" 取消章節 201 的完成狀態
		AVLChapterMeta::delete( $this->chapter_201_id, $this->alice_id, 'finished_at' );

		// And 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 章節 200, 201, 202, 203 均為解鎖狀態（最遠仍為 202，解鎖到位置 3）
		$this->assertContains( $this->chapter_200_id, $state['unlocked_chapter_ids'], '章節 200 應解鎖' );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '章節 201 應解鎖（最遠進度模式）' );
		$this->assertContains( $this->chapter_202_id, $state['unlocked_chapter_ids'], '章節 202 應解鎖' );
		$this->assertContains( $this->chapter_203_id, $state['unlocked_chapter_ids'], '章節 203 應解鎖（max_pos+1）' );
	}

	// ========== 後置（回應）- 鎖定提示資訊 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 鎖定章節附帶當前需完成章節的名稱與 ID
	 * Example: 鎖定章節提示當前應完成的章節名稱
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_鎖定章節提示當前應完成的章節名稱(): void {
		// Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		// 解鎖到位置 1（201），鎖定位置 2（202）及之後
		// 當前需完成 = 201「1-2 變數與型別」
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'locked_hints', $state );

		// And 章節 202 的鎖定提示應包含章節名稱「1-2 變數與型別」
		$this->assertArrayHasKey( $this->chapter_202_id, $state['locked_hints'], '章節 202 應有鎖定提示' );
		$this->assertStringContainsString(
			'1-2 變數與型別',
			$state['locked_hints'][ $this->chapter_202_id ]['message'],
			'章節 202 的提示應包含「1-2 變數與型別」'
		);

		// And 章節 203 的鎖定提示應包含章節名稱「1-2 變數與型別」
		$this->assertArrayHasKey( $this->chapter_203_id, $state['locked_hints'], '章節 203 應有鎖定提示' );
		$this->assertStringContainsString(
			'1-2 變數與型別',
			$state['locked_hints'][ $this->chapter_203_id ]['message'],
			'章節 203 的提示應包含「1-2 變數與型別」'
		);
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Rule: 後置（回應）- 鎖定章節附帶當前需完成章節的名稱與 ID
	 * Example: 無完成紀錄時鎖定提示指向第一章節
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::get_unlock_state()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_無完成紀錄時鎖定提示指向第一章節(): void {
		// Given 用戶 "Alice" 未完成任何章節

		// When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 操作成功
		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'locked_hints', $state );

		// And 章節 201 的鎖定提示應包含章節名稱「1-1 PHP 簡介」
		$this->assertArrayHasKey( $this->chapter_201_id, $state['locked_hints'], '章節 201 應有鎖定提示' );
		$this->assertStringContainsString(
			'1-1 PHP 簡介',
			$state['locked_hints'][ $this->chapter_201_id ]['message'],
			'章節 201 的提示應包含「1-1 PHP 簡介」'
		);
	}

	// ========== is_chapter_unlocked 輔助方法 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * 測試 is_chapter_unlocked 輔助方法
	 */
	public function test_is_chapter_unlocked_解鎖章節返回true(): void {
		// Given 無完成紀錄，僅第一章解鎖

		// When 查詢章節 200 是否已解鎖
		$result = LinearViewing::is_chapter_unlocked( $this->chapter_200_id, $this->course_id, $this->alice_id );

		// Then 應為 true
		$this->assertTrue( $result, '章節 200 應為已解鎖' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 測試 is_chapter_unlocked 輔助方法 - 鎖定章節
	 */
	public function test_is_chapter_unlocked_鎖定章節返回false(): void {
		// Given 無完成紀錄，章節 201 被鎖定

		// When 查詢章節 201 是否已解鎖
		$result = LinearViewing::is_chapter_unlocked( $this->chapter_201_id, $this->course_id, $this->alice_id );

		// Then 應為 false
		$this->assertFalse( $result, '章節 201 應為鎖定狀態' );
	}

	// ========== 線性觀看模式下 toggle-finish API 驗證 ==========
	// （來自 切換章節完成狀態.feature 的線性觀看相關 scenarios）

	/**
	 * @test
	 * @group linear-viewing
	 * @group toggle-api
	 * Rule: 前置（狀態）- 線性觀看模式下不可完成被鎖定的章節
	 * Example: 嘗試完成被鎖定的章節時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish + LinearViewing 前置驗證]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_線性觀看開啟嘗試完成鎖定章節應失敗(): void {
		// Given 課程 100 的 enable_linear_viewing 為 "yes"（已在 set_up 設定）
		// And 用戶 "Alice" 未完成任何章節
		// 僅章節 200 解鎖，章節 201 被鎖定

		// When 驗證章節 201 是否可以被完成（透過 LinearViewing 前置驗證）
		// 章節 201 在線性觀看模式下被鎖定，不應允許完成
		$is_enabled  = LinearViewing::is_enabled( $this->course_id );
		$is_unlocked = LinearViewing::is_chapter_unlocked( $this->chapter_201_id, $this->course_id, $this->alice_id );

		// Then 線性觀看應為啟用
		$this->assertTrue( $is_enabled, '線性觀看應為啟用' );

		// And 章節 201 應為鎖定（未解鎖）
		$this->assertFalse( $is_unlocked, '章節 201 應為鎖定，不允許完成' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * @group toggle-api
	 * Rule: 前置（狀態）- 線性觀看模式下不可完成被鎖定的章節
	 * Example: 線性觀看模式下完成已解鎖章節成功
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::is_chapter_unlocked()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_線性觀看開啟完成已解鎖章節應成功(): void {
		// Given 課程 100 的 enable_linear_viewing 為 "yes"（已在 set_up 設定）
		// And 用戶 "Alice" 未完成任何章節
		// 章節 200 為第一個章節，永遠解鎖

		// When 查詢章節 200 是否可完成
		$is_enabled  = LinearViewing::is_enabled( $this->course_id );
		$is_unlocked = LinearViewing::is_chapter_unlocked( $this->chapter_200_id, $this->course_id, $this->alice_id );

		// Then 線性觀看應為啟用
		$this->assertTrue( $is_enabled, '線性觀看應為啟用' );

		// And 章節 200 應為解鎖（允許完成）
		$this->assertTrue( $is_unlocked, '章節 200 應為解鎖，允許完成' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * @group toggle-api
	 * Rule: 前置（狀態）- 線性觀看模式下取消完成已完成章節成功（取消完成不受鎖定限制）
	 * Example: 線性觀看模式下取消完成已完成章節成功
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing 驗證]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_線性觀看開啟取消完成不受鎖定限制(): void {
		// Given 課程 100 的 enable_linear_viewing 為 "yes"（已在 set_up 設定）
		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		// 取消完成不受線性觀看限制（已完成的章節必然已解鎖）

		// When 查詢章節 200 是否已完成（即要進行「取消完成」操作）
		$finished_at = $this->get_chapter_meta( $this->chapter_200_id, $this->alice_id, 'finished_at' );

		// 章節 200 已完成，取消完成的前置條件（$is_this_chapter_finished = true）
		$this->assertNotEmpty( $finished_at, '章節 200 應已完成，才能進行取消完成' );

		// When 取消完成（不需要線性觀看驗證，因為 $is_this_chapter_finished = true）
		AVLChapterMeta::delete( $this->chapter_200_id, $this->alice_id, 'finished_at' );

		// Then 操作應成功，章節 200 的 finished_at 應為空
		$finished_at_after = $this->get_chapter_meta( $this->chapter_200_id, $this->alice_id, 'finished_at' );
		$this->assertEmpty( $finished_at_after, '取消完成後 finished_at 應為空' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * @group toggle-api
	 * Rule: 前置（狀態）- 線性觀看未開啟時不限制任何章節
	 * Example: 線性觀看未開啟時不限制任何章節
	 *
	 * TODO: [事件風暴部位: Query - LinearViewing::is_enabled()]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_線性觀看未開啟時不限制任何章節(): void {
		// Given 課程 100 的 enable_linear_viewing 為 "no"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// And 用戶 "Alice" 未完成任何章節

		// When 查詢章節 201（本來應被鎖定）是否可完成
		$is_enabled = LinearViewing::is_enabled( $this->course_id );

		// Then 線性觀看應為關閉
		$this->assertFalse( $is_enabled, '線性觀看應為關閉' );

		// And 關閉後，所有章節均可完成（不需要線性觀看驗證）
		// 透過 get_unlock_state 確認全部解鎖
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );
		$this->assertContains( $this->chapter_201_id, $state['unlocked_chapter_ids'], '線性觀看關閉時章節 201 應解鎖' );
	}

	// ========== 回應結構驗證 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * 驗證 get_unlock_state 回傳結構完整性
	 */
	public function test_回傳結構包含必要欄位(): void {
		// When 查詢解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then 回傳結構應包含必要欄位
		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'enabled', $state, '應包含 enabled 欄位' );
		$this->assertArrayHasKey( 'unlocked_chapter_ids', $state, '應包含 unlocked_chapter_ids 欄位' );
		$this->assertArrayHasKey( 'current_chapter_id', $state, '應包含 current_chapter_id 欄位' );
		$this->assertArrayHasKey( 'locked_hints', $state, '應包含 locked_hints 欄位' );
		$this->assertIsArray( $state['unlocked_chapter_ids'], 'unlocked_chapter_ids 應為陣列' );
		$this->assertIsArray( $state['locked_hints'], 'locked_hints 應為陣列' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 驗證 current_chapter_id 為第一個解鎖且未完成的章節
	 */
	public function test_current_chapter_id_為第一個解鎖且未完成的章節(): void {
		// Given 用戶 "Alice" 完成章節 200
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢解鎖狀態
		// 解鎖: [200, 201]，完成: [200]，第一個解鎖未完成 = 201
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then current_chapter_id 應為章節 201
		$this->assertSame(
			$this->chapter_201_id,
			$state['current_chapter_id'],
			'current_chapter_id 應為第一個解鎖且未完成的章節（201）'
		);
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 全部完成時 current_chapter_id 應為 null
	 */
	public function test_全部完成時current_chapter_id為null(): void {
		// Given 全部章節完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-02 10:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-03 10:00:00' );
		$this->set_chapter_finished( $this->chapter_203_id, $this->alice_id, '2025-06-04 10:00:00' );

		// When 查詢解鎖狀態
		$state = LinearViewing::get_unlock_state( $this->course_id, $this->alice_id );

		// Then current_chapter_id 應為 null（所有章節已完成）
		$this->assertNull( $state['current_chapter_id'], '全部完成時 current_chapter_id 應為 null' );
	}
}
