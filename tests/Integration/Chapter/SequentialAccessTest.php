<?php
/**
 * 章節線性存取驗證 整合測試
 * Feature: specs/features/progress/章節線性存取驗證.feature
 *
 * @group chapter
 * @group sequential
 */

declare( strict_types=1 );

namespace Tests\Integration\Chapter;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class SequentialAccessTest
 * 測試章節線性存取驗證邏輯
 *
 * 覆蓋 Feature: 章節線性存取驗證
 * 解鎖規則：章節 N 可存取 ⟺ 序列中 N 之前的所有章節皆已完成，或 N 是序列中的第一個章節。
 */
class SequentialAccessTest extends TestCase {

	/** @var int 測試課程 ID（多層章節：Python 入門） */
	private int $course_id;

	/** @var int 第一章節 ID（depth=0） */
	private int $ch_300;

	/** @var int 1-1 章節 ID（depth=1，parent=300） */
	private int $ch_301;

	/** @var int 1-2 章節 ID（depth=1，parent=300） */
	private int $ch_302;

	/** @var int 第二章節 ID（depth=0） */
	private int $ch_400;

	/** @var int 2-1 章節 ID（depth=1，parent=400） */
	private int $ch_401;

	/** @var int 2-2 章節 ID（depth=1，parent=400） */
	private int $ch_402;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 使用 ChapterUtils 靜態方法，無需額外依賴
	}

	/**
	 * 每個測試前建立測試資料
	 * 建立多層章節結構（DFS 前序排列）：
	 * 第一章(300) → 1-1(301) → 1-2(302) → 第二章(400) → 2-1(401) → 2-2(402)
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立課程（is_sequential = yes）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'Python 入門',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'is_sequential', 'yes' );

		// 建立多層章節結構
		// depth=0 的章節直接 parent 為 course_id
		$this->ch_300 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 1,
			]
		);

		$this->ch_301 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'post_parent' => $this->ch_300,
				'menu_order'  => 1,
			]
		);

		$this->ch_302 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'post_parent' => $this->ch_300,
				'menu_order'  => 2,
			]
		);

		$this->ch_400 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 2,
			]
		);

		$this->ch_401 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'post_parent' => $this->ch_400,
				'menu_order'  => 1,
			]
		);

		$this->ch_402 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-2',
				'post_parent' => $this->ch_400,
				'menu_order'  => 2,
			]
		);

		// 建立 Alice 用戶並加入課程
		$this->alice_id     = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	// ========== Rule: 第一個章節永遠可存取 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 第一個章節永遠可存取（不受線性觀看限制）
	 * Example: 無任何完成紀錄時可存取第一章
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_無任何完成紀錄時可存取第一章(): void {
		// Given 用戶 "Alice" 無任何章節完成紀錄

		// When 檢查用戶 "Alice" 是否可存取課程 100 的章節 300
		// ChapterUtils::is_chapter_locked($ch_300, $course_id, $alice_id)

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 第一個章節永遠可存取（不受線性觀看限制）
	 * Example: 第一個章節永遠不會被鎖定
	 *
	 * TODO: [事件風暴部位: Query - get_sequential_access_map]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_第一個章節永遠不會被鎖定(): void {
		// Given 用戶 "Alice" 無任何章節完成紀錄

		// When 查詢用戶 "Alice" 在課程的章節存取清單
		// ChapterUtils::get_sequential_access_map($course_id, $alice_id)

		// Then 章節 300 的 is_locked 應為 false

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::get_sequential_access_map() 方法');
	}

	// ========== Rule: 已完成前一個章節後，下一個章節可存取 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 已完成前一個章節後，下一個章節可存取
	 * Example: 完成第一章後可存取 1-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_完成第一章後可存取1_1(): void {
		// Given 用戶 "Alice" 已完成章節 [300]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 301

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 已完成前一個章節後，下一個章節可存取
	 * Example: 完成第一章和 1-1 後可存取 1-2
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_完成第一章和1_1後可存取1_2(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 302

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 已完成前一個章節後，下一個章節可存取
	 * Example: 完成 1-2 後可跨父章節存取第二章
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_完成1_2後可跨父章節存取第二章(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301, 302]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 400

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 已完成前一個章節後，下一個章節可存取
	 * Example: 完成第二章後可存取 2-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_完成第二章後可存取2_1(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );
		$this->set_chapter_finished( $this->ch_400, $this->alice_id, '2025-06-01 10:15:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 401

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	// ========== Rule: 未完成前一個章節時，後續章節不可存取 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 未完成前一個章節時，後續章節不可存取
	 * Example: 未完成第一章時不可存取 1-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_未完成第一章時不可存取1_1(): void {
		// Given 用戶 "Alice" 無任何章節完成紀錄

		// When 檢查用戶 "Alice" 是否可存取課程的章節 301

		// Then 結果為不可存取（is_locked = true）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 未完成前一個章節時，後續章節不可存取
	 * Example: 完成第一章但未完成 1-1 時不可存取 1-2
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_完成第一章但未完成1_1時不可存取1_2(): void {
		// Given 用戶 "Alice" 已完成章節 [300]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 302

		// Then 結果為不可存取（is_locked = true）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 未完成前一個章節時，後續章節不可存取
	 * Example: 未完成 1-2 時不可跨區段存取第二章
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_未完成1_2時不可跨區段存取第二章(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 400

		// Then 結果為不可存取（is_locked = true）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 未完成前一個章節時，後續章節不可存取
	 * Example: 不可跳過中間章節存取最後章節
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked]
	 */
	public function test_不可跳過中間章節存取最後章節(): void {
		// Given 用戶 "Alice" 已完成章節 [300]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );

		// When 檢查用戶 "Alice" 是否可存取課程的章節 402（最後章節）

		// Then 結果為不可存取（is_locked = true）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	// ========== Rule: 非連續完成紀錄的鎖定計算 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 開啟線性觀看後，即使有非連續完成紀錄，仍以第一個未完成章節為分界鎖定
	 * Example: 跳過 1-1 完成了 1-2 和第二章，開啟線性觀看後只能看到第一章和 1-1
	 *
	 * TODO: [事件風暴部位: Query - get_sequential_access_map]
	 */
	public function test_非連續完成紀錄以第一個未完成章節為分界鎖定(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 302, 400]（跳過 301）
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );
		$this->set_chapter_finished( $this->ch_400, $this->alice_id, '2025-06-01 10:15:00' );

		// When 查詢用戶 "Alice" 在課程的章節存取清單
		// ChapterUtils::get_sequential_access_map($course_id, $alice_id)

		// Then 章節 300 的 is_locked 應為 false
		// And 章節 301 的 is_locked 應為 false（第一個未完成，本身不鎖定，下一個才鎖）
		// And 章節 302 的 is_locked 應為 true
		// And 章節 400 的 is_locked 應為 true
		// And 章節 401 的 is_locked 應為 true
		// And 章節 402 的 is_locked 應為 true

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::get_sequential_access_map() 方法');
	}

	// ========== Rule: 取消完成的連鎖鎖定 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 取消完成某章節後，該章節之後所有章節根據新的完成狀態重新計算鎖定
	 * Example: 取消完成第一章後 1-1 及之後章節全部鎖定
	 *
	 * TODO: [事件風暴部位: Command - 取消完成 + Query - is_chapter_locked]
	 */
	public function test_取消完成第一章後1_1及之後章節全部鎖定(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301, 302]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );

		// When 用戶 "Alice" 取消完成章節 300
		AVLChapterMeta::delete( $this->ch_300, $this->alice_id, 'finished_at' );

		// Then 章節 300 對用戶 "Alice" 應為可存取（第一章永遠不鎖）
		// And 章節 301 對用戶 "Alice" 應為不可存取
		// And 章節 302 對用戶 "Alice" 應為不可存取
		// And 章節 400 對用戶 "Alice" 應為不可存取

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 取消完成某章節後，該章節之後所有章節根據新的完成狀態重新計算鎖定
	 * Example: 取消完成中間章節後僅影響其後方
	 *
	 * TODO: [事件風暴部位: Command - 取消完成 + Query - is_chapter_locked]
	 */
	public function test_取消完成中間章節後僅影響其後方(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400, 401]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );
		$this->set_chapter_finished( $this->ch_400, $this->alice_id, '2025-06-01 10:15:00' );
		$this->set_chapter_finished( $this->ch_401, $this->alice_id, '2025-06-01 10:20:00' );

		// When 用戶 "Alice" 取消完成章節 301
		AVLChapterMeta::delete( $this->ch_301, $this->alice_id, 'finished_at' );

		// Then 章節 300 對用戶 "Alice" 應為可存取
		// And 章節 301 對用戶 "Alice" 應為可存取（第二個，前一個 300 已完成，但 301 本身未完成）
		// And 章節 302 對用戶 "Alice" 應為不可存取
		// And 章節 400 對用戶 "Alice" 應為不可存取
		// And 章節 401 對用戶 "Alice" 應為不可存取

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	// ========== Rule: is_sequential 為 false 時所有章節可自由存取 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: is_sequential 為 false 時所有章節可自由存取
	 * Example: 未啟用線性觀看時可存取任何章節
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_locked（is_sequential=false）]
	 */
	public function test_未啟用線性觀看時可存取任何章節(): void {
		// Given 課程的 is_sequential 為 false
		update_post_meta( $this->course_id, 'is_sequential', 'no' );

		// And 用戶 "Alice" 無任何章節完成紀錄

		// When 檢查用戶 "Alice" 是否可存取課程的章節 402（最後章節）

		// Then 結果為可存取（is_locked = false）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: is_sequential 為 false 時所有章節可自由存取
	 * Example: 關閉線性觀看後所有章節立即恢復自由存取
	 *
	 * TODO: [事件風暴部位: Command - 關閉 is_sequential + Query - is_chapter_locked]
	 */
	public function test_關閉線性觀看後所有章節立即恢復自由存取(): void {
		// Given 課程的 is_sequential 為 true
		// And 用戶 "Alice" 已完成章節 [300]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		// And 章節 302 對用戶 "Alice" 為不可存取（此時 is_sequential=yes）

		// When 管理員將課程的 is_sequential 設為 false
		update_post_meta( $this->course_id, 'is_sequential', 'no' );

		// Then 章節 302 對用戶 "Alice" 應為可存取
		// And 用戶 "Alice" 的完成紀錄不受影響（300 的 finished_at 仍存在）

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::is_chapter_locked() 方法');
	}

	// ========== Rule: 單層章節支援 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 無子章節的單層課程也支援線性觀看，依 menu_order 排序
	 * Example: 單層課程的線性觀看
	 *
	 * TODO: [事件風暴部位: Query - get_sequential_access_map（單層）]
	 */
	public function test_單層課程的線性觀看(): void {
		// Given 系統中有以下課程（單層）
		$course_200 = $this->create_course(
			[
				'post_title' => 'JS 基礎',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $course_200, 'is_sequential', 'yes' );

		// And 課程 200 有以下章節結構（單層，依 menu_order）
		$ch_500 = $this->create_chapter(
			$course_200,
			[
				'post_title'  => '第一課',
				'post_parent' => $course_200,
				'menu_order'  => 1,
			]
		);
		$ch_501 = $this->create_chapter(
			$course_200,
			[
				'post_title'  => '第二課',
				'post_parent' => $course_200,
				'menu_order'  => 2,
			]
		);
		$ch_502 = $this->create_chapter(
			$course_200,
			[
				'post_title'  => '第三課',
				'post_parent' => $course_200,
				'menu_order'  => 3,
			]
		);

		// And 用戶 "Alice" 已被加入課程 200
		$this->enroll_user_to_course( $this->alice_id, $course_200, 0 );

		// And 用戶 "Alice" 已完成章節 [500]
		$this->set_chapter_finished( $ch_500, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢用戶 "Alice" 在課程 200 的章節存取清單

		// Then 章節 500 的 is_locked 應為 false
		// And 章節 501 的 is_locked 應為 false
		// And 章節 502 的 is_locked 應為 true

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::get_sequential_access_map() 方法');
	}

	// ========== Rule: 完成所有章節後全部可存取 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 所有章節完成後全部可存取
	 * Example: 完成所有章節後全部解鎖
	 *
	 * TODO: [事件風暴部位: Query - get_sequential_access_map（全部完成）]
	 */
	public function test_完成所有章節後全部解鎖(): void {
		// Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400, 401, 402]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_301, $this->alice_id, '2025-06-01 10:05:00' );
		$this->set_chapter_finished( $this->ch_302, $this->alice_id, '2025-06-01 10:10:00' );
		$this->set_chapter_finished( $this->ch_400, $this->alice_id, '2025-06-01 10:15:00' );
		$this->set_chapter_finished( $this->ch_401, $this->alice_id, '2025-06-01 10:20:00' );
		$this->set_chapter_finished( $this->ch_402, $this->alice_id, '2025-06-01 10:25:00' );

		// When 查詢用戶 "Alice" 在課程的章節存取清單

		// Then 所有章節的 is_locked 應為 false

		$this->markTestIncomplete('尚未實作：需要 ChapterUtils::get_sequential_access_map() 方法');
	}

	// ========== Rule: API 存取保護 ==========

	/**
	 * @test
	 * @group sequential
	 * Feature: 章節線性存取驗證
	 * Rule: 後端 API 拒絕對鎖定章節的操作，防止繞過前端限制
	 * Example: 嘗試透過 API 完成鎖定章節時返回錯誤
	 *
	 * TODO: [事件風暴部位: Command - toggle-finish（鎖定驗證）]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_嘗試透過API完成鎖定章節時返回錯誤(): void {
		// Given 用戶 "Alice" 已完成章節 [300]
		$this->set_chapter_finished( $this->ch_300, $this->alice_id, '2025-06-01 10:00:00' );

		// When 用戶 "Alice" 切換章節 302 的完成狀態（302 被鎖定：301 未完成）

		// Then 操作失敗，錯誤為「請先完成前面的章節」

		$this->markTestIncomplete('尚未實作：需要 Toggle Finish API 的鎖定驗證');
	}
}
