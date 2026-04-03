<?php
/**
 * 檢查章節解鎖狀態 整合測試
 * Feature: specs/features/linear-viewing/檢查章節解鎖狀態.feature
 *
 * @group linear-viewing
 * @group query
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class ChapterUnlockStatusTest
 * 測試線性觀看模式下章節解鎖狀態判定邏輯
 */
class ChapterUnlockStatusTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int ch1 章節 ID（第一章，攤平第 0 個） */
	private int $ch1;

	/** @var int ch1_1 章節 ID（1-1，攤平第 1 個） */
	private int $ch1_1;

	/** @var int ch1_2 章節 ID（1-2，攤平第 2 個） */
	private int $ch1_2;

	/** @var int ch2 章節 ID（第二章，攤平第 3 個） */
	private int $ch2;

	/** @var int ch2_1 章節 ID（2-1，攤平第 4 個） */
	private int $ch2_1;

	protected function configure_dependencies(): void {
		// 直接使用靜態方法 ChapterUtils::is_chapter_unlocked()
	}

	public function set_up(): void {
		parent::set_up();

		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);

		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );

		// 第一章（頂層，menu_order=1）
		$this->ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		// 1-1（ch1 子，menu_order=1）
		$this->ch1_1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $this->ch1,
			]
		);
		// 1-2（ch1 子，menu_order=2）
		$this->ch1_2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $this->ch1,
			]
		);
		// 第二章（頂層，menu_order=2）
		$this->ch2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'menu_order'  => 2,
				'post_parent' => $this->course_id,
			]
		);
		// 2-1（ch2 子，menu_order=1）
		$this->ch2_1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '2-1',
				'menu_order'  => 1,
				'post_parent' => $this->ch2,
			]
		);

		$this->alice_id     = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );

		// 清除快取確保每次重算
		\wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
	}

	public function tear_down(): void {
		\wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		parent::tear_down();
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 第一個章節固定為解鎖狀態
	 * Example: 新學員首次進入，第一章可存取
	 */
	public function test_新學員首次進入第一章可存取(): void {
		// Given 用戶未完成任何章節（預設狀態）

		// When & Then 第一章應為解鎖
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch1, $this->alice_id, $this->course_id ),
			'第一章（攤平第 0 個）固定解鎖'
		);

		// And 1-1 應為鎖定
		$this->assertFalse(
			ChapterUtils::is_chapter_unlocked( $this->ch1_1, $this->alice_id, $this->course_id ),
			'1-1 在第一章未完成時應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 完成當前章節後，下一個章節解鎖
	 * Example: 完成第一章後 1-1 解鎖
	 */
	public function test_完成第一章後1_1解鎖(): void {
		// Given Alice 完成第一章
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When & Then 1-1 應解鎖
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch1_1, $this->alice_id, $this->course_id ),
			'完成第一章後 1-1 應解鎖'
		);

		// And 1-2 仍鎖定
		$this->assertFalse(
			ChapterUtils::is_chapter_unlocked( $this->ch1_2, $this->alice_id, $this->course_id ),
			'1-1 未完成時 1-2 應鎖定'
		);
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 攤平順序跨越父章節邊界
	 * Example: 完成 1-2 後解鎖第二章
	 */
	public function test_完成1_2後解鎖第二章(): void {
		// Given 完成前三個章節
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch1_1, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch1_2, $this->alice_id, '2025-06-01 12:00:00' );

		// When & Then 第二章應解鎖
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch2, $this->alice_id, $this->course_id ),
			'完成 1-2 後第二章應解鎖'
		);

		// And 2-1 仍鎖定
		$this->assertFalse(
			ChapterUtils::is_chapter_unlocked( $this->ch2_1, $this->alice_id, $this->course_id ),
			'第二章未完成時 2-1 應鎖定'
		);
	}

	/**
	 * @test
	 * @group edge
	 * Rule: 嚴格線性規則 — 中間有未完成章節時後續即使已完成也鎖定
	 * Example: 跳過 1-1 已完成 1-2 的情境
	 */
	public function test_跳過1_1已完成1_2中間有缺口時1_2為鎖定(): void {
		// Given 完成第一章，跳過 1-1，直接完成 1-2
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		// ch1_1 無 finished_at
		$this->set_chapter_finished( $this->ch1_2, $this->alice_id, '2025-06-01 12:00:00' );

		// When & Then 1-1 已解鎖（前一章 ch1 已完成）
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch1_1, $this->alice_id, $this->course_id ),
			'1-1 前一章已完成，應為已解鎖'
		);

		// And 1-2 應鎖定（前一章 ch1_1 未完成）
		$this->assertFalse(
			ChapterUtils::is_chapter_unlocked( $this->ch1_2, $this->alice_id, $this->course_id ),
			'1-2 前一章 1-1 未完成，應鎖定'
		);

		// And 第二章也應鎖定
		$this->assertFalse(
			ChapterUtils::is_chapter_unlocked( $this->ch2, $this->alice_id, $this->course_id ),
			'第二章在 1-2 鎖定時也應鎖定'
		);
	}

	/**
	 * @test
	 * @group edge
	 * Rule: 填補缺口後後續章節自動恢復
	 * Example: 完成 1-1 後，之前跳躍完成的 1-2 自動恢復為已完成狀態
	 */
	public function test_完成1_1後跳躍完成的1_2自動解鎖(): void {
		// Given 完成第一章、1-1、跳躍完成 1-2
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch1_1, $this->alice_id, '2025-06-02 10:00:00' );
		$this->set_chapter_finished( $this->ch1_2, $this->alice_id, '2025-06-01 12:00:00' );

		// When & Then 1-2 已解鎖（1-1 已完成）且其 finished_at 存在 → 狀態為「已完成」
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch1_2, $this->alice_id, $this->course_id ),
			'1-2 前一章 1-1 已完成，應解鎖'
		);

		// And 第二章也應解鎖
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( $this->ch2, $this->alice_id, $this->course_id ),
			'1-2 完成後第二章應解鎖'
		);
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 攤平列表為空時，所有章節視為已解鎖
	 */
	public function test_攤平列表為空時所有判定通過(): void {
		// Given 一門無章節的課程
		$empty_course_id = $this->create_course( [ 'post_title' => '無章節課程' ] );
		\wp_cache_delete( 'flatten_post_ids_' . $empty_course_id, 'prev_next' );

		// When & Then 任何章節 ID 對空課程都視為已解鎖
		$this->assertTrue(
			ChapterUtils::is_chapter_unlocked( 9999, $this->alice_id, $empty_course_id ),
			'攤平列表為空時應視為已解鎖'
		);
	}

	/**
	 * @test
	 * @group happy
	 * Rule: get_locked_chapter_ids 正確返回鎖定列表
	 */
	public function test_get_locked_chapter_ids_新學員返回除第一章外所有章節(): void {
		// Given 用戶未完成任何章節

		// When
		$locked_ids = ChapterUtils::get_locked_chapter_ids( $this->alice_id, $this->course_id );

		// Then 第一章不在鎖定列表中
		$this->assertNotContains( $this->ch1, $locked_ids, '第一章不應在鎖定列表中' );

		// And 其他章節都在鎖定列表中
		$this->assertContains( $this->ch1_1, $locked_ids, '1-1 應在鎖定列表中' );
		$this->assertContains( $this->ch1_2, $locked_ids, '1-2 應在鎖定列表中' );
		$this->assertContains( $this->ch2, $locked_ids, '第二章應在鎖定列表中' );
		$this->assertContains( $this->ch2_1, $locked_ids, '2-1 應在鎖定列表中' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: get_first_unlocked_chapter_id 新學員返回第一章
	 */
	public function test_get_first_unlocked_chapter_id_新學員返回第一章(): void {
		// Given 用戶未完成任何章節

		// When
		$target_id = ChapterUtils::get_first_unlocked_chapter_id( $this->alice_id, $this->course_id );

		// Then 應返回第一章
		$this->assertSame( $this->ch1, $target_id, '新學員導向目標應為第一章' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: get_first_unlocked_chapter_id 完成第一章後返回 1-1
	 */
	public function test_get_first_unlocked_chapter_id_完成第一章後返回1_1(): void {
		// Given Alice 完成第一章
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When
		$target_id = ChapterUtils::get_first_unlocked_chapter_id( $this->alice_id, $this->course_id );

		// Then 應返回 1-1
		$this->assertSame( $this->ch1_1, $target_id, '完成第一章後導向目標應為 1-1' );
	}
}
