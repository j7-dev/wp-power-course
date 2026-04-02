<?php
/**
 * 設定課程線性觀看 整合測試
 * Feature: specs/features/course/設定課程線性觀看.feature
 *
 * @group course
 * @group sequential
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\Resources\Course\MetaCRUD as AVLCourseMeta;

/**
 * Class CourseSequentialSettingTest
 * 測試課程線性觀看設定的業務邏輯
 *
 * 覆蓋 Feature: 設定課程線性觀看
 * is_sequential meta 使用 'yes'/'no' 字串格式，與 is_popular、is_free 一致。
 */
class CourseSequentialSettingTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int Admin 用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 使用 WordPress post meta APIs
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立管理員用戶
		$this->admin_id     = $this->factory()->user->create(
			[
				'user_login' => 'admin_seq_' . uniqid(),
				'user_email' => 'admin_seq_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;

		// 建立測試課程（is_sequential 預設不設定）
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		update_post_meta( $this->course_id, 'is_sequential', 'no' );
	}

	// ========== Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes ==========

	/**
	 * @test
	 * @group error
	 * Feature: 設定課程線性觀看
	 * Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes
	 * Example: 不存在的課程更新失敗
	 *
	 * TODO: [事件風暴部位: Command - 更新 is_sequential（不存在課程）]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_不存在的課程更新失敗(): void {
		// When 管理員 "Admin" 更新課程 9999 的 is_sequential 為 true
		// 呼叫 REST API: POST /power-course/v2/courses/9999

		// Then 操作失敗

		$this->markTestIncomplete('尚未實作：需要 Course API 驗證課程存在性');
	}

	// ========== Rule: 前置（參數）- is_sequential 必須為布林值 ==========

	/**
	 * @test
	 * @group error
	 * Feature: 設定課程線性觀看
	 * Rule: 前置（參數）- is_sequential 必須為布林值
	 * Example: is_sequential 為非布林值時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - 更新 is_sequential（無效參數）]
	 */
	public function test_is_sequential為非布林值時操作失敗(): void {
		// When 管理員 "Admin" 更新課程 100 的 is_sequential 為 "invalid"
		// 呼叫 REST API: POST /power-course/v2/courses/{course_id}，body: {is_sequential: "invalid"}

		// Then 操作失敗

		$this->markTestIncomplete('尚未實作：需要 Course API 驗證 is_sequential 參數格式');
	}

	// ========== Rule: 後置（狀態）- 成功開啟線性觀看 ==========

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 成功開啟線性觀看
	 * Example: 管理員開啟課程線性觀看
	 *
	 * TODO: [事件風暴部位: Command - 更新 is_sequential = true]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_管理員開啟課程線性觀看(): void {
		// When 管理員 "Admin" 更新課程的 is_sequential 為 true
		// 呼叫 REST API: POST /power-course/v2/courses/{course_id}，body: {is_sequential: true}

		// Then 操作成功
		// And 課程的 is_sequential 應為 'yes'（post meta）

		$this->markTestIncomplete('尚未實作：需要 Course API 支援 is_sequential 欄位更新');
	}

	// ========== Rule: 後置（狀態）- 成功關閉線性觀看 ==========

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 成功關閉線性觀看
	 * Example: 管理員關閉課程線性觀看
	 *
	 * TODO: [事件風暴部位: Command - 更新 is_sequential = false]
	 */
	public function test_管理員關閉課程線性觀看(): void {
		// Given 課程的 is_sequential 為 true
		update_post_meta( $this->course_id, 'is_sequential', 'yes' );

		// When 管理員 "Admin" 更新課程的 is_sequential 為 false
		// 呼叫 REST API: POST /power-course/v2/courses/{course_id}，body: {is_sequential: false}

		// Then 操作成功
		// And 課程的 is_sequential 應為 'no'（post meta）

		$this->markTestIncomplete('尚未實作：需要 Course API 支援 is_sequential 欄位更新');
	}

	// ========== Rule: 後置（狀態）- 新課程的 is_sequential 預設為 false ==========

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 新課程的 is_sequential 預設為 false
	 * Example: 新建課程預設不啟用線性觀看
	 *
	 * TODO: [事件風暴部位: Aggregate - 新建課程（is_sequential 預設值）]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_新建課程預設不啟用線性觀看(): void {
		// When 管理員 "Admin" 建立新課程 "React 實戰"
		$new_course_id = $this->create_course(
			[
				'post_title' => 'React 實戰',
				'_is_course' => 'yes',
			]
		);

		// Then 操作成功
		// And 新課程的 is_sequential 應為 false（meta 值為 'no' 或空）

		// 取得課程 is_sequential meta（透過 WooCommerce product）
		$product = wc_get_product( $new_course_id );

		$this->markTestIncomplete('尚未實作：需要 Course GET API 回傳 is_sequential 欄位，且預設為 no/false');
	}

	// ========== Rule: 後置（狀態）- 關閉線性觀看不影響學員既有進度 ==========

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 關閉線性觀看不影響學員既有進度
	 * Example: 關閉線性觀看後學員的完成紀錄保持不變
	 *
	 * TODO: [事件風暴部位: Command - 關閉 is_sequential + Aggregate - 驗證完成紀錄未變]
	 */
	public function test_關閉線性觀看後學員完成紀錄保持不變(): void {
		// Given 課程的 is_sequential 為 true
		update_post_meta( $this->course_id, 'is_sequential', 'yes' );

		// And 建立 Alice 用戶並加入課程
		$alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_setting_' . uniqid(),
				'user_email' => 'alice_setting_' . uniqid() . '@test.com',
			]
		);
		$this->enroll_user_to_course( $alice_id, $this->course_id, 0 );

		// And 建立兩個章節並設定 Alice 的完成紀錄
		$ch_200 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'post_parent' => $this->course_id,
				'menu_order'  => 1,
			]
		);
		$ch_201 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第二章',
				'post_parent' => $this->course_id,
				'menu_order'  => 2,
			]
		);
		$this->set_chapter_finished( $ch_200, $alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $ch_201, $alice_id, '2025-06-01 10:05:00' );

		// When 管理員 "Admin" 更新課程的 is_sequential 為 false
		update_post_meta( $this->course_id, 'is_sequential', 'no' );

		// Then 操作成功
		// And 用戶 "Alice" 在章節 200 的 finished_at 應不為空
		$finished_200 = $this->get_chapter_meta( $ch_200, $alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_200, '關閉線性觀看後章節 200 的 finished_at 應不為空' );

		// And 用戶 "Alice" 在章節 201 的 finished_at 應不為空
		$finished_201 = $this->get_chapter_meta( $ch_201, $alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_201, '關閉線性觀看後章節 201 的 finished_at 應不為空' );
	}
}
