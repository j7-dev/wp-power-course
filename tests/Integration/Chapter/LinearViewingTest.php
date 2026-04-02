<?php
/**
 * 課程線性觀看功能 整合測試
 * Feature: specs/features/linear-viewing/chapter-lock.feature
 *          specs/features/linear-viewing/backward-compat.feature
 *          specs/features/linear-viewing/admin-setting.feature
 *
 * @group chapter
 * @group linear-viewing
 */

declare( strict_types=1 );

namespace Tests\Integration\Chapter;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

/**
 * Class LinearViewingTest
 * 測試課程線性觀看功能的核心鎖定邏輯
 */
class LinearViewingTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var array<string, int> 章節 ID 映射 (標題 => ID) */
	private array $chapter_ids;

	/** @var int 一般學員 ID */
	private int $student_id;

	/** @var int 管理員 ID */
	private int $admin_id;

	/**
	 * 初始化依賴（不需要額外服務）
	 */
	protected function configure_dependencies(): void {
		// 無額外服務依賴
	}

	/**
	 * 每個測試前建立背景資料：
	 * - 課程「PHP 基礎課」包含章節 1-1, 1-2, 1-3, 2-1（按 menu_order 排序）
	 * - 學員「小明」已購買此課程
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立管理員
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		// 給予 manage_woocommerce 能力
		$admin = get_user_by( 'ID', $this->admin_id );
		if ( $admin ) {
			$admin->add_cap( 'manage_woocommerce' );
		}
		$this->ids['Admin'] = $this->admin_id;

		// 建立學員
		$this->student_id = $this->factory()->user->create(
			[
				'user_login' => 'student_' . uniqid(),
				'user_email' => 'student_' . uniqid() . '@test.com',
				'role'       => 'subscriber',
			]
		);
		$this->ids['Student'] = $this->student_id;

		// 建立課程
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
			]
		);

		// 建立章節（按 menu_order 排序：1-1=10, 1-2=20, 1-3=30, 2-1=40）
		$this->chapter_ids = [];

		$this->chapter_ids['1-1'] = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '章節 1-1',
				'menu_order'  => 10,
			]
		);

		$this->chapter_ids['1-2'] = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '章節 1-2',
				'menu_order'  => 20,
			]
		);

		$this->chapter_ids['1-3'] = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '章節 1-3',
				'menu_order'  => 30,
			]
		);

		$this->chapter_ids['2-1'] = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '章節 2-1',
				'menu_order'  => 40,
			]
		);

		// 將學員加入課程
		$this->enroll_user_to_course( $this->student_id, $this->course_id );

		// 清除快取
		wp_cache_flush();
	}

	// ========== 輔助方法 ==========

	/**
	 * 開啟課程線性觀看模式
	 */
	private function enable_linear_mode(): void {
		update_post_meta( $this->course_id, 'enable_linear_mode', 'yes' );
	}

	/**
	 * 關閉課程線性觀看模式
	 */
	private function disable_linear_mode(): void {
		update_post_meta( $this->course_id, 'enable_linear_mode', 'no' );
	}

	// ========== 測試：admin-setting.feature ==========

	/**
	 * 場景: 線性觀看開關預設為關閉
	 * 當 管理員建立一個新課程
	 * 那麼 「線性觀看」開關預設為關閉
	 */
	public function test_linear_mode_default_is_disabled(): void {
		$value = get_post_meta( $this->course_id, 'enable_linear_mode', true );
		// 預設應為空字串或 'no'，不能是 'yes'
		$this->assertNotSame( 'yes', $value, '線性觀看模式預設應為關閉' );
	}

	/**
	 * 場景: 開啟線性觀看模式
	 * 當 管理員開啟並儲存線性觀看開關
	 * 那麼 課程的 meta enable_linear_mode 值為 "yes"
	 */
	public function test_enable_linear_mode_saves_meta(): void {
		$this->enable_linear_mode();
		$value = get_post_meta( $this->course_id, 'enable_linear_mode', true );
		$this->assertSame( 'yes', $value, '開啟後 enable_linear_mode 應為 yes' );
	}

	/**
	 * 場景: 關閉線性觀看模式
	 * 那麼 課程的 meta enable_linear_mode 值為 "no"
	 */
	public function test_disable_linear_mode_saves_meta(): void {
		$this->enable_linear_mode();
		$this->disable_linear_mode();
		$value = get_post_meta( $this->course_id, 'enable_linear_mode', true );
		$this->assertSame( 'no', $value, '關閉後 enable_linear_mode 應為 no' );
	}

	// ========== 測試：chapter-lock.feature — is_chapter_locked() ==========

	/**
	 * 場景: 未開啟線性觀看時，所有章節應為解鎖
	 */
	public function test_all_chapters_unlocked_when_linear_mode_disabled(): void {
		// 未開啟線性模式
		foreach ( $this->chapter_ids as $key => $chapter_id ) {
			$locked = ChapterUtils::is_chapter_locked( $chapter_id, $this->course_id, $this->student_id );
			$this->assertFalse( $locked, "線性模式關閉時章節 {$key} 不應鎖定" );
		}
	}

	/**
	 * 場景: 第一個章節永遠解鎖
	 */
	public function test_first_chapter_always_unlocked(): void {
		$this->enable_linear_mode();

		$locked = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-1'],
			$this->course_id,
			$this->student_id
		);
		$this->assertFalse( $locked, '第一個章節（1-1）永遠應解鎖' );
	}

	/**
	 * 場景: 開啟線性模式時，未完成前置章節的後續章節應被鎖定
	 */
	public function test_subsequent_chapters_locked_when_no_progress(): void {
		$this->enable_linear_mode();

		// 1-2, 1-3, 2-1 應被鎖定（前一章節未完成）
		$locked_1_2 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-2'],
			$this->course_id,
			$this->student_id
		);
		$locked_1_3 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-3'],
			$this->course_id,
			$this->student_id
		);
		$locked_2_1 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['2-1'],
			$this->course_id,
			$this->student_id
		);

		$this->assertTrue( $locked_1_2, '章節 1-2 在 1-1 未完成時應被鎖定' );
		$this->assertTrue( $locked_1_3, '章節 1-3 在 1-2 未完成時應被鎖定' );
		$this->assertTrue( $locked_2_1, '章節 2-1 在 1-3 未完成時應被鎖定' );
	}

	/**
	 * 場景: 完成章節後解鎖下一章節
	 * 假設 小明已完成章節 "1-1"
	 * 那麼 章節 "1-2" 顯示為可觀看（已解鎖）
	 */
	public function test_next_chapter_unlocked_after_completing_previous(): void {
		$this->enable_linear_mode();

		// 小明完成 1-1
		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		wp_cache_flush();

		$locked_1_1 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-1'],
			$this->course_id,
			$this->student_id
		);
		$locked_1_2 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-2'],
			$this->course_id,
			$this->student_id
		);
		$locked_1_3 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['1-3'],
			$this->course_id,
			$this->student_id
		);

		$this->assertFalse( $locked_1_1, '已完成的章節 1-1 不應鎖定' );
		$this->assertFalse( $locked_1_2, '章節 1-2 在 1-1 完成後應解鎖' );
		$this->assertTrue( $locked_1_3, '章節 1-3 在 1-2 未完成時仍應鎖定' );
	}

	/**
	 * 場景: 依序完成多個章節後的解鎖狀態
	 * 假設 小明已完成章節 "1-1", "1-2", "1-3"
	 * 那麼 章節 "2-1" 顯示為可觀看（已解鎖）
	 */
	public function test_unlock_propagates_through_chain(): void {
		$this->enable_linear_mode();

		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['1-2'], $this->student_id, '2026-01-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['1-3'], $this->student_id, '2026-01-01 12:00:00' );
		wp_cache_flush();

		$locked_2_1 = ChapterUtils::is_chapter_locked(
			$this->chapter_ids['2-1'],
			$this->course_id,
			$this->student_id
		);

		$this->assertFalse( $locked_2_1, '章節 2-1 在 1-3 完成後應解鎖' );
	}

	/**
	 * 場景: 全部完成後所有章節可自由存取
	 */
	public function test_all_chapters_accessible_after_completing_all(): void {
		$this->enable_linear_mode();

		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['1-2'], $this->student_id, '2026-01-01 11:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['1-3'], $this->student_id, '2026-01-01 12:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['2-1'], $this->student_id, '2026-01-01 13:00:00' );
		wp_cache_flush();

		foreach ( $this->chapter_ids as $key => $chapter_id ) {
			$locked = ChapterUtils::is_chapter_locked( $chapter_id, $this->course_id, $this->student_id );
			$this->assertFalse( $locked, "全部完成後章節 {$key} 應解鎖" );
		}
	}

	// ========== 測試：backward-compat.feature — 管理員繞過 ==========

	/**
	 * 場景: 管理員繞過鎖定限制
	 * 用戶具有 manage_woocommerce 權限時，不受線性鎖定限制
	 */
	public function test_admin_bypasses_linear_lock(): void {
		$this->enable_linear_mode();

		// 管理員未完成任何章節，仍不應被鎖定
		foreach ( $this->chapter_ids as $key => $chapter_id ) {
			$locked = ChapterUtils::is_chapter_locked( $chapter_id, $this->course_id, $this->admin_id );
			$this->assertFalse( $locked, "管理員不應受章節 {$key} 的線性鎖定限制" );
		}
	}

	// ========== 測試：backward-compat.feature — 已完成章節不被重新鎖定 ==========

	/**
	 * 場景: 中途開啟線性觀看 — 已完成的章節不被重新鎖定
	 * 假設 小明已完成章節 "1-1" 和 "1-3"（跳過了 "1-2"）
	 * 那麼 章節 "1-3" 顯示已完成，可存取（已完成的章節不被鎖定）
	 */
	public function test_already_completed_chapters_not_relocked_when_enabling_linear_mode(): void {
		// 先讓小明跳著完成 1-1 和 1-3（未完成 1-2）
		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		$this->set_chapter_finished( $this->chapter_ids['1-3'], $this->student_id, '2026-01-01 12:00:00' );

		// 開啟線性模式
		$this->enable_linear_mode();
		wp_cache_flush();

		// 1-1 完成，應解鎖
		$locked_1_1 = ChapterUtils::is_chapter_locked( $this->chapter_ids['1-1'], $this->course_id, $this->student_id );
		// 1-2 未完成，但 1-1 完成了，所以 1-2 解鎖
		$locked_1_2 = ChapterUtils::is_chapter_locked( $this->chapter_ids['1-2'], $this->course_id, $this->student_id );
		// 1-3 已完成，不應被鎖定（已完成的章節不鎖定）
		$locked_1_3 = ChapterUtils::is_chapter_locked( $this->chapter_ids['1-3'], $this->course_id, $this->student_id );
		// 2-1 未完成，且 1-2 未完成（雖然 1-3 完成了），2-1 應被鎖定
		$locked_2_1 = ChapterUtils::is_chapter_locked( $this->chapter_ids['2-1'], $this->course_id, $this->student_id );

		$this->assertFalse( $locked_1_1, '已完成的章節 1-1 不應鎖定' );
		$this->assertFalse( $locked_1_2, '章節 1-2 因 1-1 已完成所以應解鎖' );
		$this->assertFalse( $locked_1_3, '已完成的章節 1-3 不應被重新鎖定' );
		$this->assertTrue( $locked_2_1, '章節 2-1 的前一章 1-3 雖然完成，但 1-2 未完成，2-1 仍應鎖定' );
	}

	// ========== 測試：get_required_chapter_title() ==========

	/**
	 * 測試 get_required_chapter_title 回傳前一章節標題
	 * 章節 1-2 需要先完成 1-1
	 */
	public function test_get_required_chapter_title_returns_previous_chapter_title(): void {
		$this->enable_linear_mode();
		wp_cache_flush();

		$required_title = ChapterUtils::get_required_chapter_title(
			$this->chapter_ids['1-2'],
			$this->course_id,
			$this->student_id
		);

		$this->assertSame( '章節 1-1', $required_title, '章節 1-2 需要先完成章節 1-1' );
	}

	/**
	 * 測試 get_required_chapter_title 在第一個章節時回傳 null
	 */
	public function test_get_required_chapter_title_returns_null_for_first_chapter(): void {
		$this->enable_linear_mode();

		$required_title = ChapterUtils::get_required_chapter_title(
			$this->chapter_ids['1-1'],
			$this->course_id,
			$this->student_id
		);

		$this->assertNull( $required_title, '第一個章節不需要前置章節，應回傳 null' );
	}

	/**
	 * 測試 get_required_chapter_title 在未開啟線性模式時回傳 null
	 */
	public function test_get_required_chapter_title_returns_null_when_linear_mode_disabled(): void {
		// 未開啟線性模式
		$required_title = ChapterUtils::get_required_chapter_title(
			$this->chapter_ids['1-3'],
			$this->course_id,
			$this->student_id
		);

		$this->assertNull( $required_title, '未開啟線性模式時，應回傳 null' );
	}

	/**
	 * 測試 get_required_chapter_title 在章節已解鎖時回傳 null
	 */
	public function test_get_required_chapter_title_returns_null_when_chapter_unlocked(): void {
		$this->enable_linear_mode();
		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		wp_cache_flush();

		// 1-2 已解鎖（1-1 已完成），不需要前置章節提示
		$required_title = ChapterUtils::get_required_chapter_title(
			$this->chapter_ids['1-2'],
			$this->course_id,
			$this->student_id
		);

		$this->assertNull( $required_title, '章節已解鎖時，get_required_chapter_title 應回傳 null' );
	}

	// ========== 測試：向下相容 — 關閉線性模式立即恢復 ==========

	/**
	 * 場景: 關閉線性觀看後所有章節立即恢復自由觀看
	 */
	public function test_disabling_linear_mode_immediately_unlocks_all_chapters(): void {
		$this->enable_linear_mode();

		// 小明只完成了 1-1
		$this->set_chapter_finished( $this->chapter_ids['1-1'], $this->student_id, '2026-01-01 10:00:00' );
		wp_cache_flush();

		// 關閉線性模式
		$this->disable_linear_mode();

		// 所有章節應解鎖
		foreach ( $this->chapter_ids as $key => $chapter_id ) {
			$locked = ChapterUtils::is_chapter_locked( $chapter_id, $this->course_id, $this->student_id );
			$this->assertFalse( $locked, "關閉線性模式後章節 {$key} 應立即解鎖" );
		}
	}
}
