@ignore @command
Feature: 線性觀看前端即時解鎖

  學員在啟用線性觀看的課程中標記章節完成/取消完成時，
  前端 JS 即時更新章節列表的鎖定/解鎖狀態，無需重新整理頁面。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | sequential_mode |
      | 100      | Python 入門 | yes        | publish | yes             |
    And 課程 100 有以下章節（扁平順序）：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 1          |
      | 201       | 1-1        | 200         | 1          |
      | 202       | 1-2        | 200         | 2          |
      | 203       | 第二章     | 100         | 2          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 標記完成後即時解鎖 ==========

  Rule: 標記章節完成後，toggle-finish API 回傳 unlocked_chapter_ids，前端即時解鎖

    Example: 完成第一章後，下一章即時從鎖定變為可存取
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      And 用戶 "Alice" 正在觀看章節 200
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then toggle-finish API 回傳 unlocked_chapter_ids 包含 201
      And 前端章節列表中章節 201 的鎖頭圖示移除
      And 章節 201 變為可點擊狀態
      And 章節 202、203 維持鎖定狀態

    Example: 完成最後一個被鎖定的前置章節後，多個章節解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 正在觀看章節 201
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then toggle-finish API 回傳 unlocked_chapter_ids 包含 202
      And 前端章節列表中章節 202 的鎖頭圖示移除

  # ========== 取消完成後即時重新鎖定 ==========

  Rule: 取消章節完成後，toggle-finish API 回傳 locked_chapter_ids，前端即時鎖定

    Example: 取消完成後，後續未完成的章節重新鎖定
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 無 finished_at
      And 用戶 "Alice" 正在觀看章節 200
      When 用戶 "Alice" 點擊「標示為未完成」按鈕
      Then toggle-finish API 回傳 locked_chapter_ids 包含 202
      And 前端章節列表中章節 202 顯示鎖頭圖示
      And 章節 201 維持可存取狀態（因其本身已完成）

  # ========== 前往下一章節按鈕行為 ==========

  Rule: 線性觀看模式下，下一章被鎖定時「前往下一章節」按鈕 disabled

    Example: 當前章節未完成，下一章被鎖定，按鈕為 disabled
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      And 用戶 "Alice" 正在觀看章節 200
      Then 「前往下一章節」按鈕應為 disabled 狀態
      And 按鈕 tooltip 應顯示「請先完成本章節」

    Example: 當前章節已完成，下一章解鎖，按鈕恢復可用
      Given 用戶 "Alice" 正在觀看章節 200
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then 「前往下一章節」按鈕應恢復為可點擊狀態

    Example: 非線性觀看課程，按鈕始終可用
      Given 系統中有以下課程：
        | courseId | name    | _is_course | status  | sequential_mode |
        | 101      | JS 課程 | yes        | publish | no              |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 在課程 101 無任何章節完成紀錄
      Then 「前往下一章節」按鈕應為可點擊狀態

  # ========== 影片自動跳轉行為 ==========

  Rule: 線性觀看模式下，下一章被鎖定時影片結束不自動跳轉

    Example: 影片播放結束，但下一章仍被鎖定
      Given 用戶 "Alice" 正在觀看章節 200 的影片
      And 章節 201 處於鎖定狀態
      When 影片播放結束
      Then 不觸發自動跳轉到下一章
      And 顯示提示「請先標示本章節為已完成，以解鎖下一章」

  # ========== 鎖定章節視覺狀態 ==========

  Rule: 鎖定的章節顯示鎖頭圖示和灰色文字

    Example: 章節列表中鎖定章節的視覺呈現
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 查看教室頁面的章節導航列表
      Then 章節 200 顯示正常文字和正常圖示（可觀看）
      And 章節 201、202、203 顯示鎖頭圖示
      And 章節 201、202、203 的文字為灰色

    Example: 學員點擊鎖定的章節，顯示友善提示
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 點擊鎖定的章節 201
      Then 顯示提示對話框：「請先完成前面的章節才能觀看此章節」
      And 不跳轉頁面
