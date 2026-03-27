@command
Feature: 銷售方案商品數量

  銷售方案的組合裡面的每個商品都可以自由設定數量，
  包含當前課程。數量影響價格計算、前台顯示、庫存扣除。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | customer      |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100      | PHP 基礎課 | yes        | publish | 1000          |
    And 系統中有以下 WooCommerce 商品：
      | productId | name    | regular_price | type   | status  | stock_quantity |
      | 200       | T-shirt | 500           | simple | publish | 100            |
      | 201       | 帽子    | 300           | simple | publish | 50             |
    And 系統設定 course_access_trigger 為 "completed"

  # ==========================================================
  # 資料模型：pbp_product_quantities 儲存與兼容
  # ==========================================================

  Rule: 資料模型 - 新增 pbp_product_quantities meta key 以 JSON 格式儲存各商品數量

    Example: 建立含數量的銷售方案
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | bundle_type | regular_price |
        | 超值組合包 | 100            | bundle      | 3500          |
      And 銷售方案的 pbp_product_ids 為 [100, 200]
      And 銷售方案的 pbp_product_quantities 為：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      Then 操作成功
      And 銷售方案的 pbp_product_quantities meta 應為 {"100": 2, "200": 3}

    Example: 更新銷售方案的商品數量
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities  |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}    |
      When 管理員 "Admin" 更新銷售方案 600 的 pbp_product_quantities 為：
        | product_id | quantity |
        | 100        | 1        |
        | 200        | 5        |
      Then 操作成功
      And 銷售方案 600 的 pbp_product_quantities meta 應為 {"100": 1, "200": 5}

  Rule: 資料模型 - 向後兼容，pbp_product_quantities 不存在時所有商品數量預設為 1

    Example: 舊資料無 pbp_product_quantities 時數量預設為 1
      Given 系統中有以下銷售方案（舊資料，無數量欄位）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 601       | 舊方案   | bundle      | 100            | 100,200         |
      When 讀取銷售方案 601 的商品數量
      Then 商品 100 的數量應為 1
      And 商品 200 的數量應為 1

  # ==========================================================
  # 後台 UI：InputNumber 輸入
  # ==========================================================

  Rule: 後台 UI - 每個已選商品卡片右側顯示 InputNumber，最小值 1，無上限，步進 1

    Example: 目前課程卡片顯示數量輸入框
      Given 管理員進入課程 100 的銷售方案編輯頁
      When 管理員建立新銷售方案
      Then 目前課程「PHP 基礎課」卡片右側應顯示 InputNumber
      And InputNumber 預設值為 1
      And InputNumber 最小值為 1
      And InputNumber 步進為 1

    Example: 其他已選商品卡片顯示數量輸入框
      Given 管理員進入課程 100 的銷售方案編輯頁
      And 管理員已選擇商品 "T-shirt" 加入銷售方案
      Then 商品「T-shirt」卡片右側應顯示 InputNumber
      And InputNumber 預設值為 1

    Example: 排除目前課程時不顯示課程數量輸入
      Given 管理員進入課程 100 的銷售方案編輯頁
      When 管理員勾選「排除目前課程」
      Then 目前課程卡片應淡化顯示
      And 目前課程卡片不應顯示 InputNumber

  # ==========================================================
  # 價格自動計算
  # ==========================================================

  Rule: 價格計算 - regular_price 自動計算為 Σ(商品原價 × 數量)

    Example: 自動計算含數量的 regular_price
      Given 管理員進入課程 100 的銷售方案編輯頁
      And 銷售方案包含以下商品與數量：
        | product    | regular_price | quantity |
        | PHP 基礎課 | 1000          | 2        |
        | T-shirt    | 500           | 3        |
      Then 自動計算的 regular_price 應為 3500

    Example: 排除目前課程時不計入課程價格
      Given 管理員進入課程 100 的銷售方案編輯頁
      And 管理員勾選「排除目前課程」
      And 銷售方案包含以下商品與數量：
        | product | regular_price | quantity |
        | T-shirt | 500           | 3        |
      Then 自動計算的 regular_price 應為 1500

    Example: 修改數量時即時更新 regular_price
      Given 管理員進入課程 100 的銷售方案編輯頁
      And 銷售方案包含以下商品與數量：
        | product    | regular_price | quantity |
        | PHP 基礎課 | 1000          | 1        |
        | T-shirt    | 500           | 1        |
      When 管理員將「T-shirt」的數量改為 3
      Then 自動計算的 regular_price 應從 1500 更新為 2500

  # ==========================================================
  # 前台顯示
  # ==========================================================

  Rule: 前台顯示 - 銷售方案卡片中每個商品名稱後方標示「×N」

    Example: 前台銷售方案卡片顯示商品數量
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      When 訪客瀏覽課程 100 的銷售方案卡片
      Then 應顯示「PHP 基礎課 ×2」
      And 應顯示「T-shirt ×3」

    Example: 數量為 1 時也顯示 ×1
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 602       | 基礎組合 | bundle      | 100            | 100,200         | {"100": 1, "200": 1}   |
      When 訪客瀏覽課程 100 的銷售方案卡片
      Then 應顯示「PHP 基礎課 ×1」
      And 應顯示「T-shirt ×1」

    Example: 舊方案（無數量資料）前台顯示 ×1
      Given 系統中有以下銷售方案（舊資料，無數量欄位）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 601       | 舊方案   | bundle      | 100            | 100,200         |
      When 訪客瀏覽課程 100 的銷售方案卡片
      Then 應顯示「PHP 基礎課 ×1」
      And 應顯示「T-shirt ×1」

  # ==========================================================
  # 訂單處理：庫存扣除
  # ==========================================================

  Rule: 訂單處理 - 結帳時按 order qty × item qty 扣除庫存

    Example: 購買 1 份銷售方案，按各商品數量扣庫存
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities | regular_price | sale_price |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   | 3500          | 2999       |
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買 1 份銷售方案 600
      When WooCommerce 訂單 "ORDER-1" 成立
      Then 訂單應包含子項目「超值組合包 - PHP 基礎課」數量 2，售價 0
      And 訂單應包含子項目「超值組合包 - T-shirt」數量 3，售價 0
      And 商品 200 (T-shirt) 庫存應從 100 減為 97

    Example: 購買 2 份銷售方案，庫存扣除量加倍
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities | regular_price | sale_price |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   | 3500          | 2999       |
      And 用戶 "Alice" 建立訂單 "ORDER-2" 購買 2 份銷售方案 600
      When WooCommerce 訂單 "ORDER-2" 成立
      Then 訂單應包含子項目「超值組合包 - PHP 基礎課」數量 4，售價 0
      And 訂單應包含子項目「超值組合包 - T-shirt」數量 6，售價 0
      And 商品 200 (T-shirt) 庫存應從 100 減為 94

  # ==========================================================
  # 訂單處理：課程開通
  # ==========================================================

  Rule: 訂單處理 - 課程開通不受數量影響，每位學員只開通一次

    Example: 課程數量 > 1 但學員只開通一次
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      And 用戶 "Alice" 建立訂單 "ORDER-3" 購買 1 份銷售方案 600
      When WooCommerce 訂單 "ORDER-3" 狀態變更為 "completed"
      Then 用戶 "Alice" 的 avl_course_ids 應包含課程 100
      And 用戶 "Alice" 的課程 100 存取權僅有一筆記錄

  # ==========================================================
  # REST API
  # ==========================================================

  Rule: REST API - POST/PUT bundle_products 端點接受 pbp_product_quantities 參數

    Example: 透過 API 建立含數量的銷售方案
      When 管理員透過 POST /power-course/v2/bundle_products 建立銷售方案：
        | field                   | value                  |
        | name                    | API 組合包              |
        | link_course_ids         | 100                    |
        | bundle_type             | bundle                 |
        | pbp_product_ids         | [100, 200]             |
        | pbp_product_quantities  | {"100": 2, "200": 3}   |
      Then 回應狀態碼應為 200
      And 回應中 pbp_product_quantities 應為 {"100": 2, "200": 3}

    Example: 透過 API 讀取銷售方案時回傳數量資訊
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids | pbp_product_quantities |
        | 600       | 超值組合包 | bundle      | 100            | 100,200         | {"100": 2, "200": 3}   |
      When 管理員透過 GET /power-course/v2/products/600 讀取銷售方案
      Then 回應中 pbp_product_quantities 應為 {"100": 2, "200": 3}

    Example: 透過 API 讀取舊方案時 pbp_product_quantities 為空物件
      Given 系統中有以下銷售方案（舊資料，無數量欄位）：
        | productId | name   | bundle_type | link_course_id | pbp_product_ids |
        | 601       | 舊方案 | bundle      | 100            | 100,200         |
      When 管理員透過 GET /power-course/v2/products/601 讀取銷售方案
      Then 回應中 pbp_product_quantities 應為 {}
