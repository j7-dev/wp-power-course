<?php
/**
 * 線性觀看（Sequential Chapter Viewing）整合測試
 * Feature: specs/features/progress/線性觀看.feature
 *
 * 測試 ChapterUtils::is_chapter_locked() 方法的核心邏輯：
 * - 線性觀看關閉時，任何章節不鎖定
 * - 線性觀看開啟，第一個章節不鎖定
 * - 前一章節未完成時，後一章節被鎖定
 * - 前一章節已完成時，後一章節解鎖
 * - 管理員豁免（manage_woocommerce）
 * - 講師豁免（teacher_ids）
 *
 * @group progress
 * @group linear-view
 */

declare( strict_types=1 );

namespace Tests\Integration\Progress;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearViewTest
 * 測試線性觀看鎖定邏輯
 */
class LinearViewTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/**
	 * 章節 IDs（展開排序）
	 * 排序：[第一章(200), 1-1(201), 1-2(202), 第二章(203), 2-1(204)]
	 * 本測試使用索引對應順序
	 *
	 * @var int[] 索引 0=第一章, 1=1-1, 2=1-2, 3=第二章, 4=2-1
	 */
	private array $chapter_ids;

	/** @var int Alice 用戶 ID（普通學員） */
	private int $alice_id;

	/** @var int Bob 用戶 ID（普通學員） */
	private int $bob_id;

	/** @var int Admin 用戶 ID（管理員） */
	private int $admin_id;

	/** @var int Teacher 用戶 ID（講師） */
	private int $teacher_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 ChapterUtils 和 AVLChapterMeta
	}

	/**
	 * 每個測試前建立測試資料
	 * 課程架構：
	 *   第一章 (menu_order=10, depth=0)
	 *     1-1 (menu_order=10, depth=1, parent=第一章)
	 *     1-2 (menu_order=20, depth=1, parent=第一章)
	 *   第二章 (menu_order=20, depth=0)
	 *     2-1 (menu_order=10, depth=1, parent=第二章)
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立測試課程（預設 enable_linear_mode = 'no'）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課（線性觀看測試）',
				'_is_course' => 'yes',
			]
		);

		// 建立章節架構（按照 get_flatten_post_ids 的排序邏輯）
		// 頂層章節（透過 parent_course_id meta 關聯）
		$ch_first = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '第一章',
				'menu_order' => 10,
			]
		);
		$ch_1_1   = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 10,
				'post_parent' => $ch_first,
			]
		);
		$ch_1_2   = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 20,
				'post_parent' => $ch_first,
			]
		);
		$ch_second = $this->create_chapter(
			$this->course_id,
			[
				'post_title' => '第二章',
				'menu_order' => 20,
			]
		);
		$ch_2_1   = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'menu_order'  => 10,
				'post_parent' => $ch_second,
			]
		);

		// 展開排序：[第一章, 1-1, 1-2, 第二章, 2-1]
		$this->chapter_ids = [ $ch_first, $ch_1_1, $ch_1_2, $ch_second, $ch_2_1 ];

		// 建立 Alice（普通學員）
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_linear_' . uniqid(),
				'user_email' => 'alice_linear_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Alice'] = $this->alice_id;
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );

		// 建立 Bob（普通學員）
		$this->bob_id = $this->factory()->user->create(
			[
				'user_login' => 'bob_linear_' . uniqid(),
				'user_email' => 'bob_linear_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Bob'] = $this->bob_id;
		$this->enroll_user_to_course( $this->bob_id, $this->course_id, 0 );

		// Admin 用戶（使用 WP 預設 admin，ID=1）
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_linear_' . uniqid(),
				'user_email' => 'admin_linear_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$user = new \WP_User( $this->admin_id );
		$user->add_cap( 'manage_woocommerce' );
		$this->ids['Admin'] = $this->admin_id;

		// Teacher 用戶
		$this->teacher_id = $this->factory()->user->create(
			[
				'user_login' => 'teacher_linear_' . uniqid(),
				'user_email' => 'teacher_linear_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Teacher'] = $this->teacher_id;
		// 設定為課程講師
		update_post_meta( $this->course_id, 'teacher_ids', [ $this->teacher_id ] );
	}

	/**
	 * 清除快取
	 */
	public function tear_down(): void {
		// 清除 flatten_post_ids 快取
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		parent::tear_down();
	}

	// ========== Rule: 線性觀看關閉時，所有章節均可自由存取 ==========

	/**
	 * @test
	 * @group linear-view
	 * 線性觀看關閉時，任何章節不鎖定
	 * Rule: 線性觀看關閉時，所有章節均可自由存取
	 */
	public function test_線性觀看關閉時任何章節不鎖定(): void {
		// Given 課程 enable_linear_mode 為 'no'（預設）
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// And Alice 未完成任何章節

		// When 檢查章節 2-1（最後一個）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->alice_id );

		// Then 章節應為可存取（不鎖定）
		$this->assertFalse( $is_locked, '線性觀看關閉時，章節不應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 線性觀看關閉時，即使沒有完成任何章節也可存取
	 */
	public function test_線性觀看關閉時中間章節不鎖定(): void {
		// Given 課程 enable_linear_mode 為 'no'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// When 檢查 1-1（索引1）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[1], $this->alice_id );

		// Then 不鎖定
		$this->assertFalse( $is_locked, '線性觀看關閉時，任何章節都不應被鎖定' );
	}

	// ========== Rule: 線性觀看開啟時，第一個章節永遠可存取 ==========

	/**
	 * @test
	 * @group linear-view
	 * 線性觀看開啟時，展開排序中的第一個章節永遠可存取
	 * Rule: 線性觀看開啟時，展開排序中的第一個章節永遠可存取
	 */
	public function test_線性觀看開啟時第一章節不鎖定(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 未完成任何章節

		// When 檢查第一章（索引0）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[0], $this->alice_id );

		// Then 第一章不應被鎖定
		$this->assertFalse( $is_locked, '線性觀看開啟時，第一個章節永遠不應被鎖定' );
	}

	// ========== Rule: 前一章節未完成時，後續章節被鎖定 ==========

	/**
	 * @test
	 * @group linear-view
	 * 第一章未完成時，1-1 被鎖定
	 * Rule: 前一章節未完成時，後續章節被鎖定
	 */
	public function test_前一章節未完成時後一章節被鎖定(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 未完成任何章節

		// When 檢查 1-1（索引1）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[1], $this->alice_id );

		// Then 1-1 應被鎖定（因為第一章未完成）
		$this->assertTrue( $is_locked, '前一章節未完成時，後一章節應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 第一章未完成時，2-1 被鎖定
	 */
	public function test_線性觀看開啟後期章節全部鎖定(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 未完成任何章節

		// When 檢查 2-1（索引4）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->alice_id );

		// Then 2-1 應被鎖定
		$this->assertTrue( $is_locked, '線性觀看開啟且前面章節未完成時，後期章節應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 依序完成後下一章節解鎖
	 * Rule: 前一章節已完成時，下一章節解鎖
	 */
	public function test_完成前一章節後下一章節解鎖(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 完成第一章
		$this->set_chapter_finished( $this->chapter_ids[0], $this->alice_id, '2025-06-01 10:00:00' );

		// When 檢查 1-1（索引1）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[1], $this->alice_id );

		// Then 1-1 應解鎖（因為第一章已完成）
		$this->assertFalse( $is_locked, '前一章節完成後，下一章節應解鎖' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 跳過中間章節仍然被鎖定
	 */
	public function test_跳過中間章節仍被鎖定(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 完成第一章（索引0）但未完成 1-1（索引1）
		$this->set_chapter_finished( $this->chapter_ids[0], $this->alice_id, '2025-06-01 10:00:00' );

		// When 檢查 1-2（索引2）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[2], $this->alice_id );

		// Then 1-2 應被鎖定（因為 1-1 未完成）
		$this->assertTrue( $is_locked, '跳過中間章節時，後面章節仍應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 所有前面章節完成後最後一個章節可存取
	 */
	public function test_完成所有前面章節後最後章節解鎖(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 依序完成所有前面章節
		$this->set_chapter_finished( $this->chapter_ids[0], $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids[1], $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_ids[2], $this->alice_id, '2025-06-01 12:00:00' );
		$this->set_chapter_finished( $this->chapter_ids[3], $this->alice_id, '2025-06-01 13:00:00' );

		// When 檢查 2-1（索引4）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->alice_id );

		// Then 2-1 應解鎖
		$this->assertFalse( $is_locked, '所有前面章節完成後，最後一個章節應解鎖' );
	}

	// ========== Rule: 管理員/講師豁免 ==========

	/**
	 * @test
	 * @group linear-view
	 * 管理員可存取任意鎖定章節（manage_woocommerce 豁免）
	 * Rule: 管理員（manage_woocommerce）不受線性觀看限制
	 */
	public function test_管理員不受線性觀看限制(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Admin 未完成任何章節

		// When 以 Admin 身份檢查 2-1（索引4）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->admin_id );

		// Then 章節應為可存取（管理員豁免）
		$this->assertFalse( $is_locked, '管理員不應受線性觀看限制' );
	}

	/**
	 * @test
	 * @group linear-view
	 * 講師可存取任意鎖定章節（teacher_ids 豁免）
	 * Rule: 講師（teacher_ids）不受線性觀看限制
	 */
	public function test_講師不受線性觀看限制(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Teacher 是課程講師（已在 set_up 中設定）
		// And Teacher 未完成任何章節

		// When 以 Teacher 身份檢查 2-1（索引4）的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->teacher_id );

		// Then 章節應為可存取（講師豁免）
		$this->assertFalse( $is_locked, '講師不應受線性觀看限制' );
	}

	// ========== Rule: 不同學員的進度獨立 ==========

	/**
	 * @test
	 * @group linear-view
	 * Alice 的進度不影響 Bob
	 * Rule: 不同學員的進度獨立，解鎖狀態互不影響
	 */
	public function test_不同學員進度互不影響(): void {
		// Given 課程 enable_linear_mode 為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 完成第一章
		$this->set_chapter_finished( $this->chapter_ids[0], $this->alice_id, '2025-06-01 10:00:00' );

		// And Bob 未完成任何章節

		// When 檢查 Alice 的 1-1 鎖定狀態
		$alice_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[1], $this->alice_id );

		// Then Alice 的 1-1 應解鎖
		$this->assertFalse( $alice_locked, 'Alice 完成第一章後，1-1 應解鎖' );

		// When 檢查 Bob 的 1-1 鎖定狀態
		$bob_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[1], $this->bob_id );

		// Then Bob 的 1-1 仍應鎖定
		$this->assertTrue( $bob_locked, "Bob 未完成第一章，1-1 仍應鎖定（Alice 進度不影響 Bob）" );
	}

	// ========== Rule: 關閉線性觀看後所有章節解鎖 ==========

	/**
	 * @test
	 * @group linear-view
	 * 關閉線性觀看後，所有章節恢復自由存取
	 * Rule: 關閉線性觀看後，所有章節恢復自由存取
	 */
	public function test_關閉線性觀看後所有章節可存取(): void {
		// Given 課程 enable_linear_mode 初始為 'yes'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// And Alice 未完成任何章節
		// 確認 2-1 一開始是鎖定的
		$is_locked_before = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->alice_id );
		$this->assertTrue( $is_locked_before, '開啟線性觀看後 2-1 應被鎖定（前置確認）' );

		// When 關閉線性觀看
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// Then 2-1 應解鎖
		$is_locked_after = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->alice_id );
		$this->assertFalse( $is_locked_after, '關閉線性觀看後，所有章節應可存取' );
	}

	// ========== 邊界情境 ==========

	/**
	 * @test
	 * @group linear-view
	 * @group edge
	 * 課程無章節時，線性觀看開關不影響任何行為
	 */
	public function test_空課程開啟線性觀看不報錯(): void {
		// Given 空白課程（無章節）
		$empty_course_id = $this->create_course(
			[
				'post_title' => '空白課程',
				'_is_course' => 'yes',
			]
		);

		// When 開啟線性觀看
		update_post_meta( $empty_course_id, 'enable_linear_mode', 'yes' );

		// Then 不應拋出例外
		$exception_thrown = false;
		try {
			// 嘗試對不存在的章節 ID 進行檢查（模擬空課程）
			// get_flatten_post_ids 應回傳空陣列
			$flatten_ids = ChapterUtils::get_flatten_post_ids( $empty_course_id );
			$this->assertIsArray( $flatten_ids, 'get_flatten_post_ids 應回傳陣列' );
			$this->assertEmpty( $flatten_ids, '空課程的章節列表應為空' );
		} catch ( \Throwable $e ) {
			$exception_thrown = true;
			$this->fail( '空課程開啟線性觀看時不應拋出例外：' . $e->getMessage() );
		}

		$this->assertFalse( $exception_thrown, '設定儲存成功' );
	}

	/**
	 * @test
	 * @group linear-view
	 * @group edge
	 * 非連續完成後開啟線性觀看，中斷處之後鎖定
	 */
	public function test_非連續完成後開啟線性觀看中斷處之後鎖定(): void {
		// Given 課程 enable_linear_mode 初始為 'no'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// And Bob 完成第一章（索引0）但未完成 1-1（索引1），跳過完成了 2-1（索引4）
		$this->set_chapter_finished( $this->chapter_ids[0], $this->bob_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids[4], $this->bob_id, '2025-06-01 12:00:00' );

		// When 開啟線性觀看
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// Then 1-2（索引2）應被鎖定（因為 1-1 未完成）
		$is_1_2_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[2], $this->bob_id );
		$this->assertTrue( $is_1_2_locked, '1-2 應被鎖定（1-1 未完成）' );

		// And 2-1（索引4）應被鎖定（因為 1-2 未完成）
		$is_2_1_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[4], $this->bob_id );
		$this->assertTrue( $is_2_1_locked, '2-1 應被鎖定（前面有未完成章節）' );
	}

	/**
	 * @test
	 * @group linear-view
	 * @group edge
	 * 已有完成紀錄開啟線性觀看後，根據現有進度解鎖
	 */
	public function test_已有完成紀錄開啟線性觀看後根據現有進度解鎖(): void {
		// Given 課程 enable_linear_mode 為 'no'
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// And Alice 已依序完成前兩個章節（索引0, 1）
		$this->set_chapter_finished( $this->chapter_ids[0], $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids[1], $this->alice_id, '2025-06-01 11:00:00' );

		// When 開啟線性觀看
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );

		// Then 1-2（索引2）應可存取（因為 1-1 已完成）
		$is_locked = ChapterUtils::is_chapter_locked( $this->course_id, $this->chapter_ids[2], $this->alice_id );
		$this->assertFalse( $is_locked, '已完成前面章節時，下一章節應可存取' );
	}
}
