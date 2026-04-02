@ignore @query
Feature: 線性觀看章節存取控制

  開啟線性觀看的課程中，學員必須依照章節排序（攤平後）逐步完成前面的章節，才能存取後面的章節。
  第一個章節永遠可存取。管理員不受此限制。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
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

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 線性觀看開啟時，第一個章節（排序最前面）永遠可存取

    Example: 學員無任何完成紀錄時可存取第一章
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 存取章節 200
      Then 存取成功，顯示章節 200 的內容

  Rule: 前置（狀態）- 學員完成前一章後，下一章解鎖

    Example: 完成 1-1 後可存取 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 存取章節 201
      Then 存取成功，顯示章節 201 的內容

    Example: 完成 1-1、1-2、1-3 後可存取 2-1
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 存取章節 203
      Then 存取成功，顯示章節 203 的內容

  Rule: 前置（狀態）- 前一章未完成時，後續章節被鎖定

    Example: 1-1 未完成時無法存取 1-2
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 存取章節 201
      Then 存取被鎖定，導向章節 200
      And 頁面顯示提示訊息「請先完成前面的章節，才能觀看此章節」

    Example: 跳過中間章節時無法存取後續章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 存取章節 202
      Then 存取被鎖定，導向章節 201
      And 頁面顯示提示訊息「請先完成前面的章節，才能觀看此章節」

  Rule: 前置（狀態）- 導向目標為「攤平排序中第一個未完成的章節」

    Example: 學員嘗試存取 2-1 但 1-2 未完成，導向 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 存取章節 203
      Then 存取被鎖定，導向章節 201

  # ========== 前置（狀態）- 管理員豁免 ==========

  Rule: 前置（狀態）- 管理員不受線性觀看限制

    Example: 管理員可自由存取任何章節
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 管理員 "Admin" 存取章節 203
      Then 存取成功，顯示章節 203 的內容

  # ========== 前置（狀態）- 功能關閉時 ==========

  Rule: 前置（狀態）- 線性觀看關閉時，所有章節自由存取

    Example: 線性觀看關閉時學員可存取任何章節
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 存取章節 203
      Then 存取成功，顯示章節 203 的內容

  # ========== 邊界情境 ==========

  Rule: 邊界情境 - 中途開啟線性觀看時，依連續完成鏈判斷解鎖範圍

    Example: 學員之前跳著完成，開啟後依連續完成鏈解鎖
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 存取章節 202
      Then 存取被鎖定，導向章節 201
      # 雖然 1-3 之前已完成，但因 1-2 未完成，連續完成鏈斷裂，1-3 仍被鎖定

    Example: 連續完成鏈完整時可存取下一章
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 無 finished_at
      When 用戶 "Alice" 存取章節 202
      Then 存取成功，顯示章節 202 的內容

  Rule: 邊界情境 - 課程僅有一個章節時，該章節永遠可存取

    Example: 單章節課程不受線性觀看影響
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_viewing |
        | 101      | 單章節課程 | yes        | publish | yes                   |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | 唯一章節   | 101         | 10         |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      When 用戶 "Alice" 存取章節 300
      Then 存取成功，顯示章節 300 的內容

  Rule: 邊界情境 - 含子章節時，解鎖依照攤平排序

    Example: 子章節按攤平排序逐一解鎖
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_viewing |
        | 102      | 巢狀章節課 | yes        | publish | yes                   |
      And 課程 102 有以下章節（攤平排序）：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | 1-1        | 102         | 10         |
        | 401       | 1-1-a      | 400         | 10         |
        | 402       | 1-1-b      | 400         | 20         |
        | 403       | 1-2        | 102         | 20         |
      And 用戶 "Alice" 已被加入課程 102，expire_date 0
      And 用戶 "Alice" 在章節 400 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 401 無 finished_at
      When 用戶 "Alice" 存取章節 402
      Then 存取被鎖定，導向章節 401
      # 攤平排序：400 → 401 → 402 → 403，401 未完成所以 402 被鎖定
