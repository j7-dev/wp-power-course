@ignore
Feature: 取得課程進度

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title      | post_type  | post_parent | parent_course_id | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100         | 100              | 0          |
      | 202       | 第二章：語法基礎 | pc_chapter | 100         | 100              | 1          |
      | 203       | 第三章：函數    | pc_chapter | 100         | 100              | 2          |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date | last_visit_chapter_id | last_visit_at       |
      | 2      | 100      | 0           | 201                   | 2024-04-01 10:00:00 |
    And 學員 "Alice" 已完成以下章節：
      | chapterId | finished_at         |
      | 201       | 2024-04-01 14:00:00 |
      | 202       | 2024-04-02 09:00:00 |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 學員必須已有該課程的存取權

    Example: 無課程存取權的學員查詢進度時操作失敗
      When 學員 "Bob" 查詢課程 100 的進度
      Then 操作失敗，錯誤訊息包含 "未擁有課程存取權"

  Rule: 前置（狀態）- 課程存取未到期

    Example: 已到期的學員查詢進度時回傳 expired 狀態
      Given 學員 userId 2 在課程 100 的 expire_date 為 1000000000（已過期）
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 expired 應為 true

  # ========== 前置（參數）==========

  Rule: 前置（參數）- course_id 不可為空

    Example: 未提供 course_id 時操作失敗
      When 學員 "Alice" 查詢課程進度（未指定 course_id）
      Then 操作失敗

  Rule: 前置（參數）- 學員必須已登入（非訪客）

    Example: 訪客查詢課程進度時操作失敗
      When 訪客查詢課程 100 的進度
      Then 操作失敗，錯誤訊息包含 "請先登入"

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 回傳正確的進度百分比

    Example: 完成 2/3 章節時進度約為 66.67
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 progress 應約為 66.67（允許誤差 0.01）

  Rule: 後置（回應）- 回傳已完成的章節 IDs 列表

    Example: 進度查詢結果包含已完成章節 id 列表
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 finished_chapters 應包含 chapterId 201
      And 回應中 finished_chapters 應包含 chapterId 202
      And 回應中 finished_chapters 應不包含 chapterId 203

  Rule: 後置（回應）- 回傳 total_chapters（章節總數）

    Example: total_chapters 反映課程中的章節總數
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 total_chapters 應為 3

  Rule: 後置（回應）- 回傳 last_visit_info（最後造訪章節資訊）

    Example: 進度查詢包含最後造訪章節 id 與時間
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 last_visit_info.chapter_id 應為 201
      And 回應中 last_visit_info.last_visit_at 應為 "2024-04-01 10:00:00"

  Rule: 後置（回應）- 回傳 expire_date（學員存取到期時間）

    Example: 永久存取學員的 expire_date 應為 0
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 expire_date 應為 0

  Rule: 後置（回應）- 全部章節完成時 progress 為 100

    Example: 完成所有章節後進度為 100
      Given 學員 "Alice" 已另外完成章節 203
      When 學員 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 progress 應為 100
