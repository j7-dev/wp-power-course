@ignore @command
Feature: 移除排除目前課程並遷移

  移除「排除目前課程」（exclude_main_course）開關。
  目前課程改為銷售方案中的預設商品（quantity = 1），
  管理員可自由修改數量或將目前課程從方案中移除。

  遷移策略：
  - 舊方案 exclude_main_course = 'yes'：不修改 pbp_product_ids（課程本不在列表中）
  - 舊方案 exclude_main_course = 'no' 或無此欄位：將 link_course_id 加入 pbp_product_ids，
    並在 pbp_product_quantities 中設定該課程數量為 1
  - 遷移完成後刪除 exclude_main_course meta

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100     | PHP 基礎課 | yes        | publish | 3000          |

  # ========== 遷移：排除目前課程已啟用 ==========

  Rule: 遷移 - 舊方案 exclude_main_course = 'yes' 時不修改 pbp_product_ids

    Example: 排除目前課程已啟用的銷售方案保持原樣
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | exclude_main_course |
        | 500      | 加購套餐 | bundle      | 100            | yes                 |
      And 銷售方案 500 包含以下商品：
        | productId |
        | 200       |
        | 300       |
      When 執行外掛升級遷移
      Then 銷售方案 500 的 pbp_product_ids 為 [200, 300]
      And 銷售方案 500 不存在 exclude_main_course meta

  # ========== 遷移：排除目前課程未啟用 ==========

  Rule: 遷移 - 舊方案 exclude_main_course = 'no' 時將課程加入 pbp_product_ids

    Example: 排除目前課程未啟用的銷售方案自動加入課程
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | exclude_main_course |
        | 501      | 超值套餐 | bundle      | 100            | no                  |
      And 銷售方案 501 包含以下商品：
        | productId |
        | 200       |
        | 300       |
      When 執行外掛升級遷移
      Then 銷售方案 501 的 pbp_product_ids 為 [100, 200, 300]
      And 銷售方案 501 的 pbp_product_quantities 包含 {"100": 1}
      And 銷售方案 501 不存在 exclude_main_course meta

  Rule: 遷移 - 舊方案無 exclude_main_course meta 時視為 'no'

    Example: 無 exclude_main_course 欄位的銷售方案自動加入課程
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id |
        | 502      | 基礎套餐 | bundle      | 100            |
      And 銷售方案 502 沒有 exclude_main_course meta
      And 銷售方案 502 包含以下商品：
        | productId |
        | 200       |
      When 執行外掛升級遷移
      Then 銷售方案 502 的 pbp_product_ids 為 [100, 200]
      And 銷售方案 502 的 pbp_product_quantities 包含 {"100": 1}

  Rule: 遷移 - 若課程 ID 已存在於 pbp_product_ids 中則不重複加入

    Example: 課程已在 pbp_product_ids 中時不重複加入
      Given 課程 100 有以下銷售方案：
        | bundleId | name       | bundle_type | link_course_id | exclude_main_course |
        | 503      | 已含課程   | bundle      | 100            | no                  |
      And 銷售方案 503 包含以下商品：
        | productId |
        | 100       |
        | 200       |
      When 執行外掛升級遷移
      Then 銷售方案 503 的 pbp_product_ids 為 [100, 200]
      And 銷售方案 503 不存在 exclude_main_course meta

  # ========== 新建銷售方案行為 ==========

  Rule: 新建 - 建立銷售方案時目前課程為預設商品（quantity = 1）

    Example: 新建銷售方案時自動包含目前課程
      When 管理員 "Admin" 在課程 100 建立銷售方案：
        | name     | bundle_type | regular_price |
        | 新方案   | bundle      | 5000          |
      And 加入以下商品：
        | productId | quantity |
        | 200       | 3        |
      Then 操作成功
      And 新銷售方案的 pbp_product_ids 包含 100
      And 新銷售方案的 pbp_product_quantities 包含 {"100": 1, "200": 3}

  Rule: 新建 - 管理員可在建立後移除目前課程

    Example: 管理員移除目前課程後銷售方案不含該課程
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | regular_price |
        | 504      | 加購方案 | bundle      | 100            | 1000          |
      And 銷售方案 504 包含以下商品：
        | productId |
        | 100       |
        | 200       |
      When 管理員 "Admin" 更新銷售方案 504 的商品列表為：
        | productId | quantity |
        | 200       | 5        |
      Then 操作成功
      And 銷售方案 504 的 pbp_product_ids 為 [200]
      And 銷售方案 504 的 pbp_product_quantities 為 {"200": 5}

  # ========== UI 行為 ==========

  Rule: UI - 後台不再顯示「排除目前課程」開關

    Example: 銷售方案編輯介面無排除目前課程開關
      When 管理員 "Admin" 開啟銷售方案 500 的編輯介面
      Then 介面中不存在「排除目前課程」開關
      And 目前課程以一般商品的方式顯示在商品列表中（帶「目前課程」標籤）
      And 目前課程旁邊有數量輸入框與刪除按鈕
