@ignore @query
Feature: 線性觀看模式 — 章節鎖定狀態查詢

  當課程啟用線性觀看模式（enable_linear_mode = yes）時，
  系統根據 get_flatten_post_ids() 扁平順序，計算每個章節的解鎖狀態。
  解鎖規則：第一個章節永遠解鎖，其餘章節需前一個章節已完成（有 finished_at）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                |
    And 課程 100 有以下章節（扁平順序）：
      | chapterId | post_title   | post_parent | menu_order |
      | 300       | 第一章       | 100         | 1          |
      | 200       | 1-1 環境安裝 | 300         | 1          |
      | 201       | 1-2 IDE 設定 | 300         | 2          |
      | 301       | 第二章       | 100         | 2          |
      | 202       | 2-1 變數     | 301         | 1          |
      | 203       | 2-2 型別     | 301         | 2          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 基本解鎖規則 ==========

  Rule: 扁平順序中第一個章節（含頂層）永遠解鎖

    Example: 第一個章節預設解鎖
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 300（第一章）應為「已解鎖」

  Rule: 前一章節未完成時，後續章節鎖定

    Example: 尚未完成任何章節時，只有第一章解鎖
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 300（第一章）應為「已解鎖」
      And 章節 200（1-1）應為「鎖定」
      And 章節 201（1-2）應為「鎖定」
      And 章節 301（第二章）應為「鎖定」
      And 章節 202（2-1）應為「鎖定」
      And 章節 203（2-2）應為「鎖定」

  Rule: 完成前一章節後，下一章節解鎖

    Example: 完成第一章後，1-1 解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 300（第一章）應為「已解鎖」
      And 章節 200（1-1）應為「已解鎖」
      And 章節 201（1-2）應為「鎖定」

    Example: 完成 1-1 後，1-2 解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 200（1-1）應為「已解鎖」
      And 章節 201（1-2）應為「已解鎖」
      And 章節 301（第二章）應為「鎖定」

  Rule: 頂層章節也參與鎖定順序，必須完成 1-2 才能存取第二章

    Example: 完成到 1-2 後，第二章解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 201（1-2）應為「已解鎖」
      And 章節 301（第二章）應為「已解鎖」
      And 章節 202（2-1）應為「鎖定」

  # ========== 已完成章節可重複存取 ==========

  Rule: 已完成的章節不論解鎖順序是否連續，均可重新進入觀看

    Example: 已完成的章節永遠可存取
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 300（第一章）應為「已解鎖」
      And 章節 200（1-1）應為「已解鎖」

  # ========== 取消完成後重新鎖定 ==========

  Rule: 取消某章節完成狀態後，後續未完成章節重新鎖定

    Example: 取消 1-1 完成後，1-2 及後續重新鎖定
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      And 章節 300（第一章）應為「已解鎖」
      And 章節 200（1-1）應為「已解鎖」
      And 章節 201（1-2）應為「鎖定」
      And 章節 301（第二章）應為「鎖定」

  # ========== 管理員不受限 ==========

  Rule: 擁有 manage_woocommerce 權限的用戶不受線性觀看限制

    Example: 管理員可存取所有章節
      Given 系統中有以下用戶：
        | userId | name  | email          | role          |
        | 1      | Admin | admin@test.com | administrator |
      When 用戶 "Admin" 查詢課程 100 的章節解鎖狀態
      Then 所有章節應為「已解鎖」

  # ========== 未啟用線性觀看 ==========

  Rule: 課程未啟用線性觀看時，所有章節可自由存取

    Example: 未啟用線性觀看的課程不受限制
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_mode |
        | 101      | JS 入門課  | yes        | publish | no                 |
      And 課程 101 有以下章節（扁平順序）：
        | chapterId | post_title   | post_parent | menu_order |
        | 400       | 第一章       | 101         | 1          |
        | 401       | 第二章       | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      When 用戶 "Alice" 查詢課程 101 的章節解鎖狀態
      Then 所有章節應為「已解鎖」

  # ========== 事後開啟線性觀看 ==========

  Rule: 事後開啟線性觀看時，依照已完成記錄計算解鎖狀態

    Example: 已有不連續進度時，依最長連續完成序列解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-05-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-05-01 11:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-05-01 13:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節解鎖狀態
      Then 章節 300（第一章）應為「已解鎖」
      And 章節 200（1-1）應為「已解鎖」
      And 章節 201（1-2）應為「已解鎖」
      And 章節 301（第二章）應為「鎖定」
      And 章節 202（2-1）應為「鎖定」
