@ignore
Feature: 切換章節完成狀態

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email         |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 系統中有以下章節：
      | chapterId | post_title      | post_type  | post_parent | parent_course_id | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100         | 100              | 0          |
      | 202       | 第二章：語法基礎 | pc_chapter | 100         | 100              | 1          |
      | 203       | 第三章：函數    | pc_chapter | 100         | 100              | 2          |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date |
      | 2      | 100      | 0           |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 學員必須已有該章節所屬課程的存取權

    Example: 無課程存取權的學員無法標記章節完成
      When 學員 "Bob" 切換章節 201 完成狀態
      Then 操作失敗，錯誤訊息包含 "未擁有課程存取權"

  Rule: 前置（狀態）- 課程存取未到期（expire_date 為 0 代表永久）

    Example: 課程已到期的學員無法標記章節完成
      Given 學員 userId 2 在課程 100 的 expire_date 為 1000000000（已過期）
      When 學員 "Alice" 切換章節 201 完成狀態
      Then 操作失敗，錯誤訊息包含 "課程已到期"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- chapter_id 必須存在且 post_type 為 pc_chapter

    Example: 傳入不存在的 chapter_id 時操作失敗
      When 學員 "Alice" 切換章節 9999 完成狀態
      Then 操作失敗，錯誤訊息包含 "找不到章節"

  Rule: 前置（參數）- user_id 必須已登入（非訪客）

    Example: 訪客無法切換章節完成狀態
      When 訪客切換章節 201 完成狀態
      Then 操作失敗，錯誤訊息包含 "請先登入"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 標記完成時新增 chaptermeta finished_at 紀錄

    Example: 學員成功標記章節為完成
      Given 學員 "Alice" 尚未完成章節 201
      When 學員 "Alice" 切換章節 201 完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  Rule: 後置（狀態）- 標記完成時觸發 power_course_chapter_finished action

    Example: 標記完成後觸發 chapter_finished action
      Given 學員 "Alice" 尚未完成章節 201
      When 學員 "Alice" 切換章節 201 完成狀態
      Then 操作成功
      And action "power_course_chapter_finished" 應以參數 (201, 100, 2) 被觸發

  Rule: 後置（狀態）- 取消完成時刪除 chaptermeta finished_at 紀錄

    Example: 學員成功取消章節完成標記
      Given 學員 "Alice" 已完成章節 201
      When 學員 "Alice" 切換章節 201 完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應不存在

  Rule: 後置（狀態）- 取消完成時觸發 power_course_chapter_unfinished action

    Example: 取消完成後觸發 chapter_unfinished action
      Given 學員 "Alice" 已完成章節 201
      When 學員 "Alice" 切換章節 201 完成狀態
      Then 操作成功
      And action "power_course_chapter_unfinished" 應以參數 (201, 100, 2) 被觸發

  Rule: 後置（狀態）- 標記所有章節完成後觸發 power_course_course_finished action

    Example: 完成最後一章後課程進度達 100% 觸發課程完成 action
      Given 學員 "Alice" 已完成章節 201
      And 學員 "Alice" 已完成章節 202
      And 學員 "Alice" 尚未完成章節 203
      When 學員 "Alice" 切換章節 203 完成狀態
      Then 操作成功
      And action "power_course_course_finished" 應以參數 (100, 2) 被觸發
      And 課程 100 對用戶 "Alice" 的 coursemeta finished_at 應不為空

  Rule: 後置（狀態）- 課程進度在每次切換後重新計算

    Example: 完成 2/3 章節後進度應約為 66.67
      Given 學員 "Alice" 已完成章節 201
      And 學員 "Alice" 尚未完成章節 202
      And 學員 "Alice" 尚未完成章節 203
      When 學員 "Alice" 切換章節 202 完成狀態
      Then 操作成功
      And 課程 100 對用戶 "Alice" 的進度應為 66.67（允許誤差 0.01）
