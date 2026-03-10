@ignore
Feature: 取得課程詳情

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email            | role          |
      | 1      | Admin   | admin@test.com   | administrator |
      | 2      | Alice   | alice@test.com   | subscriber    |
      | 10     | Teacher | teacher@test.com | editor        |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  | price | limit_type | teacher_ids |
      | 100      | PHP 基礎入門 | yes        | publish | 1200  | fixed      | 10          |
    And 課程 100 有以下章節：
      | chapterId | post_title      | post_type  | post_parent | parent_course_id | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100         | 100              | 0          |
      | 202       | 第二章：語法基礎 | pc_chapter | 100         | 100              | 1          |
      | 203       | 1-1 安裝 PHP   | pc_chapter | 201         | 100              | 0          |
    And 課程 100 有以下銷售方案（bundle_products）：
      | productId | name         | bundle_type    | link_course_ids |
      | 500       | 基礎方案     | single_course  | 100             |
      | 501       | 年費方案     | annual_course  | 100             |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date |
      | 2      | 100      | 0           |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 指定 id 對應的課程必須存在

    Example: 查詢不存在的課程時操作失敗
      When 管理員 "Admin" 查詢課程 9999 的詳情
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- id 必須為正整數

    Example: 傳入非正整數 id 時操作失敗
      When 管理員 "Admin" 查詢課程 0 的詳情
      Then 操作失敗

  Rule: 前置（參數）- 指定商品的 _is_course 必須為 yes

    Example: 查詢非課程商品時操作失敗
      Given 系統中有一個 WooCommerce 商品 id 999，_is_course 為 "no"
      When 管理員 "Admin" 查詢課程 999 的詳情
      Then 操作失敗，錯誤訊息包含 "_is_course"

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 回應包含完整課程 meta 資料

    Example: 管理員取得課程詳情包含所有 meta 欄位
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 操作成功
      And 回應資料應包含以下欄位：
        | 欄位              | 期望值       |
        | id                | 100          |
        | name              | PHP 基礎入門 |
        | status            | publish      |
        | price             | 1200         |
        | limit_type        | fixed        |
        | _is_course        | yes          |

  Rule: 後置（回應）- 回應包含巢狀章節列表（按 menu_order ASC）

    Example: 課程詳情中章節依 menu_order 排序並包含子章節
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 操作成功
      And 回應中 chapters 第一筆應為 chapterId 201（menu_order 0）
      And 回應中 chapters 第二筆應為 chapterId 202（menu_order 1）
      And chapterId 201 下應有子章節 chapterId 203

  Rule: 後置（回應）- 回應包含銷售方案列表

    Example: 課程詳情中包含關聯的 bundle_products 清單
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 操作成功
      And 回應中 bundle_products 應包含 productId 500
      And 回應中 bundle_products 應包含 productId 501

  Rule: 後置（回應）- 回應包含 teacher_ids 資料

    Example: 課程詳情中包含講師 id 列表
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 操作成功
      And 回應中 teacher_ids 應包含 userId 10

  Rule: 後置（回應）- 學員也可以取得課程詳情（有存取權時）

    Example: 有存取權的學員查詢課程詳情成功
      When 學員 "Alice" 查詢課程 100 的詳情
      Then 操作成功
      And 回應資料包含 id 為 100 的課程資料
