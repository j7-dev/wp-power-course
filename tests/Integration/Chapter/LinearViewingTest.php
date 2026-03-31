<?php
/**
 * 線性觀看 整合測試
 * Feature: specs/features/linear-viewing/檢查章節解鎖狀態.feature
 * Feature: specs/features/linear-viewing/切換章節完成與線性觀看連動.feature
 * Feature: specs/features/linear-viewing/存取鎖定章節.feature
 *
 * @group linear-viewing
 * @group happy
 */

declare( strict_types=1 );

namespace Tests\Integration\Chapter;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\LinearViewing;

/**
 * Class LinearViewingTest
 * 測試線性觀看核心邏輯：章節解鎖狀態判斷
 */
class LinearViewingTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int 第一章 ID (扁平化序號 0) */
	private int $chapter_200_id;

	/** @var int 1-1 ID (扁平化序號 1) */
	private int $chapter_201_id;

	/** @var int 1-2 ID (扁平化序號 2) */
	private int $chapter_202_id;

	/** @var int 第二章 ID (扁平化序號 3) */
	private int $chapter_203_id;

	/** @var int 2-1 ID (扁平化序號 4) */
	private int $chapter_204_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int Admin 用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// LinearViewing 是靜態抽象類別，無需初始化
	}

	/**
	 * 每個測試前建立 Background 資料
	 * Background:
	 *   - 系統中有 Alice 和 Admin 用戶
	 *   - 課程 100 (PHP 基礎課) 啟用線性觀看
	 *   - 課程有 5 個章節（扁平化序號 0-4）
	 *   - Alice 已加入課程
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立 Admin 用戶（具有 manage_woocommerce 權限）
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;
		// 指派 manage_woocommerce 能力
		$admin_user = get_userdata( $this->admin_id );
		if ( $admin_user ) {
			$admin_user->add_cap( 'manage_woocommerce' );
		}

		// 建立 Alice 用戶
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		// 建立測試課程並啟用線性觀看
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );

		// 建立 5 個章節（模擬扁平化順序：父章節也納入序列）
		// 扁平化序列：第一章(200) -> 1-1(201) -> 1-2(202) -> 第二章(203) -> 2-1(204)
		$this->chapter_200_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 1,
			]
		);

		$this->chapter_201_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'post_parent' => $this->chapter_200_id,
				'menu_order'  => 1,
			]
		);

		$this->chapter_202_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'post_parent' => $this->chapter_200_id,
				'menu_order'  => 2,
			]
		);

		$this->chapter_203_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 2,
			]
		);

		$this->chapter_204_id = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'post_parent' => $this->chapter_203_id,
				'menu_order'  => 1,
			]
		);

		// Alice 加入課程（永久存取）
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );

		// 清除扁平化快取，確保每個測試都取得最新數據
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
	}

	/**
	 * 清理快取
	 */
	public function tear_down(): void {
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		parent::tear_down();
	}

	// ========== is_chapter_locked — 基本解鎖邏輯 ==========

	/**
	 * @test
	 * @group happy
	 * 未啟用線性觀看時，所有章節不鎖定
	 * Rule: 未啟用線性觀看的課程，所有章節都不鎖定
	 */
	public function test_未啟用線性觀看所有章節不鎖定(): void {
		// Given 課程未啟用線性觀看
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// When/Then 所有章節都不鎖定
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'未啟用線性觀看時，第一章不應鎖定'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'未啟用線性觀看時，1-1 不應鎖定'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'未啟用線性觀看時，2-1 不應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 第一個章節永遠解鎖
	 * Rule: 第一個章節永遠解鎖
	 */
	public function test_第一個章節永遠解鎖(): void {
		// Given Alice 無任何章節完成紀錄（預設狀態）

		// When/Then 第一個章節不鎖定
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'第一個章節永遠不應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 新學員只有第一個章節解鎖，其餘鎖定
	 * Rule: 第一個章節永遠解鎖
	 */
	public function test_新學員只有第一個章節解鎖(): void {
		// Given Alice 無任何章節完成紀錄

		// When 查詢各章節鎖定狀態
		// Then 章節解鎖狀態
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'第一章 (200) 應解鎖'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'1-1 (201) 應鎖定'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'1-2 (202) 應鎖定'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'第二章 (203) 應鎖定'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'2-1 (204) 應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 完成第一章後，1-1 解鎖
	 * Rule: 完成前一個章節後，下一個章節解鎖
	 */
	public function test_完成第一章後1_1解鎖(): void {
		// Given Alice 完成第一章 (200)
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 查詢解鎖狀態
		// Then
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'已完成的第一章應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'前一個已完成，1-1 應解鎖'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'1-2 仍應鎖定'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'第二章仍應鎖定'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'2-1 仍應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 依序完成到 1-2 後，第二章解鎖
	 * Rule: 完成前一個章節後，下一個章節解鎖
	 */
	public function test_依序完成到1_2後第二章解鎖(): void {
		// Given Alice 完成 200, 201, 202
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-01 12:00:00' );

		// Then 狀態：200-203 解鎖，204 鎖定
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'200 已完成應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'201 已完成應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'202 已完成應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'203 前一個(202)已完成應解鎖'
		);
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'204 前一個(203)未完成應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 已完成的章節永遠解鎖（已完成 = 已解鎖）
	 * Rule: 已完成的章節永遠解鎖
	 */
	public function test_已完成章節永遠解鎖(): void {
		// Given Alice 完成 200 和 202，但 201 未完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		// 201 無 finished_at
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-01 12:00:00' );

		// Then 正確的解鎖狀態
		// 200: 第一個章節，永遠解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'200: 第一個章節應解鎖'
		);
		// 201: 前一個(200)已完成 → 解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'201: 前一個(200)已完成應解鎖'
		);
		// 202: 自身已完成 → 已完成=已解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'202: 自身已完成應解鎖'
		);
		// 203: 前一個(202)已完成 → 解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'203: 前一個(202)已完成應解鎖'
		);
		// 204: 前一個(203)未完成 → 鎖定
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'204: 前一個(203)未完成應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 管理員不受線性觀看限制
	 * Rule: 管理員預覽模式下，所有章節不鎖定
	 */
	public function test_管理員不受線性觀看限制(): void {
		// Given Admin 具有 manage_woocommerce 權限
		// Given Alice 無任何章節完成紀錄

		// When 管理員查看任意章節
		// Then 所有章節不鎖定
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->admin_id ),
			'管理員不應受線性觀看限制，204 應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->admin_id ),
			'管理員不應受線性觀看限制，201 應解鎖'
		);
	}

	// ========== is_chapter_locked — 邊界情況 ==========

	/**
	 * @test
	 * @group edge
	 * 單章節課程不鎖定
	 * Rule: 只有一個章節的課程，該章節始終解鎖
	 */
	public function test_單章節課程不鎖定(): void {
		// Given 單章節課程
		$single_course_id = $this->create_course(
			[
				'post_title' => '單元課程',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $single_course_id, 'enable_linear_viewing', 'yes' );

		$single_chapter_id = $this->create_chapter(
			$single_course_id,
			[
				'post_title'  => '唯一章節',
				'post_parent' => $single_course_id,
				'menu_order'  => 1,
			]
		);
		$this->enroll_user_to_course( $this->alice_id, $single_course_id, 0 );
		wp_cache_delete( 'flatten_post_ids_' . $single_course_id, 'prev_next' );

		// When/Then 唯一章節應解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $single_chapter_id, $single_course_id, $this->alice_id ),
			'單章節課程的唯一章節應解鎖'
		);
	}

	/**
	 * @test
	 * @group edge
	 * chapter 不在 flat_ids 中 → 不鎖定（fail open）
	 * Rule: 錯誤處理 - fail open
	 */
	public function test_章節不在序列中則不鎖定(): void {
		// Given 一個不屬於此課程的章節 ID（使用不存在的ID）
		$nonexistent_chapter_id = 999999;

		// When/Then 不在序列中的章節不應鎖定（fail open）
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $nonexistent_chapter_id, $this->course_id, $this->alice_id ),
			'不在序列中的章節應 fail open（不鎖定）'
		);
	}

	/**
	 * @test
	 * @group edge
	 * 空課程（無章節）→ 不鎖定
	 */
	public function test_無章節課程不鎖定(): void {
		// Given 無章節的課程
		$empty_course_id = $this->create_course(
			[
				'post_title' => '空課程',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $empty_course_id, 'enable_linear_viewing', 'yes' );
		wp_cache_delete( 'flatten_post_ids_' . $empty_course_id, 'prev_next' );

		// When/Then 任意章節ID不鎖定
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $empty_course_id, $this->alice_id ),
			'無章節課程應 fail open（不鎖定）'
		);
	}

	// ========== get_chapters_lock_map ==========

	/**
	 * @test
	 * @group happy
	 * 批量取得所有章節鎖定狀態
	 * Rule: get_chapters_lock_map 回傳課程所有章節鎖定狀態
	 */
	public function test_批量取得章節鎖定狀態(): void {
		// Given Alice 完成第一章
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 取得 lock_map
		$lock_map = LinearViewing::get_chapters_lock_map( $this->course_id, $this->alice_id );

		// Then lock_map 應包含所有章節的鎖定狀態
		$this->assertIsArray( $lock_map, 'lock_map 應為陣列' );
		$this->assertArrayHasKey( $this->chapter_200_id, $lock_map, 'lock_map 應包含 200' );
		$this->assertArrayHasKey( $this->chapter_201_id, $lock_map, 'lock_map 應包含 201' );
		$this->assertArrayHasKey( $this->chapter_202_id, $lock_map, 'lock_map 應包含 202' );
		$this->assertArrayHasKey( $this->chapter_203_id, $lock_map, 'lock_map 應包含 203' );
		$this->assertArrayHasKey( $this->chapter_204_id, $lock_map, 'lock_map 應包含 204' );

		// 200: 已完成，解鎖
		$this->assertFalse( $lock_map[ $this->chapter_200_id ], '200 應解鎖' );
		// 201: 前一個完成，解鎖
		$this->assertFalse( $lock_map[ $this->chapter_201_id ], '201 應解鎖' );
		// 202: 前一個未完成，鎖定
		$this->assertTrue( $lock_map[ $this->chapter_202_id ], '202 應鎖定' );
		// 203: 前一個未完成，鎖定
		$this->assertTrue( $lock_map[ $this->chapter_203_id ], '203 應鎖定' );
		// 204: 前一個未完成，鎖定
		$this->assertTrue( $lock_map[ $this->chapter_204_id ], '204 應鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 * 未啟用線性觀看時 lock_map 全為 false
	 */
	public function test_未啟用線性觀看時lock_map全為false(): void {
		// Given 課程未啟用線性觀看
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// When 取得 lock_map
		$lock_map = LinearViewing::get_chapters_lock_map( $this->course_id, $this->alice_id );

		// Then 全部為 false
		foreach ( $lock_map as $chapter_id => $is_locked ) {
			$this->assertFalse( $is_locked, "未啟用線性觀看時，章節 {$chapter_id} 不應鎖定" );
		}
	}

	// ========== get_first_locked_chapter_id ==========

	/**
	 * @test
	 * @group happy
	 * 新學員取得第一個應完成的章節（第一個章節）
	 * Rule: get_first_locked_chapter_id 回傳第一個未完成的章節
	 */
	public function test_新學員取得第一個應完成章節(): void {
		// Given Alice 無任何章節完成紀錄

		// When 取得第一個鎖定章節
		$first_locked_id = LinearViewing::get_first_locked_chapter_id( $this->course_id, $this->alice_id );

		// Then 應為第一個章節（200）
		$this->assertSame(
			$this->chapter_200_id,
			$first_locked_id,
			'新學員應被導向第一個章節'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 部分完成後取得下一個應完成的章節
	 * Rule: get_first_locked_chapter_id 回傳序列中第一個未完成的章節
	 */
	public function test_部分完成後取得下一個應完成章節(): void {
		// Given Alice 完成 200, 201
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );

		// When 取得第一個鎖定章節
		$first_locked_id = LinearViewing::get_first_locked_chapter_id( $this->course_id, $this->alice_id );

		// Then 應為 202（下一個未完成的章節）
		$this->assertSame(
			$this->chapter_202_id,
			$first_locked_id,
			'完成 200, 201 後應被導向 202'
		);
	}

	/**
	 * @test
	 * @group edge
	 * 所有章節都完成時回傳 null
	 */
	public function test_所有章節完成後回傳null(): void {
		// Given Alice 完成所有章節
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-01 12:00:00' );
		$this->set_chapter_finished( $this->chapter_203_id, $this->alice_id, '2025-06-01 13:00:00' );
		$this->set_chapter_finished( $this->chapter_204_id, $this->alice_id, '2025-06-01 14:00:00' );

		// When 取得第一個鎖定章節
		$first_locked_id = LinearViewing::get_first_locked_chapter_id( $this->course_id, $this->alice_id );

		// Then 應為 null（無鎖定章節）
		$this->assertNull(
			$first_locked_id,
			'所有章節完成後 get_first_locked_chapter_id 應回傳 null'
		);
	}

	// ========== get_next_unlocked_chapter_id ==========

	/**
	 * @test
	 * @group happy
	 * 完成當前章節後，回傳下一個解鎖的章節 ID
	 * Rule: 完成章節後，API 回傳 next_unlocked_chapter_id
	 */
	public function test_完成章節後取得下一個解鎖章節(): void {
		// Given Alice 完成第一章 (200)，其他未完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );

		// When 取得完成 200 後下一個解鎖的章節
		$next_unlocked_id = LinearViewing::get_next_unlocked_chapter_id(
			$this->chapter_200_id,
			$this->course_id,
			$this->alice_id
		);

		// Then 應為 201
		$this->assertSame(
			$this->chapter_201_id,
			$next_unlocked_id,
			'完成第一章後下一個解鎖的章節應為 201'
		);
	}

	/**
	 * @test
	 * @group edge
	 * 完成最後一個章節時回傳 null
	 * Rule: 完成最後一個章節時 next_unlocked_chapter_id 為 null
	 */
	public function test_完成最後章節後回傳null(): void {
		// Given Alice 完成所有章節（包含最後一個 204）
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-01 12:00:00' );
		$this->set_chapter_finished( $this->chapter_203_id, $this->alice_id, '2025-06-01 13:00:00' );
		$this->set_chapter_finished( $this->chapter_204_id, $this->alice_id, '2025-06-01 14:00:00' );

		// When 取得完成 204 後下一個解鎖的章節
		$next_unlocked_id = LinearViewing::get_next_unlocked_chapter_id(
			$this->chapter_204_id,
			$this->course_id,
			$this->alice_id
		);

		// Then 應為 null（無更多章節）
		$this->assertNull(
			$next_unlocked_id,
			'完成最後章節後 get_next_unlocked_chapter_id 應回傳 null'
		);
	}

	// ========== 中途啟用線性觀看 ==========

	/**
	 * @test
	 * @group happy
	 * 中途啟用線性觀看時，已完成的章節維持解鎖
	 * Rule: 中途啟用線性觀看時，已完成的章節維持解鎖
	 */
	public function test_中途啟用線性觀看已完成章節維持解鎖(): void {
		// Given Alice 已完成 200, 201, 202
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_202_id, $this->alice_id, '2025-06-01 12:00:00' );
		// 課程已啟用線性觀看（在 set_up 中設定）

		// When/Then 解鎖狀態
		// 200-202 已完成 → 解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'200 已完成應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'201 已完成應解鎖'
		);
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'202 已完成應解鎖'
		);
		// 203 前一個(202)已完成 → 解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'203 前一個(202)已完成應解鎖'
		);
		// 204 前一個(203)未完成 → 鎖定
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'204 前一個(203)未完成應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * 取消完成 1-1 後，取消完成連鎖解鎖邏輯
	 * Rule: 取消完成後，未完成的後續章節重新鎖定（前一個未完成則鎖定）
	 */
	public function test_取消完成章節後的連鎖鎖定(): void {
		// Given Alice 完成 200, 201，但 202 及之後未完成
		$this->set_chapter_finished( $this->chapter_200_id, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_201_id, $this->alice_id, '2025-06-01 11:00:00' );
		// 202, 203, 204 無 finished_at

		// When 取消 201 的完成狀態（刪除 finished_at）
		$this->cancel_chapter_finished( $this->chapter_201_id, $this->alice_id );

		// Then 取消後狀態：200已完成, 201未完成, 202-204未完成
		// 200: 第一個章節，永遠解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_200_id, $this->course_id, $this->alice_id ),
			'200 應解鎖'
		);
		// 201: 前一個(200)已完成 → 解鎖
		$this->assertFalse(
			LinearViewing::is_chapter_locked( $this->chapter_201_id, $this->course_id, $this->alice_id ),
			'201 前一個(200)已完成應解鎖'
		);
		// 202: 前一個(201)未完成 → 鎖定
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_202_id, $this->course_id, $this->alice_id ),
			'202 前一個(201)未完成應鎖定'
		);
		// 203: 前一個(202)未完成 → 鎖定
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_203_id, $this->course_id, $this->alice_id ),
			'203 應鎖定'
		);
		// 204: 鎖定
		$this->assertTrue(
			LinearViewing::is_chapter_locked( $this->chapter_204_id, $this->course_id, $this->alice_id ),
			'204 應鎖定'
		);
	}

	// ========== Helper Methods ==========

	/**
	 * 取消章節完成狀態
	 *
	 * @param int $chapter_id 章節 ID
	 * @param int $user_id    用戶 ID
	 */
	private function cancel_chapter_finished( int $chapter_id, int $user_id ): void {
		\J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD::delete( $chapter_id, $user_id, 'finished_at' );
	}
}
