@ignore
Feature: 匯出學員CSV

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email            | role          |
      | 1      | Admin   | admin@test.com   | administrator |
      | 2      | Alice   | alice@test.com   | subscriber    |
      | 3      | Bob     | bob@test.com     | subscriber    |
      | 4      | Charlie | charlie@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date | course_granted_at   | finished_at         |
      | 2      | 100      | 0           | 2024-01-15 10:00:00 | 2024-02-20 16:00:00 |
      | 3      | 100      | 1893456000  | 2024-02-01 09:00:00 | null                |
      | 4      | 101      | 0           | 2024-03-10 08:00:00 | null                |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- course_id 對應的課程必須存在

    Example: 匯出不存在課程的學員 CSV 時操作失敗
      When 管理員 "Admin" 匯出課程 9999 的學員 CSV
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- course_id 不可為空

    Example: 未提供 course_id 時操作失敗
      When 管理員 "Admin" 匯出學員 CSV（未指定 course_id）
      Then 操作失敗，錯誤訊息包含 "course_id"

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 回應 Content-Type 為 text/csv

    Example: 匯出成功時 HTTP Response Content-Type 應為 text/csv
      When 管理員 "Admin" 匯出課程 100 的學員 CSV
      Then 操作成功
      And HTTP Response Content-Type 應為 "text/csv"

  Rule: 後置（回應）- CSV 第一列為欄位標頭

    Example: 匯出的 CSV 首行包含正確欄位名稱
      When 管理員 "Admin" 匯出課程 100 的學員 CSV
      Then 操作成功
      And CSV 第一列應包含以下標頭欄位：
        | 標頭欄位        |
        | email           |
        | display_name    |
        | expire_date     |
        | course_granted_at|
        | finished_at     |
        | course_progress |

  Rule: 後置（回應）- 只回傳該課程的學員資料

    Example: 匯出課程 100 的學員時不包含課程 101 的學員
      When 管理員 "Admin" 匯出課程 100 的學員 CSV
      Then 操作成功
      And CSV 資料列應包含 alice@test.com（課程 100 學員）
      And CSV 資料列應包含 bob@test.com（課程 100 學員）
      And CSV 資料列應不包含 charlie@test.com（課程 101 學員）

  Rule: 後置（回應）- expire_date 為 0 時顯示為「永久」或對應格式化字串

    Example: 永久存取的學員其 expire_date 欄位應格式化為易讀字串
      When 管理員 "Admin" 匯出課程 100 的學員 CSV
      Then 操作成功
      And alice@test.com 那列的 expire_date 應為 "永久" 或 "0"

  Rule: 後置（回應）- finished_at 為空時該欄位顯示空值

    Example: 尚未完課的學員其 finished_at 欄位應為空
      When 管理員 "Admin" 匯出課程 100 的學員 CSV
      Then 操作成功
      And bob@test.com 那列的 finished_at 應為空值
