<?php
/**
 * 刪除外部課程整合測試
 * Feature: specs/features/external-course/刪除外部課程.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseDeleteTest
 * 測試刪除外部課程的業務邏輯
 */
class ExternalCourseDeleteTest extends TestCase {

	/** @var int 外部課程 A 的 ID */
	private int $external_course_200;

	/** @var int 外部課程 B 的 ID */
	private int $external_course_201;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WordPress / WooCommerce APIs
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立外部課程 200
		$this->external_course_200 = $this->create_external_course(
			[
				'post_title'   => 'Python 資料科學',
				'post_status'  => 'publish',
				'_is_course'   => 'yes',
				'external_url' => 'https://hahow.in/courses/12345',
			]
		);

		// 建立外部課程 201
		$this->external_course_201 = $this->create_external_course(
			[
				'post_title'   => 'UX 設計入門',
				'post_status'  => 'draft',
				'_is_course'   => 'yes',
				'external_url' => 'https://pressplay.cc/courses/1',
			]
		);
	}

	// ========== 前置（參數）==========

	/**
	 * Feature: 刪除外部課程
	 * Rule: 前置（參數）- ids 不可為空陣列
	 * Example: 未提供 ids 時操作失敗
	 *
	 * TODO: [事件風暴部位: Command - DELETE /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_刪除外部課程_未提供ids時操作失敗(): void {
		// Given 系統中有管理員 "Admin"
		// And 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 刪除課程 ids []

		// Then 操作失敗，錯誤訊息包含 "ids"

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 後置（狀態）==========

	/**
	 * Feature: 刪除外部課程
	 * Rule: 後置（狀態）- 外部課程被刪除
	 * Example: 成功刪除單一外部課程
	 *
	 * TODO: [事件風暴部位: Command - DELETE /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_刪除外部課程_成功刪除單一外部課程(): void {
		// Given 系統中有管理員 "Admin"
		// And 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 刪除課程 ids [200]

		// Then 操作成功
		// And 課程 200 應不存在

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 刪除外部課程
	 * Rule: 後置（狀態）- 外部課程被刪除
	 * Example: 成功批量刪除多個外部課程
	 *
	 * TODO: [事件風暴部位: Command - DELETE /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_刪除外部課程_成功批量刪除多個外部課程(): void {
		// Given 系統中有管理員 "Admin"
		// And 系統中有外部課程 200（Python 資料科學）
		// And 系統中有外部課程 201（UX 設計入門）

		// When 管理員 "Admin" 刪除課程 ids [200, 201]

		// Then 操作成功
		// And 課程 200 應不存在
		// And 課程 201 應不存在

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== Helper Methods ==========

	/**
	 * 建立外部課程（WC_Product_External）
	 *
	 * @param array<string, mixed> $args 課程參數
	 * @return int 課程 ID
	 */
	private function create_external_course( array $args = [] ): int {
		$course_id = $this->create_course( $args );

		// 設定為 external 產品類型
		\wp_set_object_terms( $course_id, 'external', 'product_type' );

		// 設定 external URL
		if ( isset( $args['external_url'] ) ) {
			update_post_meta( $course_id, '_product_url', $args['external_url'] );
		}

		return $course_id;
	}
}
