@ignore @command
Feature: 更新外部課程

  管理員可以更新外部課程的名稱、介紹、封面圖、外部連結、按鈕文字、展示價格等欄位。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下外部課程：
      | courseId | name            | _is_course | product_type | status  | external_url                   | button_text | price |
      | 200      | Python 資料科學 | yes        | external     | publish | https://hahow.in/courses/12345 | 前往 Hahow  | 2400  |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 課程必須存在且為外部課程

    Example: 不存在的課程更新失敗
      When 管理員 "Admin" 更新外部課程 9999，參數如下：
        | name     |
        | 新名稱   |
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- external_url 若有更新須為合法 URL

    Example: 更新外部連結為非法 URL 時失敗
      When 管理員 "Admin" 更新外部課程 200，參數如下：
        | external_url    |
        | not-a-valid-url |
      Then 操作失敗，錯誤訊息包含 "external_url"

  Rule: 前置（參數）- external_url 不可設為空（外部課程必須有連結）

    Example: 清空外部連結時失敗
      When 管理員 "Admin" 更新外部課程 200，參數如下：
        | external_url |
        |              |
      Then 操作失敗，錯誤訊息包含 "external_url"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功更新外部課程欄位

    Example: 成功更新外部連結與按鈕文字
      When 管理員 "Admin" 更新外部課程 200，參數如下：
        | external_url                         | button_text      |
        | https://hahow.in/courses/67890       | 前往 Hahow 上課  |
      Then 操作成功
      And 課程 200 的 product_url 應為 "https://hahow.in/courses/67890"
      And 課程 200 的 button_text 應為 "前往 Hahow 上課"

    Example: 成功更新課程名稱與展示價格
      When 管理員 "Admin" 更新外部課程 200，參數如下：
        | name                  | price | regular_price |
        | Python 資料科學（進階）| 2800  | 3500          |
      Then 操作成功
      And 課程 200 的 name 應為 "Python 資料科學（進階）"
      And 課程 200 的 price 應為 "2800"

  Rule: 後置（狀態）- 更新後 product_type 仍為 external

    Example: 更新不會改變產品類型
      When 管理員 "Admin" 更新外部課程 200，參數如下：
        | name     |
        | 新名稱   |
      Then 操作成功
      And 課程 200 的 product_type 應為 "external"
