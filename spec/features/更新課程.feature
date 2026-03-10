@ignore
Feature: 更新課程

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email            | role          |
      | 1      | Admin   | admin@test.com   | administrator |
      | 10     | Teacher | teacher@test.com | editor        |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  | price | limit_type |
      | 100      | PHP 基礎入門 | yes        | publish | 1200  | unlimited  |
      | 101      | React 實戰課 | yes        | draft   | 2000  | fixed      |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 指定 id 對應的課程必須存在

    Example: 更新不存在的課程時操作失敗
      When 管理員 "Admin" 更新課程 9999，參數如下：
        | name       |
        | 不存在的課程 |
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  Rule: 前置（狀態）- 指定課程的 _is_course 必須為 yes

    Example: 更新非課程商品時操作失敗
      Given 系統中有一個 WooCommerce 商品 id 200，_is_course 為 "no"
      When 管理員 "Admin" 更新課程 200，參數如下：
        | name      |
        | 一般商品   |
      Then 操作失敗，錯誤訊息包含 "_is_course"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- id 不可為空

    Example: 未提供課程 id 時操作失敗
      When 管理員 "Admin" 更新課程（未指定 id），參數如下：
        | name       |
        | 更新後課程名 |
      Then 操作失敗

  Rule: 前置（參數）- id 必須為正整數

    Example: 提供非正整數 id 時操作失敗
      When 管理員 "Admin" 更新課程 -1，參數如下：
        | name       |
        | 更新後課程名 |
      Then 操作失敗

  Rule: 前置（參數）- limit_type 為 fixed 時 limit_value 不可為空

    Example: 將 limit_type 改為 fixed 但未提供 limit_value 時操作失敗
      When 管理員 "Admin" 更新課程 100，參數如下：
        | limit_type | limit_value | limit_unit |
        | fixed      |             | day        |
      Then 操作失敗，錯誤訊息包含 "limit_value"

  Rule: 前置（參數）- price 若有設定須為非負數

    Example: 將 price 設為負數時操作失敗
      When 管理員 "Admin" 更新課程 100，參數如下：
        | price |
        | -500  |
      Then 操作失敗，錯誤訊息包含 "price"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 觸發 power_course_before_update_product_meta action

    Example: 更新課程 meta 前觸發 action hook
      When 管理員 "Admin" 更新課程 100，參數如下：
        | name         | price |
        | PHP 基礎入門 Pro | 1500  |
      Then 操作成功
      And action "power_course_before_update_product_meta" 應被觸發，參數包含 product 及 meta_data

  Rule: 後置（狀態）- teacher_ids 先 delete_meta_data 再以 add_meta_data loop 儲存

    Example: 更新講師清單時使用多筆 meta rows
      When 管理員 "Admin" 更新課程 100，參數如下：
        | teacher_ids |
        | 10          |
      Then 操作成功
      And 課程 100 的舊有 teacher_ids meta rows 應已清除
      And 課程 100 應有 1 筆 teacher_ids meta row，值為 10

  Rule: 後置（狀態）- 僅更新有傳入的欄位，其他欄位保持不變

    Example: 只更新課程名稱時其他欄位不受影響
      When 管理員 "Admin" 更新課程 100，參數如下：
        | name             |
        | PHP 基礎入門（更新版）|
      Then 操作成功
      And 課程 100 的 name 應為 "PHP 基礎入門（更新版）"
      And 課程 100 的 price 應保持 1200
      And 課程 100 的 limit_type 應保持 "unlimited"
