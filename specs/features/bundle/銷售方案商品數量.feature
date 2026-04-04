@ignore @command
Feature: 銷售方案商品數量

  銷售方案中每個商品可自由設定數量（1~999 正整數）。
  數量影響：組合原價計算、訂單 line item qty、庫存扣除。
  課程開通維持去重機制（同用戶 + 同課程不重複開通）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | customer      |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price | price |
      | 100      | PHP 基礎課 | yes        | publish | 1000          | 1000  |
    And 系統中有以下 WooCommerce 商品：
      | productId | name    | regular_price | price | stock_quantity |
      | 200       | T-shirt | 500           | 500   | 50             |
    And 系統設定 course_access_trigger 為 "completed"

  # ========== 後置（狀態）— 儲存數量 ==========

  Rule: 後置（狀態）- 建立銷售方案時可設定各商品數量

    Example: 建立含數量的銷售方案
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name     | link_course_id | price | bundle_type |
        | 年度套餐 | 100            | 2999  | bundle      |
      And 銷售方案的 pbp_product_ids 如下：
        | product_id |
        | 100        |
        | 200        |
      And 銷售方案的 pbp_product_quantities 如下：
        | product_id | qty |
        | 100        | 2   |
        | 200        | 3   |
      Then 操作成功
      And 銷售方案的 pbp_product_quantities meta 應為 {"100": 2, "200": 3}

  Rule: 後置（狀態）- 更新銷售方案時可修改各商品數量

    Example: 修改既有銷售方案的商品數量
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities  |
        | 300       | 年度套餐 | bundle      | 100            | 100,200         | {"100": 1, "200": 1}    |
      When 管理員 "Admin" 更新銷售方案 300 的 pbp_product_quantities 為：
        | product_id | qty |
        | 100        | 2   |
        | 200        | 5   |
      Then 操作成功
      And 銷售方案 300 的 pbp_product_quantities meta 應為 {"100": 2, "200": 5}

  # ========== 後置（狀態）— 未設定數量的預設值 ==========

  Rule: 後置（狀態）- 未設定 pbp_product_quantities 的商品預設數量為 1

    Example: 舊資料沒有 pbp_product_quantities 時，所有商品數量視為 1
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 300       | 月費方案 | bundle      | 100            | 100,200         |
      When 查詢銷售方案 300 的商品 200 數量
      Then 數量應為 1

  # ========== 後置（狀態）— 組合原價計算 ==========

  Rule: 後置（狀態）- 組合原價 = 各商品 regular_price × 數量的總和（前端自動計算，可手動覆蓋）

    Example: 組合原價依數量計算
      Given 銷售方案包含以下商品與數量：
        | product_id | regular_price | qty |
        | 100        | 1000          | 2   |
        | 200        | 500           | 3   |
      Then 組合原價應為 3500

  # ========== 後置（狀態）— 訂單展開與庫存 ==========

  Rule: 後置（狀態）- 購買銷售方案後，展開 line item 時 qty = 購買份數 × 商品數量

    Example: 購買 1 份含多數量的銷售方案
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 300       | 年度套餐 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買商品 300，數量 1
      When WooCommerce 訂單 "ORDER-1" 建立
      Then 訂單 "ORDER-1" 應包含 line item "年度套餐 - PHP 基礎課" qty = 2
      And 訂單 "ORDER-1" 應包含 line item "年度套餐 - T-shirt" qty = 3

    Example: 購買 2 份含多數量的銷售方案
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 300       | 年度套餐 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      And 用戶 "Alice" 建立訂單 "ORDER-2" 購買商品 300，數量 2
      When WooCommerce 訂單 "ORDER-2" 建立
      Then 訂單 "ORDER-2" 應包含 line item "年度套餐 - PHP 基礎課" qty = 4
      And 訂單 "ORDER-2" 應包含 line item "年度套餐 - T-shirt" qty = 6

  Rule: 後置（狀態）- 訂單完成後庫存依 line item qty 正確扣除

    Example: 庫存依數量扣除
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 300       | 年度套餐 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      And 商品 200 的庫存為 50
      And 用戶 "Alice" 建立訂單 "ORDER-3" 購買商品 300，數量 1
      When WooCommerce 訂單 "ORDER-3" 狀態變更為 "completed"
      Then 商品 200 的庫存應為 47

  Rule: 後置（狀態）- 課程開通維持去重（同用戶 + 同課程不重複開通，但庫存正確扣除）

    Example: 課程數量 2 時只開通 1 次但庫存扣 2
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 300       | 年度套餐 | bundle      | 100            | 100             | {"100": 2}             |
      And 用戶 "Alice" 建立訂單 "ORDER-4" 購買商品 300，數量 1
      When WooCommerce 訂單 "ORDER-4" 狀態變更為 "completed"
      Then 用戶 "Alice" 的 avl_course_ids 應包含課程 100
      And 用戶 "Alice" 的課程 100 存取權僅有一筆記錄

  # ========== 前置（參數）— 數量驗證 ==========

  Rule: 前置（參數）- 數量必須為 1~999 的正整數

    Scenario Outline: 不合理的數量值自動修正
      When 管理員設定商品數量為 <輸入值>
      Then 實際儲存的數量應為 <修正值>

      Examples:
        | 輸入值 | 修正值 | 說明                   |
        | 0      | 1      | 零自動修正為 1         |
        | -5     | 1      | 負數自動修正為 1       |
        | 2.7    | 2      | 小數無條件捨去為整數   |
        | 1000   | 999    | 超過上限修正為 999     |
        | 3      | 3      | 合法值不修正           |

  # ========== 前台顯示 ==========

  Rule: 後置（狀態）- 前台銷售頁數量 > 1 的商品顯示「×N」

    Example: 前台顯示商品數量標示
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 300       | 年度套餐 | bundle      | 100            | 100,200         | {"100": 1, "200": 3}   |
      When 訪客瀏覽課程 100 的銷售頁
      Then 銷售方案 "年度套餐" 卡片中：
        | product_name | quantity_display |
        | PHP 基礎課   |                 |
        | T-shirt      | ×3              |
