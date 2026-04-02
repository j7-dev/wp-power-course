@ignore @query
Feature: 線性觀看存取控制

  當課程開啟線性觀看（enable_sequential = yes）時，
  學員必須依照章節排序順序（menu_order）完成前一個章節，才能進入下一個章節。
  父章節（depth 0）也被視為一般章節，需要完成才能繼續。
  第一個章節永遠開放。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_sequential |
      | 100      | PHP 基礎課 | yes        | publish | yes               |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 單層章節 ==========

  Rule: 單層章節 - 第一個章節永遠可存取

    Example: 學員可存取第一個章節（無完成紀錄）
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
        | 202       | 1-3        | 100         | 3          |
      When 用戶 "Alice" 存取章節 200
      Then 存取成功

  Rule: 單層章節 - 未完成前一章節時不可存取後續章節

    Example: 1-1 未完成時不可存取 1-2
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
        | 202       | 1-3        | 100         | 3          |
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 存取章節 201
      Then 存取被拒絕
      And 導向至章節 200

    Example: 1-1 已完成但 1-2 未完成時不可存取 1-3
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
        | 202       | 1-3        | 100         | 3          |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 存取章節 202
      Then 存取被拒絕
      And 導向至章節 201

  Rule: 單層章節 - 完成前一章節後可存取下一個章節

    Example: 完成 1-1 後可存取 1-2
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 存取章節 201
      Then 存取成功

  # ========== 含父章節的階層結構 ==========

  Rule: 階層結構 - 父章節也在順序中，必須完成才能繼續

    Example: 父章節「第一章」未完成時不可存取子章節 1-1
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | 第一章     | 100         | 1          |
        | 301       | 1-1        | 300         | 1          |
        | 302       | 1-2        | 300         | 2          |
        | 310       | 第二章     | 100         | 2          |
        | 311       | 2-1        | 310         | 1          |
      And 用戶 "Alice" 在章節 300 無 finished_at
      When 用戶 "Alice" 存取章節 301
      Then 存取被拒絕
      And 導向至章節 300

    Example: 完成「第一章」→ 1-1 → 1-2 後可存取「第二章」
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | 第一章     | 100         | 1          |
        | 301       | 1-1        | 300         | 1          |
        | 302       | 1-2        | 300         | 2          |
        | 310       | 第二章     | 100         | 2          |
        | 311       | 2-1        | 310         | 1          |
      And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 302 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 存取章節 310
      Then 存取成功

    Example: 完成第一章的子章節但未完成「第二章」父章節時不可存取 2-1
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | 第一章     | 100         | 1          |
        | 301       | 1-1        | 300         | 1          |
        | 310       | 第二章     | 100         | 2          |
        | 311       | 2-1        | 310         | 1          |
      And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 310 無 finished_at
      When 用戶 "Alice" 存取章節 311
      Then 存取被拒絕
      And 導向至章節 310

  # ========== 線性觀看關閉時 ==========

  Rule: 線性觀看關閉 - 所有章節自由存取

    Example: enable_sequential 為 no 時可自由存取任何章節
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_sequential |
        | 101      | 自由課程   | yes        | publish | no                |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | A-1        | 101         | 1          |
        | 401       | A-2        | 101         | 2          |
        | 402       | A-3        | 101         | 3          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 在章節 400 無 finished_at
      When 用戶 "Alice" 存取章節 402
      Then 存取成功

  # ========== 已完成章節可回看 ==========

  Rule: 已完成的章節可隨時回看

    Example: 已完成 1-1 後仍可回看 1-1
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 存取章節 200
      Then 存取成功

  # ========== 後端 API 防繞過 ==========

  Rule: 後端驗證 - 透過 URL 直接存取被鎖定章節時導向正確章節

    Example: 直接輸入網址存取鎖定章節時導向到目前進度章節
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
        | 202       | 1-3        | 100         | 3          |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 直接透過 URL 存取章節 202
      Then 導向至章節 201
      And 顯示提示訊息「請先完成前面的章節，才能觀看此章節喔！」

  # ========== 存取權限檢查優先於線性觀看 ==========

  Rule: 存取權限 - 無課程存取權時優先回傳存取權錯誤

    Example: 無課程存取權時不檢查線性觀看
      Given 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
      And 用戶 "Bob" 未被加入課程 100
      When 用戶 "Bob" 存取章節 200
      Then 操作失敗，錯誤為「無此課程存取權」
