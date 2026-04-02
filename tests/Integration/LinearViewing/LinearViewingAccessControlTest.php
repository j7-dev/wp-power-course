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
				'post_title'        => 'PHP 基礎課',
				'_is_course'        => 'yes',
				'enable_sequential' => 'yes',
			]
		);
		// 注意：create_course 已呼叫 update_post_meta 設定 _is_course，
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

	// ========== 單層章節：第一個章節永遠可存取 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 第一個章節永遠可存取
	 * Example: 學員可存取第一個章節（無完成紀錄）
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_單層章節_第一個章節永遠可存取(): void {
		// Given 課程 100 有以下章節：
		// | chapterId | post_title | post_parent | menu_order |
		// | 200       | 1-1        | 100         | 1          |
		// | 201       | 1-2        | 100         | 2          |
		// | 202       | 1-3        | 100         | 3          |

		// When 用戶 "Alice" 存取章節 200

		// Then 存取成功

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 單層章節：未完成前一章節時不可存取後續章節 ==========

	/**
	 * @test
	 * @group access_denied
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 未完成前一章節時不可存取後續章節
	 * Example: 1-1 未完成時不可存取 1-2
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_單層章節_未完成1_1時不可存取1_2(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// | 201 | 1-2 | 100 | 2 |
		// | 202 | 1-3 | 100 | 3 |
		// And 用戶 "Alice" 在章節 200 無 finished_at

		// When 用戶 "Alice" 存取章節 201

		// Then 存取被拒絕
		// And 導向至章節 200

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * @test
	 * @group access_denied
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 未完成前一章節時不可存取後續章節
	 * Example: 1-1 已完成但 1-2 未完成時不可存取 1-3
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_單層章節_1_1完成但1_2未完成時不可存取1_3(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// | 201 | 1-2 | 100 | 2 |
		// | 202 | 1-3 | 100 | 3 |
		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		// And 用戶 "Alice" 在章節 201 無 finished_at

		// When 用戶 "Alice" 存取章節 202

		// Then 存取被拒絕
		// And 導向至章節 201

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 單層章節：完成前一章節後可存取下一個章節 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 單層章節 - 完成前一章節後可存取下一個章節
	 * Example: 完成 1-1 後可存取 1-2
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_單層章節_完成1_1後可存取1_2(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// | 201 | 1-2 | 100 | 2 |
		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"

		// When 用戶 "Alice" 存取章節 201

		// Then 存取成功

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 階層結構：父章節也在順序中，必須完成才能繼續 ==========

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 父章節「第一章」未完成時不可存取子章節 1-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_階層結構_父章節未完成時不可存取子章節(): void {
		// Given 課程 100 有以下章節：
		// | 300 | 第一章 | 100 | 1 |
		// | 301 | 1-1    | 300 | 1 |
		// | 302 | 1-2    | 300 | 2 |
		// | 310 | 第二章 | 100 | 2 |
		// | 311 | 2-1    | 310 | 1 |
		// And 用戶 "Alice" 在章節 300 無 finished_at

		// When 用戶 "Alice" 存取章節 301

		// Then 存取被拒絕
		// And 導向至章節 300

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 完成「第一章」→ 1-1 → 1-2 後可存取「第二章」
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_階層結構_完成所有子章節後可存取第二章(): void {
		// Given 課程 100 有以下章節：
		// | 300 | 第一章 | 100 | 1 |
		// | 301 | 1-1    | 300 | 1 |
		// | 302 | 1-2    | 300 | 2 |
		// | 310 | 第二章 | 100 | 2 |
		// | 311 | 2-1    | 310 | 1 |
		// And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
		// And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 11:00:00"
		// And 用戶 "Alice" 在章節 302 的 finished_at 為 "2025-06-01 12:00:00"

		// When 用戶 "Alice" 存取章節 310

		// Then 存取成功

		$this->markTestIncomplete('尚未實作');
	}

	/**
	 * @test
	 * @group hierarchy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續
	 * Example: 完成第一章的子章節但未完成「第二章」父章節時不可存取 2-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_階層結構_未完成父章節時不可存取子章節(): void {
		// Given 課程 100 有以下章節：
		// | 300 | 第一章 | 100 | 1 |
		// | 301 | 1-1    | 300 | 1 |
		// | 310 | 第二章 | 100 | 2 |
		// | 311 | 2-1    | 310 | 1 |
		// And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
		// And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 11:00:00"
		// And 用戶 "Alice" 在章節 310 無 finished_at

		// When 用戶 "Alice" 存取章節 311

		// Then 存取被拒絕
		// And 導向至章節 310

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 線性觀看關閉時：所有章節自由存取 ==========

	/**
	 * @test
	 * @group sequential_off
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 線性觀看關閉 - 所有章節自由存取
	 * Example: enable_sequential 為 no 時可自由存取任何章節
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_線性觀看關閉時所有章節可自由存取(): void {
		// Given 系統中有以下課程：
		// | 101 | 自由課程 | yes | publish | no |
		// And 課程 101 有以下章節：
		// | 400 | A-1 | 101 | 1 |
		// | 401 | A-2 | 101 | 2 |
		// | 402 | A-3 | 101 | 3 |
		// And 用戶 "Alice" 已被加入課程 101，expire_date 0
		// And 用戶 "Alice" 在章節 400 無 finished_at

		// When 用戶 "Alice" 存取章節 402

		// Then 存取成功

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 已完成章節可隨時回看 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 已完成的章節可隨時回看
	 * Example: 已完成 1-1 後仍可回看 1-1
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_已完成章節可隨時回看(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// | 201 | 1-2 | 100 | 2 |
		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"

		// When 用戶 "Alice" 存取章節 200

		// Then 存取成功

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 後端驗證：透過 URL 直接存取被鎖定章節時導向正確章節 ==========

	/**
	 * @test
	 * @group redirect
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 後端驗證 - 透過 URL 直接存取被鎖定章節時導向正確章節
	 * Example: 直接輸入網址存取鎖定章節時導向到目前進度章節
	 *
	 * TODO: [事件風暴部位: Query - get_current_progress_chapter_id]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_直接URL存取鎖定章節時導向目前進度章節(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// | 201 | 1-2 | 100 | 2 |
		// | 202 | 1-3 | 100 | 3 |
		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		// And 用戶 "Alice" 在章節 201 無 finished_at

		// When 用戶 "Alice" 直接透過 URL 存取章節 202

		// Then 導向至章節 201
		// And 顯示提示訊息「請先完成前面的章節，才能觀看此章節喔！」

		$this->markTestIncomplete('尚未實作');
	}

	// ========== 存取權限：無課程存取權時優先回傳存取權錯誤 ==========

	/**
	 * @test
	 * @group access_permission
	 *
	 * Feature: 線性觀看存取控制
	 * Rule: 存取權限 - 無課程存取權時優先回傳存取權錯誤
	 * Example: 無課程存取權時不檢查線性觀看
	 *
	 * TODO: [事件風暴部位: Query - is_chapter_accessible (with permission check)]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_無課程存取權時不檢查線性觀看(): void {
		// Given 課程 100 有以下章節：
		// | 200 | 1-1 | 100 | 1 |
		// And 用戶 "Bob" 未被加入課程 100

		// When 用戶 "Bob" 存取章節 200

		// Then 操作失敗，錯誤為「無此課程存取權」

		$this->markTestIncomplete('尚未實作');
	}
}
