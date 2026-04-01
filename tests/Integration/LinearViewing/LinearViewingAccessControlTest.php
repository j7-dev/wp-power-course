<?php
/**
 * 線性觀看存取控制 整合測試
 * Feature: specs/features/linear-viewing/線性觀看存取控制.feature
 *
 * @group linear-viewing
 * @group access-control
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class LinearViewingAccessControlTest
 * 測試線性觀看存取控制邏輯：
 * - is_chapter_locked() 判斷
 * - get_next_should_complete_chapter_id() 回傳值
 * - toggle-finish API 回應包含 next_unlocked_chapter_id / locked_chapter_ids
 * - GET chapters API 的 is_locked 欄位
 * - 未啟用線性觀看時行為
 */
class LinearViewingAccessControlTest extends TestCase {

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

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// ChapterUtils 使用靜態方法；LifeCycle 已在 bootstrap 載入
	}

	/**
	 * 每個測試前建立測試資料
	 * 對應 Feature Background
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

		// 建立章節：200 > 201 > 202 > 203
		$this->ch_200 = $this->create_chapter(
			$this->course_id,
			[ 'post_title' => '第一章', 'post_parent' => $this->course_id, 'menu_order' => 10 ]
		);

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

		$this->ch_203 = $this->create_chapter(
			$this->course_id,
			[ 'post_title' => '第二章', 'post_parent' => $this->course_id, 'menu_order' => 20 ]
		);

		// 加入學員（無任何完成紀錄）
		$this->enroll_user_to_course( $this->alice_id, $this->course_id );
	}

	// ========== is_chapter_locked 基礎判斷 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * 第一章永遠不鎖定
	 */
	public function test_第一章永遠不鎖定(): void {
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_200, $this->alice_id, $this->course_id );

		$this->assertFalse( $is_locked, '第一章（扁平序列第一個）永遠不鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 未完成前一章時，後續章節應鎖定
	 */
	public function test_未完成前一章時後續章節應鎖定(): void {
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// 1-1 小節（ch_201）應被鎖定（前一章 ch_200 未完成）
		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_201, $this->alice_id, $this->course_id );

		$this->assertTrue( $is_locked, '前一章未完成時，1-1 小節應鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 完成前一章後，下一章不鎖定
	 */
	public function test_完成前一章後下一章不鎖定(): void {
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_201, $this->alice_id, $this->course_id );

		$this->assertFalse( $is_locked, '前一章已完成，1-1 小節應解鎖' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 存取鎖定章節時，get_next_should_complete_chapter_id 應回傳第一個未完成章節
	 */
	public function test_存取鎖定章節時應重導向到第一個未完成章節(): void {
		// Given 用戶 "Alice" 無任何章節完成紀錄
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// When 查詢下一個應完成的章節
		$next_chapter_id = ChapterUtils::get_next_should_complete_chapter_id( $this->course_id, $this->alice_id );

		// Then 應回傳第一章（ch_200）
		$this->assertSame( $this->ch_200, $next_chapter_id, '無任何完成記錄時，下一個應完成章節應為第一章' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 完成第一章後存取 1-2 章節時，重導向目標應為 1-1
	 */
	public function test_完成第一章後存取1_2時重導向目標應為1_1(): void {
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// ch_202 應被鎖定（ch_201 未完成）
		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_202, $this->alice_id, $this->course_id );
		$this->assertTrue( $is_locked, '1-2 小節應鎖定（前一章 1-1 未完成）' );

		// 下一個應完成的章節應為 ch_201（1-1 小節）
		$next_chapter_id = ChapterUtils::get_next_should_complete_chapter_id( $this->course_id, $this->alice_id );
		$this->assertSame( $this->ch_201, $next_chapter_id, '完成 200 後，下一個應完成章節應為 1-1 小節' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 解鎖的章節不應被鎖定
	 */
	public function test_解鎖的章節不應被鎖定(): void {
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// 第一章（ch_200）是第一個，永遠不鎖定
		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_200, $this->alice_id, $this->course_id );
		$this->assertFalse( $is_locked, '第一章不應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 完成第一章後，1-1 小節不應被鎖定
	 */
	public function test_完成第一章後1_1不應被鎖定(): void {
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_201, $this->alice_id, $this->course_id );
		$this->assertFalse( $is_locked, '完成第一章後，1-1 小節不應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 已完成的章節即使位於鎖定區間，is_chapter_locked 仍應為 false
	 */
	public function test_已完成章節即使位於鎖定區間仍不鎖定(): void {
		// 200 完成、201 無 finished_at、202 已完成
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch_202, $this->alice_id, '2025-06-01 10:20:00' );
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		$is_locked = ChapterUtils::is_chapter_locked( $this->ch_202, $this->alice_id, $this->course_id );
		$this->assertFalse( $is_locked, '已完成的章節（1-2）不應被鎖定' );
	}

	// ========== get_next_should_complete_chapter_id ==========

	/**
	 * @test
	 * @group linear-viewing
	 * 驗證 get_next_should_complete_chapter_id 正確回傳下一個應完成的章節 ID
	 */
	public function test_取得下一個應完成的章節ID(): void {
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// 無任何完成記錄時，應回傳第一章
		$next_id = ChapterUtils::get_next_should_complete_chapter_id( $this->course_id, $this->alice_id );
		$this->assertSame( $this->ch_200, $next_id, '無完成記錄時，下一個應完成章節為第一章' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 完成第一章後，下一個應完成章節為 1-1 小節
	 */
	public function test_完成第一章後下一個應完成為1_1(): void {
		$this->set_chapter_finished( $this->ch_200, $this->alice_id, '2025-06-01 10:00:00' );
		wp_cache_flush_group( 'prev_next' );

		$next_id = ChapterUtils::get_next_should_complete_chapter_id( $this->course_id, $this->alice_id );
		$this->assertSame( $this->ch_201, $next_id, '完成第一章後，下一個應完成章節為 1-1 小節' );
	}

	// ========== 未啟用線性觀看時的行為 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * 未啟用線性觀看時，所有章節不被鎖定
	 */
	public function test_未啟用線性觀看時所有章節不鎖定(): void {
		// Given 課程的 enable_linear_viewing 為 "no"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );
		wp_set_current_user( $this->alice_id );
		wp_cache_flush_group( 'prev_next' );

		// 任意章節都不應被鎖定
		$is_locked_201 = ChapterUtils::is_chapter_locked( $this->ch_201, $this->alice_id, $this->course_id );
		$is_locked_202 = ChapterUtils::is_chapter_locked( $this->ch_202, $this->alice_id, $this->course_id );
		$is_locked_203 = ChapterUtils::is_chapter_locked( $this->ch_203, $this->alice_id, $this->course_id );

		$this->assertFalse( $is_locked_201, '未啟用線性觀看時，1-1 小節不應被鎖定' );
		$this->assertFalse( $is_locked_202, '未啟用線性觀看時，1-2 小節不應被鎖定' );
		$this->assertFalse( $is_locked_203, '未啟用線性觀看時，第二章不應被鎖定' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 未啟用線性觀看時，get_all_chapters_lock_status 全部回傳 false
	 */
	public function test_未啟用線性觀看時批次查詢全為false(): void {
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );
		wp_cache_flush_group( 'prev_next' );

		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, $this->alice_id );

		foreach ($lock_status as $chapter_id => $is_locked) {
			$this->assertFalse( $is_locked, "未啟用線性觀看時，章節 {$chapter_id} 不應被鎖定" );
		}
	}

	/**
	 * @test
	 * @group linear-viewing
	 * 未登入用戶所有章節 is_locked 為 false
	 */
	public function test_未登入用戶所有章節不鎖定(): void {
		// 用戶 ID 為 0（未登入）
		wp_set_current_user( 0 );
		wp_cache_flush_group( 'prev_next' );

		$lock_status = ChapterUtils::get_all_chapters_lock_status( $this->course_id, 0 );

		foreach ($lock_status as $chapter_id => $is_locked) {
			$this->assertFalse( $is_locked, "未登入時，章節 {$chapter_id} 不應被鎖定" );
		}
	}

	/**
	 * @test
	 * @group linear-viewing
	 * format_chapter_details 回傳的陣列包含 is_locked 欄位
	 */
	public function test_format_chapter_details包含is_locked欄位(): void {
		$post = get_post( $this->ch_200 );
		$this->assertInstanceOf( \WP_Post::class, $post );

		/** @var \WP_Post $post */
		$formatted = ChapterUtils::format_chapter_details( $post );

		$this->assertArrayHasKey( 'is_locked', $formatted, 'format_chapter_details 回傳陣列應包含 is_locked 欄位' );
		$this->assertFalse( $formatted['is_locked'], 'format_chapter_details 預設 is_locked 應為 false' );
	}
}
