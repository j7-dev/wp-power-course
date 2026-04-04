@ignore @command
Feature: 銷售方案商品數量

  管理員可以為銷售方案中的每個商品設定數量（1~999），
  影響原價計算、前台顯示、訂單項目名稱與庫存扣減。
  數量存於 postmeta `pbp_product_quantities`（JSON: {"商品ID": 數量}），
  `pbp_product_ids` 維持不變。未設定數量的商品預設為 1。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100      | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下 WooCommerce 商品：
      | productId | name        | regular_price | stock_quantity | manage_stock |
      | 200       | Python 講義 | 500           | 50             | yes          |
      | 201       | React 講義  | 300           | 30             | yes          |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- 數量必須為 1~999 的正整數

    Scenario Outline: 輸入不合法的數量時自動修正為 1
      Given 管理員 "Admin" 建立銷售方案，參數如下：
        | name     | link_course_id | price | bundle_type |
        | 測試方案 | 100            | 399   | bundle      |
      And 銷售方案的 pbp_product_ids 包含商品 200
      When 管理員設定商品 200 的數量為 "<input>"
      Then 商品 200 的實際儲存數量應為 <expected>

      Examples:
        | input | expected | 說明                     |
        | 0     | 1        | 零自動修正為 1           |
        |       | 1        | 空白自動修正為 1         |
        | -1    | 1        | 負數自動修正為 1         |
        | 1.5   | 1        | 小數取整（floor）        |
        | abc   | 1        | 非數字自動修正為 1       |
        | 1000  | 999      | 超過上限修正為 999       |

    Example: 設定合法數量
      Given 管理員 "Admin" 建立銷售方案，參數如下：
        | name     | link_course_id | price | bundle_type |
        | 測試方案 | 100            | 399   | bundle      |
      And 銷售方案的 pbp_product_ids 包含商品 200
      When 管理員設定商品 200 的數量為 "3"
      Then 商品 200 的實際儲存數量應為 3

  # ========== 後置（狀態）— 儲存 ==========

  Rule: 後置（狀態）- 數量以 JSON 格式儲存於 `pbp_product_quantities` postmeta

    Example: 儲存含數量的銷售方案
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | price | bundle_type |
        | 超值學習包 | 100            | 2000  | bundle      |
      And 銷售方案的 pbp_product_ids 包含商品 [100, 200, 201]
      And 銷售方案的 pbp_product_quantities 為：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
        | 201        | 2   |
      Then 操作成功
      And 銷售方案的 postmeta `pbp_product_quantities` 應為 JSON '{"100":1,"200":3,"201":2}'

    Example: 重新開啟編輯畫面時數量正確回顯
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 100,200,201     |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"100":1,"200":3,"201":2}'
      When 管理員 "Admin" 開啟銷售方案 600 的編輯畫面
      Then 商品 100 的數量輸入框應顯示 1
      And 商品 200 的數量輸入框應顯示 3
      And 商品 201 的數量輸入框應顯示 2

  # ========== 後置（狀態）— 向下相容 ==========

  Rule: 後置（狀態）- 既有銷售方案升級後，所有商品數量預設為 1

    Example: 舊銷售方案無 pbp_product_quantities 時預設為 1
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 月費方案 | bundle      | 100            | 200             |
      And 銷售方案 600 不存在 pbp_product_quantities postmeta
      When 管理員 "Admin" 開啟銷售方案 600 的編輯畫面
      Then 商品 200 的數量輸入框應顯示 1

  # ========== 後置（狀態）— 原價計算 ==========

  Rule: 後置（狀態）- 原價參考值 = 各商品 (單價 x 數量) 的加總

    Example: 原價根據數量計算
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 100,200         |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"100":1,"200":3}'
      Then 銷售方案 600 的原價參考值應為 4500
      # 計算：PHP 基礎課 $3,000 x 1 + Python 講義 $500 x 3 = $4,500

  # ========== 後置（狀態）— 前台顯示 ==========

  Rule: 後置（狀態）- 前台銷售頁：數量 > 1 時顯示「xN」，數量 = 1 時不顯示

    Example: 前台顯示商品數量
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 100,200,201     |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"100":1,"200":3,"201":1}'
      When 學員瀏覽課程 100 的銷售頁面
      Then 銷售方案「超值學習包」的商品列表應顯示：
        | product_name | qty_display |
        | PHP 基礎課   |             |
        | Python 講義  | x3          |
        | React 講義   |             |

  # ========== 後置（狀態）— 訂單項目 ==========

  Rule: 後置（狀態）- 訂單項目名稱包含數量標記，WC qty 為銷售方案購買份數

    Example: 購買 1 份含多數量商品的銷售方案
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 100,200         |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"100":1,"200":3}'
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買商品 600，數量 1
      When WooCommerce 處理訂單 "ORDER-1"
      Then 訂單 "ORDER-1" 應包含以下項目：
        | item_name                    | qty | total |
        | 超值學習包                   | 1   | 2000  |
        | 超值學習包 - PHP 基礎課      | 1   | 0     |
        | 超值學習包 - Python 講義 x3   | 1   | 0     |

  # ========== 後置（狀態）— 庫存扣減 ==========

  Rule: 後置（狀態）- 庫存扣減 = 購買份數 x 方案內商品數量

    Example: 購買 1 份銷售方案，庫存按方案數量扣減
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 200             |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"200":3}'
      And 商品 200 "Python 講義" 庫存為 50
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買商品 600，數量 1
      When WooCommerce 訂單 "ORDER-1" 完成庫存扣減
      Then 商品 200 "Python 講義" 庫存應為 47
      # 計算：50 - (1 x 3) = 47

    Example: 購買 2 份銷售方案，庫存按倍數扣減
      Given 系統中有以下銷售方案：
        | productId | name       | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 超值學習包 | bundle      | 100            | 200             |
      And 銷售方案 600 的 pbp_product_quantities 為 '{"200":3}'
      And 商品 200 "Python 講義" 庫存為 50
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買商品 600，數量 2
      When WooCommerce 訂單 "ORDER-1" 完成庫存扣減
      Then 商品 200 "Python 講義" 庫存應為 44
      # 計算：50 - (2 x 3) = 44

    Example: 數量為 1 的商品庫存扣減不變（向下相容）
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 600       | 月費方案 | bundle      | 100            | 200             |
      And 銷售方案 600 不存在 pbp_product_quantities postmeta
      And 商品 200 "Python 講義" 庫存為 50
      And 用戶 "Alice" 建立訂單 "ORDER-1" 購買商品 600，數量 1
      When WooCommerce 訂單 "ORDER-1" 完成庫存扣減
      Then 商品 200 "Python 講義" 庫存應為 49
      # 計算：50 - (1 x 1) = 49（預設數量 1）
