<?php
/**
 * 線性觀看章節鎖定 整合測試
 * Feature: specs/features/chapter/線性觀看章節鎖定.feature
 *
 * @group chapter
 * @group linear-mode
 */

declare( strict_types=1 );

namespace Tests\Integration\Chapter;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearModeChapterLockTest
 * 測試課程章節線性觀看鎖定邏輯
 */
class LinearModeChapterLockTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int 管理員 ID */
	private int $admin_id;

	/** @var int Alice（學員）ID */
	private int $alice_id;

	/** @var int Teacher（講師）ID */
	private int $teacher_id;

	/** @var int 第一章 ID */
	private int $ch200;

	/** @var int 1-1 章節 ID */
	private int $ch201;

	/** @var int 1-2 章節 ID */
	private int $ch202;

	/** @var int 第二章 ID */
	private int $ch203;

	/** @var int 2-1 章節 ID */
	private int $ch204;

	/**
	 * 初始化依賴（無需額外 service）
	 */
	protected function configure_dependencies(): void {
		// 無需額外 service
	}

	/**
	 * 每個測試前建立 Background 資料
	 *
	 * 課程結構：
	 *   第一章 (menu_order=10)
	 *     1-1 (post_parent=第一章, menu_order=10)
	 *     1-2 (post_parent=第一章, menu_order=20)
	 *   第二章 (menu_order=20)
	 *     2-1 (post_parent=第二章, menu_order=10)
	 *
	 * DFS 展開順序：第一章 → 1-1 → 1-2 → 第二章 → 2-1
	 */
	public function set_up(): void {
		parent::set_up();

		// Background: 系統中有以下用戶
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;

		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		$this->teacher_id = $this->factory()->user->create(
			[
				'user_login' => 'teacher_' . uniqid(),
				'user_email' => 'teacher_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Teacher'] = $this->teacher_id;

		// Background: 課程 100（enable_linear_mode = yes）
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );
		add_post_meta( $this->course_id, 'teacher_ids', $this->teacher_id );

		// 頂層章節：第一章（menu_order=10）
		$this->ch200 = $this->factory()->post->create(
			[
				'post_title'  => '第一章',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => 0,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $this->ch200, 'parent_course_id', $this->course_id );

		// 子章節：1-1（post_parent=第一章, menu_order=10）
		$this->ch201 = $this->factory()->post->create(
			[
				'post_title'  => '1-1',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => $this->ch200,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $this->ch201, 'parent_course_id', $this->course_id );

		// 子章節：1-2（post_parent=第一章, menu_order=20）
		$this->ch202 = $this->factory()->post->create(
			[
				'post_title'  => '1-2',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => $this->ch200,
				'menu_order'  => 20,
			]
		);
		update_post_meta( $this->ch202, 'parent_course_id', $this->course_id );

		// 頂層章節：第二章（menu_order=20）
		$this->ch203 = $this->factory()->post->create(
			[
				'post_title'  => '第二章',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => 0,
				'menu_order'  => 20,
			]
		);
		update_post_meta( $this->ch203, 'parent_course_id', $this->course_id );

		// 子章節：2-1（post_parent=第二章, menu_order=10）
		$this->ch204 = $this->factory()->post->create(
			[
				'post_title'  => '2-1',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => $this->ch203,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $this->ch204, 'parent_course_id', $this->course_id );

		// Alice 已被加入課程
		$this->enroll_user_to_course( $this->alice_id, $this->course_id );
	}

	/**
	 * 每個測試後清除 wp_cache（避免快取殘留影響其他測試）
	 */
	public function tear_down(): void {
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		wp_cache_flush_group( 'prev_next' );
		parent::tear_down();
	}

	// ========== Rule: 線性觀看序列 ==========

	/**
	 * @test
	 * @group happy
	 * Rule: DFS 展開排序
	 * Example: 第一個章節（第一章）永遠可觀看
	 */
	public function test_第一個章節永遠可觀看(): void {
		// When 用戶 Alice 查詢第一章的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $this->ch200, $this->alice_id );

		// Then 第一章不被鎖定
		$this->assertFalse( $is_locked, '第一個章節應永遠為 unlocked' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: DFS 展開排序
	 * Example: 未完成第一個章節時，其餘章節全部鎖定
	 */
	public function test_未完成第一個章節時其餘章節全部鎖定(): void {
		// Given Alice 在章節 200 無 finished_at（預設未完成）

		// When Alice 查詢各章節鎖定狀態
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->alice_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->alice_id );
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->alice_id );
		$locked_204 = ChapterUtils::is_chapter_locked( $this->ch204, $this->alice_id );

		// Then 201/202/203/204 全部鎖定
		$this->assertTrue( $locked_201, '1-1 應被鎖定' );
		$this->assertTrue( $locked_202, '1-2 應被鎖定' );
		$this->assertTrue( $locked_203, '第二章應被鎖定' );
		$this->assertTrue( $locked_204, '2-1 應被鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: DFS 展開排序
	 * Example: 完成第一章後，1-1 解鎖
	 */
	public function test_完成第一章後下一個子章解鎖(): void {
		// Given Alice 完成了第一章
		$this->set_chapter_finished( $this->ch200, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢 1-1 及 1-2 的鎖定狀態
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->alice_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->alice_id );

		// Then 1-1 解鎖，1-2 仍鎖定
		$this->assertFalse( $locked_201, '完成第一章後 1-1 應解鎖' );
		$this->assertTrue( $locked_202, '尚未完成 1-1，1-2 應仍鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: DFS 展開排序
	 * Example: 依序完成到 1-2 後，第二章解鎖
	 */
	public function test_依序完成到子章後下一個頂層章解鎖(): void {
		// Given Alice 依序完成了 第一章、1-1、1-2
		$this->set_chapter_finished( $this->ch200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch201, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch202, $this->alice_id, '2025-06-01 12:00:00' );

		// When 查詢 第二章 及 2-1 的鎖定狀態
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->alice_id );
		$locked_204 = ChapterUtils::is_chapter_locked( $this->ch204, $this->alice_id );

		// Then 第二章解鎖，2-1 仍鎖定
		$this->assertFalse( $locked_203, '完成 1-2 後第二章應解鎖' );
		$this->assertTrue( $locked_204, '尚未完成第二章，2-1 應仍鎖定' );
	}

	// ========== Rule: 取消完成後重新鎖定 ==========

	/**
	 * @test
	 * @group happy
	 * Rule: 取消前面章節的完成狀態後，後續章節重新鎖定
	 * Example: 取消 1-1 完成後，1-2 及後續章節重新鎖定
	 */
	public function test_取消完成後後續章節重新鎖定(): void {
		// Given Alice 已依序完成 第一章、1-1、1-2
		$this->set_chapter_finished( $this->ch200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch201, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch202, $this->alice_id, '2025-06-01 12:00:00' );

		// When 取消 1-1 的完成狀態
		AVLChapterMeta::delete( $this->ch201, $this->alice_id, 'finished_at' );

		// 清除快取以確保重新計算
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );

		// Then 1-1 仍解鎖（前置章節第一章已完成），但 1-2 及後續重新鎖定
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->alice_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->alice_id );
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->alice_id );

		$this->assertFalse( $locked_201, '1-1 的前置章節（第一章）已完成，1-1 應解鎖' );
		$this->assertTrue( $locked_202, '取消 1-1 完成後，1-2 應重新鎖定' );
		$this->assertTrue( $locked_203, '取消 1-1 完成後，第二章應重新鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 取消前面章節的完成狀態後，後續章節重新鎖定
	 * Example: 取消完成不清除後續章節的 finished_at 紀錄
	 */
	public function test_取消完成不清除後續章節的finished_at(): void {
		// Given Alice 已依序完成 第一章、1-1、1-2
		$this->set_chapter_finished( $this->ch200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch201, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch202, $this->alice_id, '2025-06-01 12:00:00' );

		// When 取消 第一章 的完成狀態
		AVLChapterMeta::delete( $this->ch200, $this->alice_id, 'finished_at' );

		// Then 1-1 及 1-2 的 finished_at 仍然存在（不被清除）
		$finished_201 = $this->get_chapter_meta( $this->ch201, $this->alice_id, 'finished_at' );
		$finished_202 = $this->get_chapter_meta( $this->ch202, $this->alice_id, 'finished_at' );

		$this->assertNotEmpty( $finished_201, '取消第一章完成後，1-1 的 finished_at 應仍存在' );
		$this->assertNotEmpty( $finished_202, '取消第一章完成後，1-2 的 finished_at 應仍存在' );
	}

	// ========== Rule: 管理員 / 講師豁免 ==========

	/**
	 * @test
	 * @group happy
	 * Rule: 管理員不受線性觀看限制
	 * Example: 管理員可自由存取所有章節
	 */
	public function test_管理員可自由存取所有章節(): void {
		// Given Alice 未完成任何章節（預設）

		// When Admin 查詢所有章節鎖定狀態
		$locked_200 = ChapterUtils::is_chapter_locked( $this->ch200, $this->admin_id );
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->admin_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->admin_id );
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->admin_id );
		$locked_204 = ChapterUtils::is_chapter_locked( $this->ch204, $this->admin_id );

		// Then 所有章節對管理員均解鎖
		$this->assertFalse( $locked_200, '管理員存取第一章應解鎖' );
		$this->assertFalse( $locked_201, '管理員存取 1-1 應解鎖' );
		$this->assertFalse( $locked_202, '管理員存取 1-2 應解鎖' );
		$this->assertFalse( $locked_203, '管理員存取第二章應解鎖' );
		$this->assertFalse( $locked_204, '管理員存取 2-1 應解鎖' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 課程講師不受線性觀看限制
	 * Example: 講師可自由存取所有章節
	 */
	public function test_講師可自由存取所有章節(): void {
		// Given Alice 未完成任何章節（預設）

		// When Teacher 查詢所有章節鎖定狀態
		$locked_200 = ChapterUtils::is_chapter_locked( $this->ch200, $this->teacher_id );
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->teacher_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->teacher_id );
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->teacher_id );
		$locked_204 = ChapterUtils::is_chapter_locked( $this->ch204, $this->teacher_id );

		// Then 所有章節對講師均解鎖
		$this->assertFalse( $locked_200, '講師存取第一章應解鎖' );
		$this->assertFalse( $locked_201, '講師存取 1-1 應解鎖' );
		$this->assertFalse( $locked_202, '講師存取 1-2 應解鎖' );
		$this->assertFalse( $locked_203, '講師存取第二章應解鎖' );
		$this->assertFalse( $locked_204, '講師存取 2-1 應解鎖' );
	}

	// ========== Rule: 功能關閉時 ==========

	/**
	 * @test
	 * @group happy
	 * Rule: enable_linear_mode 為 no 時，所有章節均不鎖定
	 * Example: 未開啟線性觀看時，所有章節自由存取
	 */
	public function test_未開啟線性觀看時所有章節自由存取(): void {
		// Given 課程的 enable_linear_mode 設為 no
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// When Alice 查詢所有章節鎖定狀態
		$locked_200 = ChapterUtils::is_chapter_locked( $this->ch200, $this->alice_id );
		$locked_201 = ChapterUtils::is_chapter_locked( $this->ch201, $this->alice_id );
		$locked_202 = ChapterUtils::is_chapter_locked( $this->ch202, $this->alice_id );
		$locked_203 = ChapterUtils::is_chapter_locked( $this->ch203, $this->alice_id );
		$locked_204 = ChapterUtils::is_chapter_locked( $this->ch204, $this->alice_id );

		// Then 所有章節均解鎖
		$this->assertFalse( $locked_200, '功能關閉後第一章應解鎖' );
		$this->assertFalse( $locked_201, '功能關閉後 1-1 應解鎖' );
		$this->assertFalse( $locked_202, '功能關閉後 1-2 應解鎖' );
		$this->assertFalse( $locked_203, '功能關閉後第二章應解鎖' );
		$this->assertFalse( $locked_204, '功能關閉後 2-1 應解鎖' );
	}

	// ========== 額外邊界情況 ==========

	/**
	 * @test
	 * @group happy
	 * 驗證 get_locked_chapter_ids() 批量回傳鎖定清單
	 */
	public function test_get_locked_chapter_ids_批量取得鎖定清單(): void {
		// Given Alice 未完成任何章節

		// When 批量取得課程鎖定章節 ID 清單
		$locked_ids = ChapterUtils::get_locked_chapter_ids( $this->course_id, $this->alice_id );

		// Then 應包含 201/202/203/204（第一章不鎖定）
		$this->assertContains( $this->ch201, $locked_ids, '1-1 應在鎖定清單中' );
		$this->assertContains( $this->ch202, $locked_ids, '1-2 應在鎖定清單中' );
		$this->assertContains( $this->ch203, $locked_ids, '第二章應在鎖定清單中' );
		$this->assertContains( $this->ch204, $locked_ids, '2-1 應在鎖定清單中' );
		$this->assertNotContains( $this->ch200, $locked_ids, '第一章不應在鎖定清單中' );
	}

	/**
	 * @test
	 * @group happy
	 * 驗證 get_locked_chapter_ids() 管理員回傳空陣列
	 */
	public function test_get_locked_chapter_ids_管理員回傳空陣列(): void {
		// When 管理員查詢鎖定清單
		$locked_ids = ChapterUtils::get_locked_chapter_ids( $this->course_id, $this->admin_id );

		// Then 回傳空陣列
		$this->assertSame( [], $locked_ids, '管理員的鎖定清單應為空陣列' );
	}

	/**
	 * @test
	 * @group happy
	 * 驗證 get_locked_chapter_ids() 功能關閉時回傳空陣列
	 */
	public function test_get_locked_chapter_ids_功能關閉回傳空陣列(): void {
		// Given 課程關閉線性觀看
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );

		// When Alice 查詢鎖定清單
		$locked_ids = ChapterUtils::get_locked_chapter_ids( $this->course_id, $this->alice_id );

		// Then 回傳空陣列
		$this->assertSame( [], $locked_ids, '功能關閉時鎖定清單應為空陣列' );
	}

	/**
	 * @test
	 * @group happy
	 * 驗證課程只有一個章節時永遠解鎖
	 */
	public function test_課程只有一個章節時永遠解鎖(): void {
		// Given 建立一個只有單一章節的課程
		$single_course_id = $this->create_course(
			[
				'post_title'  => '單章節課程',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		update_post_meta( $single_course_id, 'enable_linear_mode', 'yes' );

		$single_chapter_id = $this->factory()->post->create(
			[
				'post_title'  => '唯一章節',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => 0,
				'menu_order'  => 10,
			]
		);
		update_post_meta( $single_chapter_id, 'parent_course_id', $single_course_id );

		$this->enroll_user_to_course( $this->alice_id, $single_course_id );

		// When Alice 查詢唯一章節的鎖定狀態
		$is_locked = ChapterUtils::is_chapter_locked( $single_chapter_id, $this->alice_id );

		// Then 唯一章節不被鎖定（第一個章節永遠解鎖）
		$this->assertFalse( $is_locked, '課程只有一個章節時，該章節永遠解鎖' );

		// 清除快取
		wp_cache_delete( 'flatten_post_ids_' . $single_course_id, 'prev_next' );
	}

	/**
	 * @test
	 * @group happy
	 * 驗證 get_flatten_post_ids() DFS 展開順序正確
	 */
	public function test_get_flatten_post_ids_DFS展開順序正確(): void {
		// When 取得課程 DFS 扁平章節 ID 列表
		$flatten_ids = ChapterUtils::get_flatten_post_ids( $this->course_id );

		// Then 順序應為：第一章 → 1-1 → 1-2 → 第二章 → 2-1
		$this->assertCount( 5, $flatten_ids, 'DFS 列表應包含 5 個章節' );
		$this->assertSame( $this->ch200, $flatten_ids[0], '第 1 個應為第一章' );
		$this->assertSame( $this->ch201, $flatten_ids[1], '第 2 個應為 1-1' );
		$this->assertSame( $this->ch202, $flatten_ids[2], '第 3 個應為 1-2' );
		$this->assertSame( $this->ch203, $flatten_ids[3], '第 4 個應為第二章' );
		$this->assertSame( $this->ch204, $flatten_ids[4], '第 5 個應為 2-1' );
	}
}
