@ignore
Feature: 查詢課程列表

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name            | _is_course | status  | price | is_popular | created_at          |
      | 100      | PHP 基礎入門    | yes        | publish | 1200  | no         | 2024-01-10 10:00:00 |
      | 101      | React 實戰課    | yes        | publish | 2000  | yes        | 2024-02-15 09:00:00 |
      | 102      | Vue 完整課程    | yes        | draft   | 1800  | no         | 2024-03-20 08:00:00 |
      | 103      | Node.js 後端開發 | yes        | publish | 2500  | yes        | 2024-04-05 11:00:00 |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- posts_per_page 預設為 10

    Example: 未指定 posts_per_page 時使用預設值 10
      When 管理員 "Admin" 查詢課程列表，不帶任何參數
      Then 操作成功
      And 回應中每頁最多回傳 10 筆資料

  Rule: 前置（參數）- paged 預設為 1

    Example: 未指定 paged 時從第一頁開始
      When 管理員 "Admin" 查詢課程列表，不帶任何參數
      Then 操作成功
      And 回應中包含第一頁的課程資料

  Rule: 前置（參數）- status 預設包含 publish 及 draft

    Example: 未指定 status 時同時回傳 publish 和 draft 課程
      When 管理員 "Admin" 查詢課程列表，不帶任何參數
      Then 操作成功
      And 回應中應包含課程 100（publish）
      And 回應中應包含課程 102（draft）

  Rule: 前置（參數）- order 只允許 ASC 或 DESC

    Scenario Outline: 設定不合法的 order 值時操作失敗
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | order   |
        | <order> |
      Then 操作失敗

      Examples:
        | order   |
        | up      |
        | down    |
        | random  |

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 回應 HTTP Header 包含 X-WP-Total（總課程數）

    Example: 查詢所有課程時 X-WP-Total 反映正確總數
      When 管理員 "Admin" 查詢課程列表，不帶任何參數
      Then 操作成功
      And HTTP Header X-WP-Total 應為 4

  Rule: 後置（回應）- 回應 HTTP Header 包含 X-WP-TotalPages（總頁數）

    Example: posts_per_page 為 2 時 X-WP-TotalPages 應為 2
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | posts_per_page |
        | 2              |
      Then 操作成功
      And HTTP Header X-WP-TotalPages 應為 2

  Rule: 後置（回應）- 支援依 status 篩選

    Example: 只查詢 publish 課程時不回傳 draft
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | status  |
        | publish |
      Then 操作成功
      And 回應中應包含課程 100、101、103
      And 回應中應不包含課程 102（draft）

  Rule: 後置（回應）- 支援關鍵字搜尋課程名稱

    Example: 搜尋 "React" 時只回傳名稱包含 React 的課程
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | search |
        | React  |
      Then 操作成功
      And 回應中應只包含課程 101（React 實戰課）

  Rule: 後置（回應）- 支援依 order DESC 排序

    Example: 以 date DESC 排序時最新建立的課程排在最前
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | orderby | order |
        | date    | DESC  |
      Then 操作成功
      And 回應中第一筆課程應為 courseId 103（2024-04-05 建立）

  Rule: 後置（回應）- 每筆資料包含必要欄位

    Example: 回應中每筆課程包含 id、name、status、price 等欄位
      When 管理員 "Admin" 查詢課程列表，不帶任何參數
      Then 操作成功
      And 回應中每筆課程應包含欄位：
        | 欄位           |
        | id             |
        | name           |
        | status         |
        | price          |
        | limit_type     |
        | total_students |
        | created_at     |
