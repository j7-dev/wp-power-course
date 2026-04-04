@ignore @command
Feature: 移除排除目前課程功能

  移除「排除目前課程」(exclude_main_course) 開關。
  新建銷售方案時，預設帶入「目前課程」×1，管理員可像其他商品一樣刪除它。
  舊資料遷移：exclude=yes 的方案不變，exclude=no（或不存在）的方案自動補入目前課程。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 系統中有以下 WooCommerce 商品：
      | productId | name    | price |
      | 200       | T-shirt | 500   |

  # ========== 新建方案 ==========

  Rule: 後置（狀態）- 新建銷售方案時，預設帶入目前課程（link_course_id）到 pbp_product_ids

    Example: 新建方案預設包含目前課程
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name     | link_course_id | price | bundle_type |
        | 月費方案 | 100            | 399   | bundle      |
      Then 操作成功
      And 銷售方案的 pbp_product_ids 應包含 100

  # ========== 目前課程可刪除 ==========

  Rule: 後置（狀態）- 管理員可從銷售方案中移除目前課程

    Example: 移除目前課程後方案不包含該課程
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 300       | 月費方案 | bundle      | 100            | 100,200         |
      When 管理員 "Admin" 更新銷售方案 300 的 pbp_product_ids 為：
        | product_id |
        | 200        |
      Then 操作成功
      And 銷售方案 300 的 pbp_product_ids 應不包含 100

  # ========== exclude_main_course 開關移除 ==========

  Rule: 後置（狀態）- API 回應不再包含 exclude_main_course 欄位

    Example: 查詢銷售方案時不回傳 exclude_main_course
      Given 系統中有以下銷售方案：
        | productId | name     | bundle_type | link_course_id |
        | 300       | 月費方案 | bundle      | 100            |
      When 查詢銷售方案 300 的詳情
      Then 回應中不應包含 "exclude_main_course" 欄位

  # ========== 舊資料遷移 ==========

  Rule: 後置（狀態）- 舊方案 exclude_main_course=yes 時，pbp_product_ids 不變

    Example: 舊方案 exclude=yes，不自動補入目前課程
      Given 系統中有以下銷售方案（舊版資料）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | exclude_main_course |
        | 300       | 月費方案 | bundle      | 100            | 200             | yes                 |
      When 系統執行資料遷移
      Then 銷售方案 300 的 pbp_product_ids 應為 [200]

  Rule: 後置（狀態）- 舊方案 exclude_main_course=no 或不存在時，自動補入目前課程 ×1

    Example: 舊方案 exclude=no，自動補入目前課程
      Given 系統中有以下銷售方案（舊版資料）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids | exclude_main_course |
        | 300       | 年度套餐 | bundle      | 100            | 200             | no                  |
      When 系統執行資料遷移
      Then 銷售方案 300 的 pbp_product_ids 應為 [100, 200]
      And 銷售方案 300 的 pbp_product_quantities 應包含 {"100": 1}

    Example: 舊方案無 exclude_main_course 欄位，自動補入目前課程
      Given 系統中有以下銷售方案（舊版資料）：
        | productId | name     | bundle_type | link_course_id | pbp_product_ids |
        | 400       | 超值套餐 | bundle      | 100            | 200             |
      When 系統執行資料遷移
      Then 銷售方案 400 的 pbp_product_ids 應為 [100, 200]
      And 銷售方案 400 的 pbp_product_quantities 應包含 {"100": 1}
