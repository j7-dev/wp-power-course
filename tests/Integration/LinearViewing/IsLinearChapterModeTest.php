<?php
/**
 * 設定課程線性觀看模式 整合測試
 * Feature: specs/features/linear-viewing/設定課程線性觀看模式.feature
 *
 * @group linear-viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class IsLinearChapterModeTest
 * 測試課程線性觀看模式的設定與讀取
 */
class IsLinearChapterModeTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用靜態方法 CourseUtils::is_linear_chapter_mode()
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: 新課程預設不開啟線性觀看（postmeta 不存在 → 返回 false）
	 */
	public function test_新課程預設不開啟線性觀看(): void {
		// Given 系統中有一門課程（已在 set_up 中建立）

		// When 查詢課程的 is_linear_chapter_mode
		$result = CourseUtils::is_linear_chapter_mode( $this->course_id );

		// Then 結果應為 false（因 postmeta 不存在，wc_string_to_bool('') = false）
		$this->assertFalse( $result, '新課程預設 linear_chapter_mode 應為 false' );
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: postmeta 為 "yes" 時，is_linear_chapter_mode 應返回 true
	 */
	public function test_postmeta為yes時返回true(): void {
		// Given 課程的 linear_chapter_mode 設為 "yes"
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );

		// When 查詢課程的 is_linear_chapter_mode
		$result = CourseUtils::is_linear_chapter_mode( $this->course_id );

		// Then 結果應為 true
		$this->assertTrue( $result, 'linear_chapter_mode = yes 應返回 true' );
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: postmeta 為 "no" 時，is_linear_chapter_mode 應返回 false
	 */
	public function test_postmeta為no時返回false(): void {
		// Given 課程的 linear_chapter_mode 設為 "no"
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'no' );

		// When 查詢課程的 is_linear_chapter_mode
		$result = CourseUtils::is_linear_chapter_mode( $this->course_id );

		// Then 結果應為 false
		$this->assertFalse( $result, 'linear_chapter_mode = no 應返回 false' );
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: 管理員開啟線性觀看後 postmeta 確實更新
	 */
	public function test_管理員開啟線性觀看後postmeta更新(): void {
		// Given 課程的 linear_chapter_mode 初始為 "no"
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'no' );

		// When 更新課程的 linear_chapter_mode 為 "yes"（模擬管理員操作）
		try {
			\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );
			$this->lastError = null;
		} catch ( \Throwable $e ) {
			$this->lastError = $e;
		}

		// Then 操作成功
		$this->assert_operation_succeeded();

		// And 課程的 postmeta linear_chapter_mode 應為 "yes"
		$value = \get_post_meta( $this->course_id, 'linear_chapter_mode', true );
		$this->assertSame( 'yes', $value, 'postmeta 應為 yes' );

		// And is_linear_chapter_mode 應返回 true
		$this->assertTrue( CourseUtils::is_linear_chapter_mode( $this->course_id ) );
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: 管理員關閉線性觀看後 postmeta 確實更新
	 */
	public function test_管理員關閉線性觀看後postmeta更新(): void {
		// Given 課程的 linear_chapter_mode 為 "yes"
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );

		// When 更新課程的 linear_chapter_mode 為 "no"
		try {
			\update_post_meta( $this->course_id, 'linear_chapter_mode', 'no' );
			$this->lastError = null;
		} catch ( \Throwable $e ) {
			$this->lastError = $e;
		}

		// Then 操作成功
		$this->assert_operation_succeeded();

		// And 課程的 postmeta linear_chapter_mode 應為 "no"
		$value = \get_post_meta( $this->course_id, 'linear_chapter_mode', true );
		$this->assertSame( 'no', $value, 'postmeta 應為 no' );
	}

	/**
	 * @test
	 * @group happy
	 * Feature: 設定課程線性觀看模式
	 * Scenario: 關閉線性觀看後完成紀錄保留
	 */
	public function test_關閉線性觀看後完成紀錄保留(): void {
		// Given 課程的 linear_chapter_mode 為 "yes"
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );

		// And 建立章節並設定完成紀錄
		$chapter_id = $this->create_chapter( $this->course_id, [ 'post_title' => '1-1' ] );
		$alice_id   = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->set_chapter_finished( $chapter_id, $alice_id, '2025-06-01 10:00:00' );

		// When 關閉線性觀看
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'no' );

		// Then 章節完成紀錄應保留
		$finished_at = $this->get_chapter_meta( $chapter_id, $alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_at, '關閉線性觀看後，完成紀錄應保留不被清除' );
	}
}
