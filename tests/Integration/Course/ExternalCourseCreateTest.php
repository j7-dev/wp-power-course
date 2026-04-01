<?php
/**
 * 建立外部課程整合測試
 * Feature: specs/features/external-course/建立外部課程.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseCreateTest
 * 測試建立外部課程（WC_Product_External）的業務邏輯
 */
class ExternalCourseCreateTest extends TestCase {

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WordPress / WooCommerce APIs
	}

	// ========== 前置（參數）==========

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- name 不可為空
	 * Example: 未提供課程名稱時建立失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_建立外部課程_未提供課程名稱時建立失敗(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name | external_url                   | button_text |
		//   |      | https://hahow.in/courses/12345 | 前往 Hahow  |

		// Then 操作失敗，錯誤訊息包含 "name"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- external_url 為必填且必須為合法 URL
	 * Example: 未提供外部連結時建立失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_建立外部課程_未提供外部連結時建立失敗(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name              | external_url |
		//   | Python 資料科學   |              |

		// Then 操作失敗，錯誤訊息包含 "external_url"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- external_url 為必填且必須為合法 URL
	 * Example: 外部連結非合法 URL 時建立失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_建立外部課程_外部連結非合法URL時建立失敗(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name              | external_url       |
		//   | Python 資料科學   | not-a-valid-url    |

		// Then 操作失敗，錯誤訊息包含 "external_url"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- external_url 為必填且必須為合法 URL
	 * Example: 外部連結不是 http/https 開頭時建立失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_建立外部課程_外部連結不是http_https開頭時建立失敗(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name              | external_url              |
		//   | Python 資料科學   | ftp://example.com/course  |

		// Then 操作失敗，錯誤訊息包含 "external_url"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- button_text 為選填，未填時預設為「前往課程」
	 * Example: 未提供按鈕文字時使用預設值
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_建立外部課程_未提供按鈕文字時使用預設值(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name              | external_url                   |
		//   | Python 資料科學   | https://hahow.in/courses/12345 |

		// Then 操作成功
		// And 新建課程的 button_text 應為 "前往課程"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 前置（參數）- price 為選填（展示用），若有設定須為非負數
	 * Example: 設定負數 price 時建立失敗
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.success-failure 實作 Then
	 */
	public function test_建立外部課程_設定負數price時建立失敗(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name              | external_url                   | price |
		//   | Python 資料科學   | https://hahow.in/courses/12345 | -100  |

		// Then 操作失敗，錯誤訊息包含 "price"

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== 後置（狀態）==========

	/**
	 * Feature: 建立外部課程
	 * Rule: 後置（狀態）- 成功建立 WooCommerce external 商品並設 _is_course 為 yes
	 * Example: 成功建立基本外部課程
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_建立外部課程_成功建立基本外部課程(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name            | status  | external_url                   | button_text | description          |
		//   | Python 資料科學 | publish | https://hahow.in/courses/12345 | 前往 Hahow  | Hahow 熱門課程       |

		// Then 操作成功
		// And 新建課程的 _is_course meta 應為 "yes"
		// And 新建課程的 product_type 應為 "external"
		// And 新建課程的 product_url 應為 "https://hahow.in/courses/12345"
		// And 新建課程的 button_text 應為 "前往 Hahow"
		// And 新建課程的 status 應為 "publish"
		// And 回應中包含新建課程的 id（正整數）

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 後置（狀態）- 外部課程的 is_purchasable 為 false
	 * Example: 外部課程不可購買
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_建立外部課程_外部課程不可購買(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name            | external_url                   |
		//   | Python 資料科學 | https://hahow.in/courses/12345 |

		// Then 操作成功
		// And 新建課程 WC_Product 的 is_purchasable() 應為 false

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 後置（狀態）- 外部課程可設定展示用價格
	 * Example: 建立含展示價格的外部課程
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_建立外部課程_建立含展示價格的外部課程(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name            | external_url                   | price | regular_price |
		//   | Python 資料科學 | https://hahow.in/courses/12345 | 2400  | 3000          |

		// Then 操作成功
		// And 新建課程的 price 應為 "2400"
		// And 新建課程的 regular_price 應為 "3000"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 後置（狀態）- 外部課程可設定分類與標籤
	 * Example: 建立外部課程並指定分類
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given（分類）
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_建立外部課程_建立外部課程並指定分類(): void {
		// Given 系統中有以下商品分類：
		//   | termId | name     | slug         |
		//   | 50     | 程式設計 | programming  |

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name            | external_url                   | category_ids |
		//   | Python 資料科學 | https://hahow.in/courses/12345 | 50           |

		// Then 操作成功
		// And 新建課程應屬於分類 "程式設計"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 建立外部課程
	 * Rule: 後置（狀態）- 回傳新建課程的完整資料（含 ID 與外部課程欄位）
	 * Example: 建立課程後回應包含完整課程資料
	 *
	 * TODO: [事件風暴部位: Command - POST /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_建立外部課程_建立課程後回應包含完整課程資料(): void {
		// Given 系統中有管理員 "Admin"

		// When 管理員 "Admin" 建立外部課程，參數如下：
		//   | name            | status  | external_url                       | button_text    | price |
		//   | UX 設計入門     | draft   | https://pressplay.cc/courses/99999 | 前往 PressPlay | 1800  |

		// Then 操作成功
		// And 回應資料應包含以下欄位：
		//   | 欄位            | 期望值                               |
		//   | name            | UX 設計入門                          |
		//   | status          | draft                                |
		//   | product_type    | external                             |
		//   | external_url    | https://pressplay.cc/courses/99999   |
		//   | button_text     | 前往 PressPlay                       |
		//   | price           | 1800                                 |
		//   | _is_course      | yes                                  |

		$this->markTestIncomplete( '尚未實作' );
	}
}
