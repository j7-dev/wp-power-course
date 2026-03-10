@ignore
Feature: 查詢學員列表

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email              | role          |
      | 1      | Admin   | admin@test.com     | administrator |
      | 2      | Alice   | alice@test.com     | subscriber    |
      | 3      | Bob     | bob@test.com       | subscriber    |
      | 4      | Charlie | charlie@test.com   | subscriber    |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date | course_granted_at   |
      | 2      | 100      | 0           | 2024-01-15 10:00:00 |
      | 3      | 100      | 1893456000  | 2024-02-20 09:00:00 |
      | 4      | 101      | 0           | 2024-03-10 08:00:00 |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- posts_per_page 預設為 20

    Example: 未指定 posts_per_page 時預設每頁 20 筆
      When 管理員 "Admin" 查詢課程 100 的學員列表，不帶其他參數
      Then 操作成功
      And 回應中每頁最多回傳 20 筆資料

  Rule: 前置（參數）- paged 預設為 1

    Example: 未指定 paged 時從第一頁開始
      When 管理員 "Admin" 查詢課程 100 的學員列表，不帶其他參數
      Then 操作成功
      And 回應中包含第一頁的學員資料

  Rule: 前置（參數）- search_field 只允許 email、name、id、default

    Scenario Outline: 設定不合法的 search_field 時操作失敗
      When 管理員 "Admin" 查詢學員列表，參數如下：
        | search       | search_field   |
        | alice        | <search_field> |
      Then 操作失敗

      Examples:
        | search_field |
        | phone        |
        | username     |
        | address      |

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 依 meta_value（課程 id）篩選該課程的學員

    Example: 查詢課程 100 的學員只回傳 Alice 和 Bob
      When 管理員 "Admin" 查詢課程 100 的學員列表，不帶其他參數
      Then 操作成功
      And 回應中應包含 userId 2（Alice）
      And 回應中應包含 userId 3（Bob）
      And 回應中應不包含 userId 4（Charlie，只有課程 101）

  Rule: 後置（回應）- 支援 email 關鍵字搜尋

    Example: 搜尋 "alice" 時只回傳 email 包含 alice 的學員
      When 管理員 "Admin" 查詢課程 100 的學員列表，參數如下：
        | search | search_field |
        | alice  | email        |
      Then 操作成功
      And 回應中應只包含 userId 2（Alice）

  Rule: 後置（回應）- 支援 id 精確搜尋

    Example: 以 userId 搜尋時只回傳指定學員
      When 管理員 "Admin" 查詢課程 100 的學員列表，參數如下：
        | search | search_field |
        | 3      | id           |
      Then 操作成功
      And 回應中應只包含 userId 3（Bob）

  Rule: 後置（回應）- 每筆學員資料包含課程相關 meta

    Example: 學員列表中每筆包含 expire_date 與 course_granted_at
      When 管理員 "Admin" 查詢課程 100 的學員列表，不帶其他參數
      Then 操作成功
      And 回應中每筆學員資料應包含欄位：
        | 欄位             |
        | user_id          |
        | display_name     |
        | email            |
        | expire_date      |
        | course_granted_at|
        | course_progress  |

  Rule: 後置（回應）- 回應包含分頁資訊（total 與 total_pages）

    Example: 查詢結果包含分頁 meta
      When 管理員 "Admin" 查詢課程 100 的學員列表，不帶其他參數
      Then 操作成功
      And 回應中 pagination.total 應為 2
      And 回應中 pagination.total_pages 應為 1
