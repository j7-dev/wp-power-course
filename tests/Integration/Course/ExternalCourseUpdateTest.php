<?php
/**
 * 更新外部課程整合測試
 * Feature: specs/features/external-course/更新外部課程.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseUpdateTest
 * 測試更新外部課程欄位的業務邏輯
 */
class ExternalCourseUpdateTest extends TestCase {

	/** @var int 測試外部課程 ID */
	private int $external_course_id;

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

		// 建立外部課程（courseId=200）
		$this->external_course_id = $this->create_external_course(
			[
				'post_title'   => 'Python 資料科學',
				'post_status'  => 'publish',
				'_is_course'   => 'yes',
				'external_url' => 'https://hahow.in/courses/12345',
				'button_text'  => '前往 Hahow',
				'price'        => '2400',
			]
		);
	}

	// ========== 前置（狀態）==========

	/**
	 * Feature: 更新外部課程
	 * Rule: 前置（狀態）- 課程必須存在且為外部課程
	 * Example: 不存在的課程更新失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_更新外部課程_不存在的課程更新失敗(): void {
		// Given 系統中有管理員 "Admin"
		// And 課程 9999 不存在

		// When 管理員 "Admin" 更新外部課程 9999，參數如下：
		//   | name     |
		//   | 新名稱   |

		// Then 操作失敗

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 前置（參數）==========

	/**
	 * Feature: 更新外部課程
	 * Rule: 前置（參數）- external_url 若有更新須為合法 URL
	 * Example: 更新外部連結為非法 URL 時失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_更新外部課程_更新外部連結為非法URL時失敗(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新外部課程 200，參數如下：
		//   | external_url    |
		//   | not-a-valid-url |

		// Then 操作失敗，錯誤訊息包含 "external_url"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 更新外部課程
	 * Rule: 前置（參數）- external_url 不可設為空（外部課程必須有連結）
	 * Example: 清空外部連結時失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_更新外部課程_清空外部連結時失敗(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新外部課程 200，參數如下：
		//   | external_url |
		//   |              |

		// Then 操作失敗，錯誤訊息包含 "external_url"

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 後置（狀態）==========

	/**
	 * Feature: 更新外部課程
	 * Rule: 後置（狀態）- 成功更新外部課程欄位
	 * Example: 成功更新外部連結與按鈕文字
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_更新外部課程_成功更新外部連結與按鈕文字(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新外部課程 200，參數如下：
		//   | external_url                         | button_text      |
		//   | https://hahow.in/courses/67890       | 前往 Hahow 上課  |

		// Then 操作成功
		// And 課程 200 的 product_url 應為 "https://hahow.in/courses/67890"
		// And 課程 200 的 button_text 應為 "前往 Hahow 上課"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 更新外部課程
	 * Rule: 後置（狀態）- 成功更新外部課程欄位
	 * Example: 成功更新課程名稱與展示價格
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_更新外部課程_成功更新課程名稱與展示價格(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新外部課程 200，參數如下：
		//   | name                  | price | regular_price |
		//   | Python 資料科學（進階）| 2800  | 3500          |

		// Then 操作成功
		// And 課程 200 的 name 應為 "Python 資料科學（進階）"
		// And 課程 200 的 price 應為 "2800"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 更新外部課程
	 * Rule: 後置（狀態）- 更新後 product_type 仍為 external
	 * Example: 更新不會改變產品類型
	 *
	 * TODO: [事件風暴部位: Command - POST /courses/{id}]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_更新外部課程_更新不會改變產品類型(): void {
		// Given 系統中有外部課程 200（Python 資料科學）

		// When 管理員 "Admin" 更新外部課程 200，參數如下：
		//   | name     |
		//   | 新名稱   |

		// Then 操作成功
		// And 課程 200 的 product_type 應為 "external"

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

		// 設定 external URL 和 button text
		if ( isset( $args['external_url'] ) ) {
			update_post_meta( $course_id, '_product_url', $args['external_url'] );
		}
		if ( isset( $args['button_text'] ) ) {
			update_post_meta( $course_id, '_button_text', $args['button_text'] );
		}

		return $course_id;
	}
}
