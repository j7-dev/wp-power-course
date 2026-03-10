@ignore
Feature: 綁定課程到商品

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 系統中有以下 WooCommerce 商品：
      | productId | name           | type   |
      | 500       | 超值年費方案   | simple |
      | 501       | 銷售方案 Bundle | simple |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- product_id 對應的商品必須存在

    Example: 指定不存在的商品 id 時操作失敗
      When 管理員 "Admin" 綁定課程到商品 9999，bind_courses_data 如下：
        | course_id | limit_type |
        | 100       | unlimited  |
      Then 操作失敗，錯誤訊息包含 "找不到商品"

  Rule: 前置（狀態）- bind_courses_data 中每個 course_id 對應的課程必須存在

    Example: bind_courses_data 包含不存在的課程 id 時操作失敗
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 如下：
        | course_id | limit_type |
        | 9999      | unlimited  |
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  Rule: 前置（狀態）- bind_courses_data 中每個 course_id 對應的課程 _is_course 必須為 yes

    Example: bind_courses_data 包含非課程商品時操作失敗
      Given 系統中有一個 WooCommerce 商品 id 600，_is_course 為 "no"
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 如下：
        | course_id | limit_type |
        | 600       | unlimited  |
      Then 操作失敗，錯誤訊息包含 "_is_course"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- product_id 不可為空

    Example: 未提供 product_id 時操作失敗
      When 管理員 "Admin" 綁定課程到商品（未指定 product_id），bind_courses_data 如下：
        | course_id | limit_type |
        | 100       | unlimited  |
      Then 操作失敗，錯誤訊息包含 "product_id"

  Rule: 前置（參數）- bind_courses_data 不可為空陣列

    Example: 提供空的 bind_courses_data 時操作失敗
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 為空陣列
      Then 操作失敗，錯誤訊息包含 "bind_courses_data"

  Rule: 前置（參數）- 每項 limit_type 為 fixed 時 limit_value 不可為空

    Example: limit_type 為 fixed 但未提供 limit_value 時操作失敗
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 如下：
        | course_id | limit_type | limit_value | limit_unit |
        | 100       | fixed      |             | day        |
      Then 操作失敗，錯誤訊息包含 "limit_value"

  Rule: 前置（參數）- 每項 limit_type 為 fixed 時 limit_unit 不可為空

    Example: limit_type 為 fixed 但未提供 limit_unit 時操作失敗
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 如下：
        | course_id | limit_type | limit_value | limit_unit |
        | 100       | fixed      | 180         |            |
      Then 操作失敗，錯誤訊息包含 "limit_unit"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- bind_courses_data meta 儲存到商品

    Example: 成功綁定單一課程（永久存取）到商品
      When 管理員 "Admin" 綁定課程到商品 500，bind_courses_data 如下：
        | course_id | limit_type |
        | 100       | unlimited  |
      Then 操作成功
      And 商品 500 的 bind_courses_data meta 應包含 course_id 100
      And 商品 500 的 bind_courses_data meta 中 course_id 100 的 limit_type 應為 "unlimited"

  Rule: 後置（狀態）- 支援綁定多個課程到同一商品，各自設定不同期限

    Example: 成功綁定兩個課程並各自設定不同的 limit_type
      When 管理員 "Admin" 綁定課程到商品 501，bind_courses_data 如下：
        | course_id | limit_type | limit_value | limit_unit |
        | 100       | unlimited  |             |            |
        | 101       | fixed      | 365         | day        |
      Then 操作成功
      And 商品 501 的 bind_courses_data meta 應包含 2 筆課程綁定資料
      And 商品 501 的 bind_courses_data 中 course_id 101 的 limit_type 應為 "fixed"
      And 商品 501 的 bind_courses_data 中 course_id 101 的 limit_value 應為 365
      And 商品 501 的 bind_courses_data 中 course_id 101 的 limit_unit 應為 "day"
