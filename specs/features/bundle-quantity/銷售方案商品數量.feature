@issue-140 @bundle @quantity
Feature: 銷售方案商品數量自訂

  作為站長，我希望銷售方案中每個商品可以自訂數量，
  這樣我就能建立像「2個課程 + 3件T-shirt」這樣的組合方案。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | regular_price | status  | _is_course | manage_stock | stock_quantity |
      | 100      | PHP 基礎課 | 1000          | publish | yes        | true         | 50             |
    And 系統中有以下商品：
      | productId | name       | regular_price | status  | type   | manage_stock | stock_quantity |
      | 200       | 品牌T-shirt | 500           | publish | simple | true         | 100            |
      | 300       | 程式設計書  | 800           | publish | simple | true         | 30             |

  # ==========================================================
  # Command: 建立含商品數量的銷售方案
  # ==========================================================

  Rule: 建立銷售方案時可指定每個綑綁商品的數量

    Example: 成功建立含自訂數量的銷售方案
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name               | link_course_id | bundle_type | regular_price | sale_price |
        | 超值學習組合         | 100            | bundle      | 5000          | 3999       |
      And 銷售方案包含以下商品與數量：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      Then 操作成功
      And 銷售方案的 pbp_product_ids 應包含 [100, 200]
      And 銷售方案的 pbp_product_quantities 應為：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |

    Example: 未指定數量時預設為 1
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name           | link_course_id | bundle_type | regular_price |
        | 基本學習組合     | 100            | bundle      | 1500          |
      And 銷售方案包含以下商品與數量：
        | product_id | quantity |
        | 100        |          |
        | 200        |          |
      Then 操作成功
      And 銷售方案的 pbp_product_quantities 應為：
        | product_id | quantity |
        | 100        | 1        |
        | 200        | 1        |

  Rule: 數量必須為正整數（>= 1）

    Example: 數量為 0 時建立失敗
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | bundle_type |
        | 錯誤方案    | 100            | bundle      |
      And 銷售方案包含以下商品與數量：
        | product_id | quantity |
        | 100        | 0        |
      Then 操作失敗，錯誤訊息包含 "數量必須大於 0"

    Example: 數量為負數時建立失敗
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | bundle_type |
        | 錯誤方案    | 100            | bundle      |
      And 銷售方案包含以下商品與數量：
        | product_id | quantity |
        | 100        | -1       |
      Then 操作失敗，錯誤訊息包含 "數量必須大於 0"

  # ==========================================================
  # Command: 更新銷售方案的商品數量
  # ==========================================================

  Rule: 可更新既有銷售方案中商品的數量

    Example: 成功更新商品數量
      Given 系統中有以下銷售方案：
        | bundleId | name         | link_course_id | bundle_type |
        | 400      | 超值學習組合  | 100            | bundle      |
      And 銷售方案 400 包含以下商品與數量：
        | product_id | quantity |
        | 100        | 1        |
        | 200        | 1        |
      When 管理員 "Admin" 更新銷售方案 400 的商品數量：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      Then 操作成功
      And 銷售方案 400 的 pbp_product_quantities 應為：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |

  # ==========================================================
  # Read Model: 價格自動計算
  # ==========================================================

  Rule: 建議售價（regular_price）應根據商品數量自動計算

    Example: 建議售價 = 各商品單價 × 數量之總和
      Given 管理員正在編輯銷售方案，已選擇以下商品：
        | product_id | name        | regular_price | quantity |
        | 100        | PHP 基礎課   | 1000          | 2        |
        | 200        | 品牌T-shirt  | 500           | 3        |
      And 排除目前課程 = "no"
      Then 建議售價應為 3500
      # 計算：(1000 × 2) + (500 × 3) = 3500

    Example: 排除主課程時不計入主課程價格
      Given 管理員正在編輯銷售方案，已選擇以下商品：
        | product_id | name        | regular_price | quantity |
        | 200        | 品牌T-shirt  | 500           | 3        |
      And 排除目前課程 = "yes"
      Then 建議售價應為 1500
      # 計算：500 × 3 = 1500

  # ==========================================================
  # 結帳後庫存扣減
  # ==========================================================

  Rule: 結帳完成後，庫存按照銷售方案中各商品的數量扣減

    Example: 購買 1 份銷售方案，按商品數量扣庫存
      Given 系統中有以下銷售方案：
        | bundleId | name         | link_course_id | bundle_type | regular_price |
        | 400      | 超值學習組合  | 100            | bundle      | 3999          |
      And 銷售方案 400 包含以下商品與數量：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      And 課程 100 的庫存為 50
      And 商品 200 的庫存為 100
      When 客戶購買 1 份銷售方案 400 並完成結帳
      Then 課程 100 的庫存應為 48
      And 商品 200 的庫存應為 97
      And 訂單中應包含以下項目：
        | name                            | quantity | total |
        | 超值學習組合                      | 1        | 3999  |
        | 超值學習組合 - PHP 基礎課          | 2        | 0     |
        | 超值學習組合 - 品牌T-shirt         | 3        | 0     |

    Example: 購買多份銷售方案時，庫存按倍數扣減
      Given 系統中有以下銷售方案：
        | bundleId | name         | link_course_id | bundle_type | regular_price |
        | 400      | 超值學習組合  | 100            | bundle      | 3999          |
      And 銷售方案 400 包含以下商品與數量：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      And 課程 100 的庫存為 50
      And 商品 200 的庫存為 100
      When 客戶購買 2 份銷售方案 400 並完成結帳
      Then 課程 100 的庫存應為 46
      And 商品 200 的庫存應為 94
      # 計算：課程 50 - (2 × 2) = 46，T-shirt 100 - (3 × 2) = 94

  # ==========================================================
  # 前台顯示
  # ==========================================================

  Rule: 前台銷售方案卡片應顯示各商品數量

    Example: 銷售方案卡片顯示商品及其數量
      Given 系統中有以下銷售方案：
        | bundleId | name         | link_course_id | bundle_type | status  |
        | 400      | 超值學習組合  | 100            | bundle      | publish |
      And 銷售方案 400 包含以下商品與數量：
        | product_id | quantity |
        | 100        | 2        |
        | 200        | 3        |
      When 訪客瀏覽包含銷售方案 400 的頁面
      Then 銷售方案卡片應顯示 "PHP 基礎課" 且數量標示 "x2"
      And 銷售方案卡片應顯示 "品牌T-shirt" 且數量標示 "x3"

    Example: 數量為 1 時不顯示數量標示
      Given 系統中有以下銷售方案：
        | bundleId | name         | link_course_id | bundle_type | status  |
        | 400      | 基本組合      | 100            | bundle      | publish |
      And 銷售方案 400 包含以下商品與數量：
        | product_id | quantity |
        | 100        | 1        |
        | 200        | 1        |
      When 訪客瀏覽包含銷售方案 400 的頁面
      Then 銷售方案卡片應顯示 "PHP 基礎課" 且不顯示數量標示
      And 銷售方案卡片應顯示 "品牌T-shirt" 且不顯示數量標示

  # ==========================================================
  # 後台 UI
  # ==========================================================

  Rule: 後台銷售方案編輯表單每個商品旁邊有數量輸入框

    Example: 新增商品時預設數量為 1
      Given 管理員正在編輯銷售方案
      When 管理員搜尋並加入商品 "品牌T-shirt"
      Then 商品列表中 "品牌T-shirt" 旁應有數量輸入框
      And 數量輸入框的預設值應為 1

    Example: 管理員可修改商品數量
      Given 管理員正在編輯銷售方案
      And 商品列表中已有 "品牌T-shirt" 數量為 1
      When 管理員將 "品牌T-shirt" 的數量修改為 3
      Then 商品列表中 "品牌T-shirt" 的數量應為 3
      And 建議售價應即時更新

    Example: 當前課程也有數量輸入框
      Given 管理員正在編輯銷售方案
      And 排除目前課程 = "no"
      Then 當前課程 "PHP 基礎課" 旁應有數量輸入框
      And 數量輸入框的預設值應為 1

  # ==========================================================
  # 向後相容
  # ==========================================================

  Rule: 既有銷售方案（無 pbp_product_quantities 資料）應向後相容

    Example: 舊銷售方案讀取時數量預設為 1
      Given 系統中有舊格式銷售方案（僅有 pbp_product_ids，無 pbp_product_quantities）：
        | bundleId | name     | link_course_id | pbp_product_ids |
        | 500      | 舊方案    | 100            | [100, 200]      |
      When 管理員開啟銷售方案 500 的編輯頁面
      Then 商品 100 的數量應顯示為 1
      And 商品 200 的數量應顯示為 1

    Example: 舊銷售方案結帳時每個商品數量為 1
      Given 系統中有舊格式銷售方案（僅有 pbp_product_ids，無 pbp_product_quantities）：
        | bundleId | name     | link_course_id | pbp_product_ids | regular_price |
        | 500      | 舊方案    | 100            | [100, 200]      | 1500          |
      And 課程 100 的庫存為 50
      And 商品 200 的庫存為 100
      When 客戶購買 1 份銷售方案 500 並完成結帳
      Then 課程 100 的庫存應為 49
      And 商品 200 的庫存應為 99
