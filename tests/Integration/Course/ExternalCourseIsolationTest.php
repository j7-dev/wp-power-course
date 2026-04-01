<?php
/**
 * 外部課程功能隔離整合測試
 * Feature: specs/features/external-course/外部課程功能隔離.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseIsolationTest
 * 測試外部課程不參與站內教學相關功能（學員管理、自動授權、章節管理、銷售方案）
 */
class ExternalCourseIsolationTest extends TestCase {

	/** @var int 外部課程 ID */
	private int $external_course_id;

	/** @var int 站內課程 ID */
	private int $simple_course_id;

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

		// 建立外部課程 200（Python 資料科學）
		$this->external_course_id = $this->create_external_course(
			[
				'post_title'   => 'Python 資料科學',
				'post_status'  => 'publish',
				'_is_course'   => 'yes',
				'external_url' => 'https://hahow.in/courses/12345',
			]
		);

		// 建立站內課程 100（PHP 基礎課）
		$this->simple_course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
				'price'       => '1200',
			]
		);
	}

	// ========== 學員管理隔離 ==========

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不出現在學員管理的課程篩選選項中
	 * Example: 學員管理頁面課程篩選不含外部課程
	 *
	 * TODO: [事件風暴部位: Query - GET /courses-options]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_外部課程功能隔離_學員管理課程篩選不含外部課程(): void {
		// Given 系統中有外部課程 200（Python 資料科學）和站內課程 100（PHP 基礎課）

		// When 管理員 "Admin" 取得學員管理的課程選項列表

		// Then 選項中應包含 "PHP 基礎課"
		// And 選項中不應包含 "Python 資料科學"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不可新增學員
	 * Example: 嘗試為外部課程新增學員時失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}/add-students]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_外部課程功能隔離_嘗試為外部課程新增學員時失敗(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 為課程 200 新增學員 user_ids [10]

		// Then 操作失敗

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 自動郵件隔離 ==========

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不可被選為自動郵件的目標課程
	 * Example: 建立郵件模板時課程選項不含外部課程
	 *
	 * TODO: [事件風暴部位: Query - GET /courses-options (for email)]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_外部課程功能隔離_郵件模板課程選項不含外部課程(): void {
		// Given 系統中有外部課程 200（Python 資料科學）和站內課程 100（PHP 基礎課）

		// When 管理員 "Admin" 取得郵件模板的課程選項列表

		// Then 選項中應包含 "PHP 基礎課"
		// And 選項中不應包含 "Python 資料科學"

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 自動授權隔離 ==========

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不可被加入自動授權課程清單
	 * Example: 設定自動授權課程時不可選擇外部課程
	 *
	 * TODO: [事件風暴部位: Command - POST /settings (auto_grant_course_ids)]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_外部課程功能隔離_設定自動授權課程時不可選擇外部課程(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新設定，auto_grant_course_ids 包含 [200]

		// Then 操作失敗，錯誤訊息包含 "external"

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 章節管理隔離 ==========

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不可新增章節
	 * Example: 嘗試為外部課程建立章節時失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /chapters (parent=外部課程)]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_外部課程功能隔離_嘗試為外部課程建立章節時失敗(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 為課程 200 建立章節，參數如下：
		//   | post_title |
		//   | 第一章     |

		// Then 操作失敗

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 銷售方案隔離 ==========

	/**
	 * Feature: 外部課程功能隔離
	 * Rule: 外部課程不可建立銷售方案
	 * Example: 嘗試為外部課程建立銷售方案時失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /bundle-products (parent=外部課程)]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_外部課程功能隔離_嘗試為外部課程建立銷售方案時失敗(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 為課程 200 建立銷售方案，參數如下：
		//   | name       | price |
		//   | 年度方案   | 3000  |

		// Then 操作失敗

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

		if ( isset( $args['external_url'] ) ) {
			update_post_meta( $course_id, '_product_url', $args['external_url'] );
		}

		return $course_id;
	}
}
