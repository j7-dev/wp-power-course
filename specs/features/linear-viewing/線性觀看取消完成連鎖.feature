@ignore @command
Feature: 線性觀看取消完成連鎖

  當課程開啟線性觀看（enable_sequential = yes）時，
  學員取消完成某章節後，該章節之後所有未完成的章節會重新被鎖定。
  取消完成時需彈出確認提示，告知後續章節將被鎖定。
  完成章節時，API 回傳下一個解鎖的章節 ID，前端即時更新 DOM。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_sequential |
      | 100      | PHP 基礎課 | yes        | publish | yes               |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 1-1        | 100         | 1          |
      | 201       | 1-2        | 100         | 2          |
      | 202       | 1-3        | 100         | 3          |
      | 203       | 2-1        | 100         | 4          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 取消完成後重新鎖定 ==========

  Rule: 取消完成 - 後續章節重新鎖定

    Example: 取消完成 1-1 後，1-2 以後的章節被鎖定
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 無 finished_at
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應為空
      And 用戶 "Alice" 不可存取章節 201
      And 用戶 "Alice" 不可存取章節 202

    Example: 取消完成中間章節後，後續章節被鎖定但前面章節不受影響
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應為空
      And 用戶 "Alice" 可存取章節 200
      And 用戶 "Alice" 可存取章節 201
      And 用戶 "Alice" 不可存取章節 202

  # ========== 完成後 API 回傳下一個解鎖 ID ==========

  Rule: 完成章節 - API 回傳 next_unlocked_chapter_id

    Example: 完成 1-1 後 API 回傳 1-2 的 ID
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And API 回傳 next_unlocked_chapter_id 為 201

    Example: 完成最後一個章節後 API 回傳 next_unlocked_chapter_id 為 null
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 無 finished_at
      When 用戶 "Alice" 切換章節 203 的完成狀態
      Then 操作成功
      And API 回傳 next_unlocked_chapter_id 為 null

    Example: 線性觀看關閉時 API 不回傳 next_unlocked_chapter_id
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_sequential |
        | 101      | 自由課程   | yes        | publish | no                |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | A-1        | 101         | 1          |
        | 401       | A-2        | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 在章節 400 無 finished_at
      When 用戶 "Alice" 切換章節 400 的完成狀態
      Then 操作成功
      And API 回傳中不包含 next_unlocked_chapter_id 欄位

  # ========== 線性觀看關閉時取消完成不影響存取 ==========

  Rule: 線性觀看關閉 - 取消完成不影響其他章節存取

    Example: enable_sequential 為 no 時取消完成不鎖定後續章節
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_sequential |
        | 101      | 自由課程   | yes        | publish | no                |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | A-1        | 101         | 1          |
        | 401       | A-2        | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 在章節 400 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 400 的完成狀態
      Then 操作成功
      And 用戶 "Alice" 可存取章節 401

  # ========== 取消完成確認提示 ==========

  Rule: 取消完成確認 - 線性觀看模式下取消完成前需確認

    Example: 取消完成時前端應顯示確認對話框
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 點擊章節 200 的完成按鈕
      Then 前端顯示確認對話框：「取消完成此章節後，後續章節將重新鎖定，確定要繼續嗎？」
      And 用戶確認後才送出 API 請求

  # ========== 章節列表鎖定狀態 ==========

  Rule: 章節列表 - 顯示正確的鎖定/解鎖狀態

    Example: 取得章節列表時包含 is_locked 狀態
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 取得課程 100 的章節列表
      Then 章節 200 的 is_locked 為 false
      And 章節 201 的 is_locked 為 false
      And 章節 202 的 is_locked 為 true
      And 章節 203 的 is_locked 為 true

    Example: 所有章節都未完成時只有第一個不鎖定
      When 用戶 "Alice" 取得課程 100 的章節列表
      Then 章節 200 的 is_locked 為 false
      And 章節 201 的 is_locked 為 true
      And 章節 202 的 is_locked 為 true
      And 章節 203 的 is_locked 為 true
