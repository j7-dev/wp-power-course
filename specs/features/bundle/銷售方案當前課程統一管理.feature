@ignore @command
Feature: 銷售方案當前課程統一管理

  將「當前課程」重構為與其他商品完全一致的邏輯和 UI。
  建立銷售方案時自動將「當前課程」預設加入（qty=1），管理員可自由修改數量或移除。
  移除 `exclude_main_course` 開關，改由商品是否在 `pbp_product_ids` 列表中決定。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100      | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下 WooCommerce 商品：
      | productId | name        | regular_price |
      | 200       | Python 講義 | 500           |

  # ========== 後置（狀態）— 建立新方案 ==========

  Rule: 後置（狀態）- 建立銷售方案時，自動將當前課程加入 pbp_product_ids 並預設數量為 1

    Example: 新建銷售方案自動包含當前課程
      When 管理員 "Admin" 在課程 100 的編輯頁面建立銷售方案，參數如下：
        | name     | link_course_id | price | bundle_type |
        | 月費方案 | 100            | 399   | bundle      |
      Then 操作成功
      And 銷售方案的 pbp_product_ids 應包含 100
      And 銷售方案的 pbp_product_quantities 中課程 100 的數量應為 1

  # ========== 後置（狀態）— UI 一致性 ==========

  Rule: 後置（狀態）- 當前課程在 UI 上與其他商品的操作方式完全一致

    Example: 當前課程顯示數量輸入框
      When 管理員 "Admin" 在課程 100 的銷售方案編輯畫面
      Then 當前課程 "PHP 基礎課" 應出現在商品列表中
      And 當前課程 "PHP 基礎課" 旁邊應有數量輸入框，預設值為 1
      And 當前課程 "PHP 基礎課" 旁邊應有刪除按鈕

    Example: 修改當前課程的數量
      Given 銷售方案的 pbp_product_ids 包含商品 [100, 200]
      When 管理員將課程 100 的數量改為 2
      And 儲存銷售方案
      Then 銷售方案的 pbp_product_quantities 中課程 100 的數量應為 2

  # ========== 後置（狀態）— 移除當前課程 ==========

  Rule: 後置（狀態）- 管理員可以從銷售方案中移除當前課程

    Example: 移除當前課程後，pbp_product_ids 不包含課程 ID
      Given 銷售方案的 pbp_product_ids 包含商品 [100, 200]
      When 管理員從銷售方案中移除課程 100
      And 儲存銷售方案
      Then 銷售方案的 pbp_product_ids 應不包含 100
      And 銷售方案的 pbp_product_quantities 應不包含 key "100"

    Example: 移除當前課程不影響 bind_courses_data 課程授權
      Given 銷售方案的 pbp_product_ids 包含商品 [100, 200]
      And 銷售方案的 bind_courses_data 包含課程 100
      When 管理員從銷售方案中移除課程 100
      And 儲存銷售方案
      Then 銷售方案的 bind_courses_data 仍應包含課程 100
      # 注意：pbp_product_ids 影響庫存扣減與顯示，bind_courses_data 影響課程授權，兩者獨立

    Example: 僅包含非課程商品的銷售方案（純商品組合）
      Given 銷售方案的 pbp_product_ids 包含商品 [200]
      And 課程 100 已從 pbp_product_ids 中移除
      When 學員購買此銷售方案
      Then 學員仍應獲得課程 100 的授權（透過 bind_courses_data）
      And 庫存扣減僅影響商品 200

  # ========== 後置（狀態）— 原價計算 ==========

  Rule: 後置（狀態）- 原價參考值根據 pbp_product_ids 中的商品計算（含當前課程）

    Example: 包含當前課程時原價含課程價格
      Given 銷售方案的 pbp_product_ids 包含商品 [100, 200]
      And 銷售方案的 pbp_product_quantities 為 '{"100":1,"200":3}'
      Then 原價參考值應為 4500
      # 計算：$3,000 x 1 + $500 x 3 = $4,500

    Example: 移除當前課程後原價不含課程價格
      Given 銷售方案的 pbp_product_ids 包含商品 [200]
      And 銷售方案的 pbp_product_quantities 為 '{"200":3}'
      Then 原價參考值應為 1500
      # 計算：$500 x 3 = $1,500

  # ========== 後置（狀態）— 廢棄 exclude_main_course ==========

  Rule: 後置（狀態）- exclude_main_course 開關移除，改由 pbp_product_ids 列表決定

    Example: exclude_main_course 欄位不再出現在 UI 上
      When 管理員 "Admin" 開啟銷售方案編輯畫面
      Then 畫面中不應出現「排除目前課程」開關

    Example: API 回應中不再包含 exclude_main_course 欄位
      When 管理員 "Admin" 查詢銷售方案資料
      Then API 回應中不應包含 exclude_main_course 欄位

  # ========== 後置（狀態）— 向下相容遷移 ==========

  Rule: 後置（狀態）- 既有銷售方案的 exclude_main_course 遷移為 pbp_product_ids 列表

    Example: exclude_main_course=no 的既有方案，課程 ID 加入 pbp_product_ids
      Given 系統中有以下銷售方案（舊版格式）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | exclude_main_course |
        | 600       | 月費方案 | bundle      | 100            | 200             | no                  |
      When 系統執行向下相容遷移
      Then 銷售方案 600 的 pbp_product_ids 應為 [100, 200]
      And 銷售方案 600 的 pbp_product_quantities 應為 '{"100":1,"200":1}'

    Example: exclude_main_course=yes 的既有方案，維持不含課程 ID
      Given 系統中有以下銷售方案（舊版格式）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | exclude_main_course |
        | 600       | 純商品包 | bundle      | 100            | 200             | yes                 |
      When 系統執行向下相容遷移
      Then 銷售方案 600 的 pbp_product_ids 應為 [200]
      And 銷售方案 600 的 pbp_product_quantities 應為 '{"200":1}'

    Example: 無 exclude_main_course 欄位的既有方案，視為 no（含當前課程）
      Given 系統中有以下銷售方案（舊版格式）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | exclude_main_course |
        | 600       | 舊方案   | bundle      | 100            | 200             |                     |
      When 系統執行向下相容遷移
      Then 銷售方案 600 的 pbp_product_ids 應為 [100, 200]
