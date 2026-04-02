@ignore @command
Feature: 線性觀看切換完成限制

  當課程開啟線性觀看時，切換章節完成狀態 API 需額外驗證：
  1. 標記完成：章節必須處於「已解鎖」狀態（前一章已完成）
  2. 取消完成：線性觀看模式下禁止取消完成

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name           | _is_course | status  | enable_linear_viewing |
      | 100      | Python 從零到一 | yes        | publish | yes                   |
    And 課程 100 有以下章節（攤平排序）：
      | chapterId | post_title  | post_parent | menu_order |
      | 200       | 1-1 基礎語法 | 100         | 10         |
      | 201       | 1-2 變數型別 | 100         | 20         |
      | 202       | 1-3 流程控制 | 100         | 30         |
      | 203       | 2-1 函式入門 | 100         | 40         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 前置（狀態）- 標記完成驗證 ==========

  Rule: 前置（狀態）- 章節必須已解鎖才能標記為完成

    Example: 嘗試標記被鎖定的章節為完成時操作失敗
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作失敗，錯誤為「章節尚未解鎖，請先完成前面的章節」

    Example: 第一章永遠可標記為完成
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

    Example: 前一章已完成後可標記下一章為完成
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  # ========== 前置（狀態）- 取消完成禁止 ==========

  Rule: 前置（狀態）- 線性觀看模式下禁止取消章節完成

    Example: 嘗試取消已完成章節時操作失敗
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作失敗，錯誤為「線性觀看模式下無法取消章節完成」

    Example: 嘗試取消中間已完成章節時操作失敗
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作失敗，錯誤為「線性觀看模式下無法取消章節完成」

  # ========== 前置（狀態）- 功能關閉時 ==========

  Rule: 前置（狀態）- 線性觀看關閉時，切換完成不受額外限制

    Example: 線性觀看關閉時可自由標記任何章節為完成
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 切換章節 202 的完成狀態
      Then 操作成功

    Example: 線性觀看關閉時可自由取消完成
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應為空

  # ========== 後置（回應）- 完成後回傳下一個解鎖章節 ==========

  Rule: 後置（回應）- 標記完成後回傳 next_unlocked_chapter_id

    Example: 完成 1-1 後回傳 1-2 為下一個解鎖章節
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 回應中 next_unlocked_chapter_id 應為 201
      And 回應中 next_unlocked_chapter_icon_html 應不為空

    Example: 完成最後一章時 next_unlocked_chapter_id 為 null
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 203 的完成狀態
      Then 操作成功
      And 回應中 next_unlocked_chapter_id 應為 null
