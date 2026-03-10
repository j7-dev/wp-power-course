@ignore
Feature: 查詢學員活動紀錄

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email            | role          |
      | 1      | Admin   | admin@test.com   | administrator |
      | 2      | Alice   | alice@test.com   | subscriber    |
      | 3      | Bob     | bob@test.com     | subscriber    |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 系統中有以下學員活動紀錄（pc_student_logs）：
      | id | user_id | course_id | chapter_id | log_type        | title                  | user_ip     | created_at          |
      | 1  | 2       | 100       | null       | course_granted  | Alice 獲得 PHP 課程存取 | 192.168.1.1 | 2024-03-01 10:00:00 |
      | 2  | 2       | 100       | 201        | chapter_finish  | Alice 完成第一章       | 192.168.1.1 | 2024-03-10 14:00:00 |
      | 3  | 3       | 100       | null       | course_granted  | Bob 獲得 PHP 課程存取  | 10.0.0.5    | 2024-03-15 09:00:00 |
      | 4  | 2       | 100       | 202        | chapter_finish  | Alice 完成第二章       | 192.168.1.1 | 2024-03-20 16:00:00 |
      | 5  | 2       | 101       | null       | course_granted  | Alice 獲得 React 課程  | 192.168.1.1 | 2024-04-01 11:00:00 |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- posts_per_page 預設為 20

    Example: 未指定 posts_per_page 時預設每頁 20 筆
      When 管理員 "Admin" 查詢學員活動紀錄，不帶任何參數
      Then 操作成功
      And 回應中每頁最多回傳 20 筆資料

  Rule: 前置（參數）- paged 預設為 1

    Example: 未指定 paged 時從第一頁開始
      When 管理員 "Admin" 查詢學員活動紀錄，不帶任何參數
      Then 操作成功
      And 回應中包含第一頁的活動紀錄

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 不帶篩選條件時回傳所有紀錄

    Example: 查詢全部活動紀錄時回傳 5 筆
      When 管理員 "Admin" 查詢學員活動紀錄，不帶任何參數
      Then 操作成功
      And 回應中紀錄總數應為 5

  Rule: 後置（回應）- 支援依 course_id 篩選

    Example: 只查詢課程 100 的活動紀錄
      When 管理員 "Admin" 查詢學員活動紀錄，參數如下：
        | course_id |
        | 100       |
      Then 操作成功
      And 回應中紀錄總數應為 4
      And 回應中應不包含 id 5（屬於課程 101）

  Rule: 後置（回應）- 支援依 user_id 篩選

    Example: 只查詢 userId 2（Alice）的活動紀錄
      When 管理員 "Admin" 查詢學員活動紀錄，參數如下：
        | user_id |
        | 2       |
      Then 操作成功
      And 回應中紀錄總數應為 4
      And 回應中應不包含 id 3（屬於 userId 3）

  Rule: 後置（回應）- 支援依 log_type 篩選

    Example: 只查詢 chapter_finish 類型的紀錄
      When 管理員 "Admin" 查詢學員活動紀錄，參數如下：
        | log_type       |
        | chapter_finish |
      Then 操作成功
      And 回應中紀錄總數應為 2
      And 回應中所有紀錄的 log_type 應為 "chapter_finish"

  Rule: 後置（回應）- 支援同時依 course_id 與 user_id 篩選

    Example: 查詢 Alice 在課程 100 的所有活動
      When 管理員 "Admin" 查詢學員活動紀錄，參數如下：
        | course_id | user_id |
        | 100       | 2       |
      Then 操作成功
      And 回應中紀錄總數應為 3
      And 回應中包含 id 1、id 2、id 4

  Rule: 後置（回應）- 每筆紀錄包含必要欄位

    Example: 回應中每筆活動紀錄包含所有欄位
      When 管理員 "Admin" 查詢學員活動紀錄，不帶任何參數
      Then 操作成功
      And 回應中每筆紀錄應包含欄位：
        | 欄位       |
        | id         |
        | user_id    |
        | course_id  |
        | chapter_id |
        | log_type   |
        | title      |
        | content    |
        | user_ip    |
        | created_at |

  Rule: 後置（回應）- 回應包含分頁資訊

    Example: 查詢結果包含 pagination 分頁資訊
      When 管理員 "Admin" 查詢學員活動紀錄，不帶任何參數
      Then 操作成功
      And 回應中應包含分頁資訊 total 及 total_pages
