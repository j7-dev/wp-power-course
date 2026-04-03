@ignore @command
Feature: 線性觀看模式下完成章節

  開啟線性觀看的課程中，章節完成操作為單向（只能標記完成，不可取消完成）。
  完成章節後即時解鎖下一個章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | linear_chapter_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                 |
    And 課程 100 有以下章節（攤平順序）：
      | chapterId | post_title | menu_order | post_parent |
      | 200       | 第一章     | 1          | 100         |
      | 201       | 1-1        | 1          | 200         |
      | 202       | 1-2        | 2          | 200         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 章節必須為已解鎖狀態才能完成

    Example: 嘗試完成被鎖定的章節時失敗
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作失敗，錯誤為「此章節尚未解鎖，請先完成前面的章節」

  # ========== 單向操作 ==========

  Rule: 線性觀看模式下禁止取消完成

    Example: 嘗試取消已完成章節時失敗
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作失敗，錯誤為「線性觀看模式下無法取消已完成的章節」

    Example: 關閉線性觀看後可以取消完成
      Given 課程 100 的 linear_chapter_mode 為 "no"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應為空

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 完成章節後 API 回應包含下一章解鎖資訊

    Example: 完成第一章後回應包含下一章解鎖資訊
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應不為空
      And 回應資料應包含：
        | 欄位                       | 期望值 |
        | is_this_chapter_finished   | true   |
        | next_chapter_id            | 201    |
        | next_chapter_unlocked      | true   |
      And action "power_course_chapter_finished" 應被觸發

  Rule: 後置（狀態）- 完成最後一章不回傳 next_chapter

    Example: 完成最後一個章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 無 finished_at
      When 用戶 "Alice" 切換章節 202 的完成狀態
      Then 操作成功
      And 回應資料應包含：
        | 欄位                       | 期望值 |
        | is_this_chapter_finished   | true   |
        | next_chapter_id            | null   |
      And 用戶 "Alice" 在課程 100 的進度應為 100
      And action "power_course_course_finished" 應以參數 (100, 2) 被觸發
