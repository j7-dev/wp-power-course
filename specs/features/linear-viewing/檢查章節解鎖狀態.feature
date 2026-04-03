@ignore @query
Feature: 檢查章節解鎖狀態

  開啟線性觀看的課程中，系統依據章節攤平順序和完成進度判斷每個章節的解鎖狀態。
  所有章節（含父章節）攤平為線性序列，依 menu_order ASC 排序。
  第一個章節固定解鎖，後續章節需前一章已完成才解鎖。

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
      | 203       | 第二章     | 2          | 100         |
      | 204       | 2-1        | 1          | 203         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 核心規則 ==========

  Rule: 第一個章節（攤平順序）固定為解鎖狀態

    Example: 新學員首次進入，第一章可存取
      Given 用戶 "Alice" 未完成任何章節
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 200（第一章）的狀態應為「已解鎖」
      And 章節 201（1-1）的狀態應為「已鎖定」

  Rule: 完成當前章節後，下一個章節解鎖

    Example: 完成第一章後 1-1 解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 200（第一章）的狀態應為「已完成」
      And 章節 201（1-1）的狀態應為「已解鎖」
      And 章節 202（1-2）的狀態應為「已鎖定」

  Rule: 攤平順序跨越父章節邊界

    Example: 完成 1-2 後解鎖第二章
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 203（第二章）的狀態應為「已解鎖」
      And 章節 204（2-1）的狀態應為「已鎖定」

  # ========== 跳躍式進度（中途開啟） ==========

  Rule: 嚴格線性規則 — 中間有未完成章節時，後續即使已完成也視為鎖定

    Example: 跳過 1-1 已完成 1-2 的情境（管理員中途開啟線性觀看）
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 200（第一章）的狀態應為「已完成」
      And 章節 201（1-1）的狀態應為「已解鎖」
      And 章節 202（1-2）的狀態應為「已鎖定」
      And 章節 203（第二章）的狀態應為「已鎖定」

    Example: 完成 1-1 後，之前跳躍完成的 1-2 自動恢復為已完成狀態
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-02 10:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 202（1-2）的狀態應為「已完成」
      And 章節 203（第二章）的狀態應為「已解鎖」

  # ========== 線性觀看關閉時 ==========

  Rule: 線性觀看關閉時，所有章節均為解鎖

    Example: 課程未開啟線性觀看
      Given 系統中有以下課程：
        | courseId | name     | _is_course | status  | linear_chapter_mode |
        | 300      | 自由課程 | yes        | publish | no                  |
      And 課程 300 有以下章節：
        | chapterId | post_title | post_parent |
        | 400       | A          | 300         |
        | 401       | B          | 300         |
      And 用戶 "Alice" 已被加入課程 300，expire_date 0
      When 查詢用戶 "Alice" 在課程 300 的章節解鎖狀態
      Then 所有章節的狀態應為「已解鎖」

  # ========== 管理員預覽 ==========

  Rule: 管理員不受線性觀看鎖定限制

    Example: 管理員可存取任何章節（含被鎖定的）
      Given 系統中有以下用戶：
        | userId | name  | email          | role          |
        | 1      | Admin | admin@test.com | administrator |
      And 用戶 "Admin" 未被加入課程 100（管理員預覽模式）
      When 查詢用戶 "Admin" 在課程 100 的章節解鎖狀態
      Then 所有章節的狀態應為「已解鎖（管理員）」
