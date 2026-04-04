@ignore @command
Feature: 銷售方案含數量訂單處理

  學員購買銷售方案後，系統依照每個商品的設定數量加入訂單。
  庫存扣除公式：方案購買數量 × 商品設定數量。
  無 pbp_product_quantities 時所有商品預設數量為 1（向下相容）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email           | role     |
      | 1      | Admin | admin@test.com  | administrator |
      | 2      | 小明  | ming@test.com   | customer |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price | manage_stock | stock_quantity |
      | 100     | PHP 基礎課 | yes        | publish | 3000          | yes          | 50             |
    And 系統中有以下商品：
      | productId | name       | type   | status  | regular_price | manage_stock | stock_quantity |
      | 200       | T-shirt    | simple | publish | 200           | yes          | 100            |
      | 300       | React 課程 | simple | publish | 5000          | yes          | 30             |

  # ========== 訂單中商品數量 ==========

  Rule: 訂單處理 - 訂單中各商品數量 = 方案購買數量 × 商品設定數量

    Example: 購買 1 份方案，各商品按設定數量加入訂單
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 500      | 超值套餐 | bundle      | 100            | 6000  |
      And 銷售方案 500 包含以下商品及數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
      When 學員 "小明" 購買 1 份銷售方案 500，訂單完成
      Then 訂單中包含以下訂單項目：
        | productName           | quantity | subtotal |
        | 超值套餐              | 1        | 6000     |
        | 超值套餐 - PHP 基礎課 | 1        | 0        |
        | 超值套餐 - T-shirt    | 3        | 0        |
        | 超值套餐 - React 課程 | 1        | 0        |

    Example: 購買 2 份方案，數量乘以方案購買數
      Given 課��� 100 有以下銷售���案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 500      | 超值套餐 | bundle      | 100            | 6000  |
      And 銷售方案 500 包含以下商品及數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
      When 學員 "小明" 購買 2 份��售方案 500，訂單完成
      Then 訂單中包含以下訂單項���：
        | productName           | quantity | subtotal |
        | 超值套餐              | 2        | 12000    |
        | 超值套餐 - PHP 基礎課 | 2        | 0        |
        | 超值套餐 - T-shirt    | 6        | 0        |
        | 超值套餐 - React 課程 | 2        | 0        |

  # ========== 庫存扣除 ==========

  Rule: 庫存扣除 - 各商品庫存減少量 = 方案購買數量 × 商品設定數量

    Example: 購買 1 份方案，庫存按設定數量扣除
      Given 課��� 100 ���以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 500      | 超值套餐 | bundle      | 100            | 6000  |
      And 銷售方案 500 包含以下商品及數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
      When 學員 "小明" 購買 1 份銷售方案 500，訂單完成
      Then PHP 基礎課 的庫存從 50 減為 49
      And T-shirt 的庫存從 100 減為 97
      And React 課程 的庫存從 30 減��� 29

    Example: 購買 2 份方案，庫存扣除量翻倍
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 500      | 超值套餐 | bundle      | 100            | 6000  |
      And 銷售方案 500 包含以下��品及數量：
        | productId | quantity |
        | 100       | 2        |
        | 200       | 3        |
        | 300       | 1        |
      When 學員 "小明" 購買 2 份銷售方案 500���訂單完成
      Then PHP 基礎課 的庫存從 50 減為 46
      And T-shirt 的庫存從 100 減為 94
      And React 課��� 的庫存從 30 減為 28

  # ========== 向下相容 ==========

  Rule: 向下相容 - 無 pbp_product_quantities 時所有商品數量預設為 1

    Example: 舊銷售方案（無數量資料）購買時每個商品數量為 1
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 501      | 舊方案   | bundle      | 100            | 5000  |
      And 銷售方案 501 包含以下商品：
        | productId |
        | 100       |
        | 200       |
      And 銷售方案 501 沒有 pbp_product_quantities meta
      When 學員 "小明" 購�� 1 份銷售方案 501，訂單��成
      Then 訂單中包含以下訂單項目：
        | productName           | quantity | subtotal |
        | 舊方案                | 1        | 5000     |
        | 舊方案 - PHP 基礎課   | 1        | 0        |
        | 舊方案 - T-shirt      | 1        | 0        |
      And PHP 基礎課 的庫存�� 50 減為 49
      And T-shirt 的庫存從 100 減為 99

  # ========== 課程存取權 ==========

  Rule: 課程存取權 - 課程類商品無論數量為何，學員僅獲得一份課程存取權

    Example: 課程數量 > 1 時學員仍只獲得一份存取權
      Given 課程 100 有以��銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price |
        | 500      | 超值套餐 | bundle      | 100            | 6000  |
      And 銷售方案 500 包含以下商品及數量：
        | productId | quantity |
        | 100       | 2        |
        | 200       | 3        |
      When 學員 "小明" 購買 1 份銷售方案 500，訂單完成
      Then 學員 "小明" 獲得 PHP 基礎課 的存取權（僅一份）
      And PHP 基礎課 的庫存從 50 減為 48
