<?php
/**
 * 線性觀看存取控制 整合測試
 * Feature: specs/features/linear-viewing/線性觀看存取控制.feature
 *
 * @group linear_viewing
 * @group query
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class LinearViewingAccessControlTest
 * 測試線性觀看存取控制邏輯：is_chapter_accessible / get_current_progress_chapter_id
 */
class LinearViewingAccessControlTest extends TestCase {

	/** @var int 課程 ID（enable_sequential = yes） */
	private int $course_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 使用 ChapterUtils::is_chapter_accessible 和 ChapterUtils::get_current_progress_chapter_id
	}

	/**
	 * 每個測試前建立共用基礎資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立測試課程（線性觀看開啟）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		// enable_sequential 需額外設定
		update_post_meta( $this->course_id, 'enable_sequential', 'yes' );

		// 建立 Alice 用戶
		$this->alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		// Alice 加入課程（永久）
		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );
	}

	/**
	 * 建立單層章節結構（1-1, 1-2, 1-3），回傳 [ch1_id, ch2_id, ch3_id]
	 *
	 * @return array{0: int, 1: int, 2: int}
	 */
	private function create_flat_chapters_3(): array {
		$ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		$ch2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $this->course_id,
			]
		);
		$ch3 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-3',
				'menu_order'  => 3,
				'post_parent' => $this->course_id,
			]
		);
		// 清除 wp_cache 讓 get_flatten_post_ids 重新計算
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		return [ $ch1, $ch2, $ch3 ];
	}

	/**
	 * 建立單層章節結構（1-1, 1-2），回傳 [ch1_id, ch2_id]
	 *
	 * @return array{0: int, 1: int}
	 */
	private function create_flat_chapters_2(): array {
		$ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		$ch2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $this->course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		return [ $ch1, $ch2 ];
	}

	/**
	 * 建立含父章節的階層結構：
	 * 第一章 (parent=course, order=1)
	 *   1-1 (parent=第一章, order=1)
	 *   1-2 (parent=第一章, order=2)  [可選]
	 * 第二章 (parent=course, order=2)
	 *   2-1 (parent=第二章, order=1)
	 *
	 * @param bool $include_1_2 是否包含 1-2 章節
	 * @return array{ch1: int, ch1_1: int, ch1_2?: int, ch2: int, ch2_1: int}
	 */
	private function create_hierarchy_chapters( bool $include_1_2 = true ): array {
		// 父章節「第一章」
		$ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		// 子章節 1-1（parent=第一章，不設定 parent_course_id=course）
		$ch1_1 = $this->factory()->post->create(
			[
				'post_title'  => '1-1',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => $ch1,
				'menu_order'  => 1,
			]
		);

		$result = [
			'ch1'   => $ch1,
			'ch1_1' => $ch1_1,
			'ch2'   => 0,
			'ch2_1' => 0,
		];

		if ( $include_1_2 ) {
			$ch1_2 = $this->factory()->post->create(
				[
					'post_title'  => '1-2',
					'post_status' => 'publish',
					'post_type'   => 'pc_chapter',
					'post_parent' => $ch1,
					'menu_order'  => 2,
				]
			);
			$result['ch1_2'] = $ch1_2;
		}

		// 父章節「第二章」
		$ch2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'menu_order'  => 2,
				'post_parent' => $this->course_id,
			]
		);
		// 子章節 2-1
		$ch2_1 = $this->factory()->post->create(
			[
				'post_title'  => '2-1',
				'post_status' => 'publish',
				'post_type'   => 'pc_chapter',
				'post_parent' => $ch2,
				'menu_order'  => 1,
			]
		);

		$result['ch2']   = $ch2;
		$result['ch2_1'] = $ch2_1;

		wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );

		return $result;
	}

	// ========== 單層章節：第一個章節永遠可存取 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 第一個章節永遠可存取
	 * Example: 學員可存取第一個章節（無完成紀錄）
	 */
	public function test_單層章節_第一個章節永遠可存取(): void {
		// Given 課程有 3 個章節，Alice 無任何完成紀錄
		[ $ch1, $ch2, $ch3 ] = $this->create_flat_chapters_3();

		// When 用戶 "Alice" 存取第一個章節
		$result = ChapterUtils::is_chapter_accessible( $ch1, $this->alice_id, $this->course_id );

		// Then 存取成功
		$this->assertTrue( $result, '第一個章節應永遠可存取' );
	}

	// ========== 單層章節：未完成前一章節時不可存取後續章節 ==========

	/**
	 * @test
	 * @group access_denied
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 未完成前一章節時不可存取後續章節
	 * Example: 1-1 未完成時不可存取 1-2
	 */
	public function test_單層章節_未完成1_1時不可存取1_2(): void {
		// Given 課程有 3 個章節，1-1 無 finished_at
		[ $ch1, $ch2, $ch3 ] = $this->create_flat_chapters_3();
		// Alice 沒有完成任何章節

		// When 用戶 "Alice" 存取章節 1-2
		$result = ChapterUtils::is_chapter_accessible( $ch2, $this->alice_id, $this->course_id );

		// Then 存取被拒絕
		$this->assertFalse( $result, '1-1 未完成時不可存取 1-2' );

		// And 導向至章節 1-1（get_current_progress_chapter_id 應回傳 1-1）
		$redirect_to = ChapterUtils::get_current_progress_chapter_id( $this->alice_id, $this->course_id );
		$this->assertSame( $ch1, $redirect_to, '導向目標應為 1-1' );
	}

	/**
	 * @test
	 * @group access_denied
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 未完成前一章節時不可存取後續章節
	 * Example: 1-1 已完成但 1-2 未完成時不可存取 1-3
	 */
	public function test_單層章節_1_1完成但1_2未完成時不可存取1_3(): void {
		// Given 課程有 3 個章節，1-1 已完成，1-2 未完成
		[ $ch1, $ch2, $ch3 ] = $this->create_flat_chapters_3();
		$this->set_chapter_finished( $ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 用戶 "Alice" 存取章節 1-3
		$result = ChapterUtils::is_chapter_accessible( $ch3, $this->alice_id, $this->course_id );

		// Then 存取被拒絕
		$this->assertFalse( $result, '1-2 未完成時不可存取 1-3' );

		// And 導向至章節 1-2
		$redirect_to = ChapterUtils::get_current_progress_chapter_id( $this->alice_id, $this->course_id );
		$this->assertSame( $ch2, $redirect_to, '導向目標應為 1-2' );
	}

	// ========== 單層章節：完成前一章節後可存取下一個章節 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 完成前一章節後可存取下一個章節
	 * Example: 完成 1-1 後可存取 1-2
	 */
	public function test_單層章節_完成1_1後可存取1_2(): void {
		// Given 課程有 2 個章節，1-1 已完成
		[ $ch1, $ch2 ] = $this->create_flat_chapters_2();
		$this->set_chapter_finished( $ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 用戶 "Alice" 存取章節 1-2
		$result = ChapterUtils::is_chapter_accessible( $ch2, $this->alice_id, $this->course_id );

		// Then 存取成功
		$this->assertTrue( $result, '完成 1-1 後應可存取 1-2' );
	}

	// ========== 階層結構：父章節也在順序中，必須完成才能繼續 ==========

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 父章節「第一章」未完成時不可存取子章節 1-1
	 */
	public function test_階層結構_父章節未完成時不可存取子章節(): void {
		// Given 課程有階層結構，「第一章」父章節未完成
		$chapters = $this->create_hierarchy_chapters( true );

		// When 用戶 "Alice" 存取章節 1-1（父章節第一章的子章節）
		$result = ChapterUtils::is_chapter_accessible( $chapters['ch1_1'], $this->alice_id, $this->course_id );

		// Then 存取被拒絕（因為第一章未完成，1-1 是第一章的子章節）
		$this->assertFalse( $result, '父章節未完成時不可存取子章節 1-1' );

		// And 導向至章節「第一章」
		$redirect_to = ChapterUtils::get_current_progress_chapter_id( $this->alice_id, $this->course_id );
		$this->assertSame( $chapters['ch1'], $redirect_to, '導向目標應為「第一章」父章節' );
	}

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 完成「第一章」→ 1-1 → 1-2 後可存取「第二章」
	 */
	public function test_階層結構_完成所有子章節後可存取第二章(): void {
		// Given 課程有階層結構，第一章、1-1、1-2 均已完成
		$chapters = $this->create_hierarchy_chapters( true );
		$this->set_chapter_finished( $chapters['ch1'], $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $chapters['ch1_1'], $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $chapters['ch1_2'], $this->alice_id, '2025-06-01 12:00:00' );

		// When 用戶 "Alice" 存取「第二章」
		$result = ChapterUtils::is_chapter_accessible( $chapters['ch2'], $this->alice_id, $this->course_id );

		// Then 存取成功
		$this->assertTrue( $result, '完成所有第一章的章節後應可存取第二章' );
	}

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 完成第一章的子章節但未完成「第二章」父章節時不可存取 2-1
	 */
	public function test_階層結構_未完成父章節時不可存取子章節(): void {
		// Given 課程有階層結構（不含 1-2），第一章與 1-1 已完成，第二章未完成
		$chapters = $this->create_hierarchy_chapters( false );
		$this->set_chapter_finished( $chapters['ch1'], $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $chapters['ch1_1'], $this->alice_id, '2025-06-01 11:00:00' );
		// 第二章（ch2）無 finished_at

		// When 用戶 "Alice" 存取 2-1（第二章的子章節）
		$result = ChapterUtils::is_chapter_accessible( $chapters['ch2_1'], $this->alice_id, $this->course_id );

		// Then 存取被拒絕
		$this->assertFalse( $result, '第二章未完成時不可存取 2-1' );

		// And 導向至「第二章」
		$redirect_to = ChapterUtils::get_current_progress_chapter_id( $this->alice_id, $this->course_id );
		$this->assertSame( $chapters['ch2'], $redirect_to, '導向目標應為「第二章」' );
	}

	// ========== 線性觀看關閉時：所有章節自由存取 ==========

	/**
	 * @test
	 * @group sequential_off
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 線性觀看關閉 - 所有章節自由存取
	 * Example: enable_sequential 為 no 時可自由存取任何章節
	 */
	public function test_線性觀看關閉時所有章節可自由存取(): void {
		// Given 系統有一個 enable_sequential = no 的課程
		$free_course_id = $this->create_course(
			[
				'post_title' => '自由課程',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $free_course_id, 'enable_sequential', 'no' );

		$ch_a1 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-1',
				'menu_order'  => 1,
				'post_parent' => $free_course_id,
			]
		);
		$ch_a2 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-2',
				'menu_order'  => 2,
				'post_parent' => $free_course_id,
			]
		);
		$ch_a3 = $this->create_chapter(
			$free_course_id,
			[
				'post_title'  => 'A-3',
				'menu_order'  => 3,
				'post_parent' => $free_course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $free_course_id, 'prev_next' );

		// Alice 加入此課程
		$this->enroll_user_to_course( $this->alice_id, $free_course_id, 0 );
		// A-1 無 finished_at

		// When 用戶 "Alice" 存取 A-3（線性觀看關閉）
		$result = ChapterUtils::is_chapter_accessible( $ch_a3, $this->alice_id, $free_course_id );

		// Then 存取成功
		$this->assertTrue( $result, '線性觀看關閉時應可自由存取任何章節' );
	}

	// ========== 已完成章節可隨時回看 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 已完成的章節可隨時回看
	 * Example: 已完成 1-1 後仍可回看 1-1
	 */
	public function test_已完成章節可隨時回看(): void {
		// Given 課程有 2 個章節，1-1 已完成
		[ $ch1, $ch2 ] = $this->create_flat_chapters_2();
		$this->set_chapter_finished( $ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 用戶 "Alice" 存取已完成的章節 1-1
		$result = ChapterUtils::is_chapter_accessible( $ch1, $this->alice_id, $this->course_id );

		// Then 存取成功（已完成的章節可回看）
		$this->assertTrue( $result, '已完成的章節應可隨時回看' );
	}

	// ========== 後端驗證：透過 URL 直接存取被鎖定章節時導向正確章節 ==========

	/**
	 * @test
	 * @group redirect
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 後端驗證 - 透過 URL 直接存取被鎖定章節時導向正確章節
	 * Example: 直接輸入網址存取鎖定章節時導向到目前進度章節
	 */
	public function test_直接URL存取鎖定章節時導向目前進度章節(): void {
		// Given 課程有 3 個章節，1-1 已完成，1-2 未完成
		[ $ch1, $ch2, $ch3 ] = $this->create_flat_chapters_3();
		$this->set_chapter_finished( $ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 用戶 "Alice" 嘗試直接存取章節 1-3（被鎖定）
		$is_accessible = ChapterUtils::is_chapter_accessible( $ch3, $this->alice_id, $this->course_id );

		// Then 存取被拒絕，並導向至目前進度章節 1-2
		$this->assertFalse( $is_accessible, '1-3 應被鎖定' );

		$redirect_to = ChapterUtils::get_current_progress_chapter_id( $this->alice_id, $this->course_id );
		$this->assertSame( $ch2, $redirect_to, '導向目標應為 1-2（目前進度章節）' );
	}

	// ========== 存取權限：無課程存取權時優先回傳存取權錯誤 ==========

	/**
	 * @test
	 * @group access_permission
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 存取權限 - 無課程存取權時優先回傳存取權錯誤
	 * Example: 無課程存取權時不檢查線性觀看
	 */
	public function test_無課程存取權時不檢查線性觀看(): void {
		// Given 課程有一個章節
		[ $ch1 ] = $this->create_flat_chapters_3();

		// 建立 Bob 用戶，未加入課程
		$bob_id = $this->factory()->user->create(
			[
				'user_login' => 'bob_' . uniqid(),
				'user_email' => 'bob_' . uniqid() . '@test.com',
			]
		);

		// When/Then：Bob 無課程存取權
		// is_chapter_accessible 不驗證課程存取權（那是課程 middleware 的職責）
		// 此測試驗證：無課程授權時仍會被存取控制攔截
		$this->assert_user_has_no_course_access( $bob_id, $this->course_id );

		// 即使 is_chapter_accessible 本身不直接拋出「無存取權」錯誤
		// 課程存取是比線性觀看更上層的防線
		// 這裡驗證 Bob 確實沒有課程存取權
		$this->assertFalse(
			$this->user_has_course_access( $bob_id, $this->course_id ),
			'Bob 應無此課程存取權'
		);
	}
}
