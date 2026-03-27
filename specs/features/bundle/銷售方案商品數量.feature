@bundle @quantity
Feature: 銷售方案商品數量

  身為 課程管理員
  我想要 在銷售方案裡自由設定每個商品的數量
  以便於 組合出符合需求的銷售方案（如 2 堂課 + 3 件 T-shirt）

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  | regular_price | manage_stock | stock_quantity |
      | 100      | JavaScript課 | yes        | publish | 1000          | no           |                |
    And 系統中有以下商品：
      | productId | name       | status  | type   | regular_price | manage_stock | stock_quantity |
      | 201       | Python課   | publish | simple | 800           | no           |                |
      | 202       | 經典T-shirt | publish | simple | 500           | yes          | 100            |
      | 203       | 進階帽子    | publish | simple | 300           | yes          | 50             |
    And 系統中有以下銷售方案：
      | bundleId | name       | link_course_id | bundle_type | status  |
      | 301      | 基礎方案    | 100            | bundle      | publish |

  # ========== 資料結構 ==========

  Rule: 數量儲存於獨立 meta key `pbp_product_quantities`，格式為 JSON `{"product_id": qty}`

    Example: 新建銷售方案時設定商品數量
      When 管理員 "Admin" 更新銷售方案 "301" 的包含商品與數量：
        | product_id | quantity |
        | 100        | 2        |
        | 202        | 3        |
      Then 操作成功
      And 銷售方案 "301" 的 "pbp_product_ids" 包含 [100, 202]
      And 銷售方案 "301" 的 "pbp_product_quantities" 為 {"100": 2, "202": 3}

    Example: 未設定數量的商品預設為 1（向後兼容）
      Given 銷售方案 "301" 已有包含商品 [100, 201]，但沒有 "pbp_product_quantities" meta
      When 系統讀取銷售方案 "301" 的商品數量
      Then 商品 "100" 的數量為 1
      And 商品 "201" 的數量為 1

  # ========== 數量限制 ==========

  Rule: 數量必須為 1~999 的正整數

    Example: 數量為 0 時操作失敗
      When 管理員 "Admin" 更新銷售方案 "301" 的包含商品與數量：
        | product_id | quantity |
        | 100        | 0        |
      Then 操作失敗

    Example: 數量為負數時操作失敗
      When 管理員 "Admin" 更新銷售方案 "301" 的包含商品與數量：
        | product_id | quantity |
        | 100        | -1       |
      Then 操作失敗

    Example: 數量為小數時操作失敗
      When 管理員 "Admin" 更新銷售方案 "301" 的包含商品與數量：
        | product_id | quantity |
        | 100        | 1.5      |
      Then 操作失敗

    Example: 數量超過 999 時操作失敗
      When 管理員 "Admin" 更新銷售方案 "301" 的包含商品與數量：
        | product_id | quantity |
        | 100        | 1000     |
      Then 操作失敗

  # ========== 價格自動計算 ==========

  Rule: regular_price = Σ(商品單價 × 數量)，包含當前課程

    Example: 自動計算含數量的總價
      Given 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity | regular_price |
        | 100        | 2        | 1000          |
        | 202        | 3        | 500           |
      When 前端自動計算建議售價
      Then 建議 regular_price 為 3500
      # 計算：(1000 × 2) + (500 × 3) = 3500

    Example: 排除當前課程時不計入課程價格
      Given 銷售方案 "301" 設定 exclude_main_course 為 "yes"
      And 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity | regular_price |
        | 202        | 3        | 500           |
      When 前端自動計算建議售價
      Then 建議 regular_price 為 1500
      # 計算：500 × 3 = 1500

  # ========== 庫存扣減 ==========

  Rule: 結帳扣庫存 = 方案內數量 × 購買份數

    Example: 購買 1 份含多數量的銷售方案
      Given 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity |
        | 100        | 2        |
        | 202        | 3        |
      And "經典T-shirt" 目前庫存為 100
      When 客戶購買 1 份銷售方案 "301"
      Then 訂單中包含以下項目：
        | product_name                   | quantity | subtotal |
        | 基礎方案 - JavaScript課         | 2        | 0        |
        | 基礎方案 - 經典T-shirt          | 3        | 0        |
      And "經典T-shirt" 庫存變為 97

    Example: 購買 2 份含多數量的銷售方案
      Given 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity |
        | 100        | 2        |
        | 202        | 3        |
      And "經典T-shirt" 目前庫存為 100
      When 客戶購買 2 份銷售方案 "301"
      Then 訂單中包含以下項目：
        | product_name                   | quantity | subtotal |
        | 基礎方案 - JavaScript課         | 4        | 0        |
        | 基礎方案 - 經典T-shirt          | 6        | 0        |
      And "經典T-shirt" 庫存變為 94
      # 計算：3 × 2 = 6，100 - 6 = 94

  # ========== 前台顯示 ==========

  Rule: 前台銷售方案卡片顯示「商品名稱 × 數量」，數量為 1 時不顯示 × 1

    Example: 數量大於 1 時顯示數量
      Given 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity |
        | 100        | 2        |
        | 202        | 3        |
      When 前台渲染銷售方案 "301" 的商品列表
      Then 顯示 "JavaScript課 × 2"
      And 顯示 "經典T-shirt × 3"

    Example: 數量為 1 時不顯示數量標記
      Given 銷售方案 "301" 包含商品與數量如下：
        | product_id | quantity |
        | 100        | 1        |
        | 202        | 1        |
      When 前台渲染銷售方案 "301" 的商品列表
      Then 顯示 "JavaScript課"
      And 不顯示 "× 1"

  # ========== 後台 UI ==========

  Rule: 後台每個已選商品右側顯示 InputNumber，預設值 1，min=1，max=999

    Example: 新增商品到銷售方案時預設數量為 1
      When 管理員在銷售方案編輯頁面選擇商品 "經典T-shirt"
      Then 商品 "經典T-shirt" 旁邊顯示 InputNumber
      And InputNumber 的值為 1
      And InputNumber 的最小值為 1
      And InputNumber 的最大值為 999

    Example: 當前課程也顯示 InputNumber
      When 管理員查看銷售方案 "301" 的編輯頁面
      Then 當前課程 "JavaScript課" 旁邊顯示 InputNumber
      And InputNumber 的值為對應的數量設定

    Example: 修改數量後自動更新建議售價
      Given 管理員在銷售方案 "301" 編輯頁面
      And 已選商品如下：
        | product_id | name       | regular_price | quantity |
        | 100        | JavaScript課 | 1000          | 1        |
        | 202        | 經典T-shirt | 500           | 1        |
      When 管理員將 "經典T-shirt" 的數量改為 3
      Then 建議 regular_price 從 1500 更新為 2500
      # 計算：(1000 × 1) + (500 × 3) = 2500
