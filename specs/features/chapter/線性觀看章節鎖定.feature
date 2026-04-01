@ignore @query
Feature: 線性觀看章節鎖定

  課程開啟 enable_linear_mode 後，學員必須按照章節排序（menu_order ASC、展開後 DFS）
  依序完成前面的章節，才能觀看後續章節。第一個章節永遠可觀看。
  管理員（manage_woocommerce）及該課程講師（teacher_ids）豁免此限制。

  Background:
    Given 系統中有以下用戶：
      | userId | name    | email            | role          |
      | 1      | Admin   | admin@test.com   | administrator |
      | 2      | Alice   | alice@test.com   | subscriber    |
      | 3      | Teacher | teacher@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                |
    And 課程 100 的 teacher_ids 為 [3]
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 10         |
      | 201       | 1-1        | 200         | 10         |
      | 202       | 1-2        | 200         | 20         |
      | 203       | 第二章     | 100         | 20         |
      | 204       | 2-1        | 203         | 10         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 線性觀看序列 ==========

  Rule: 線性觀看序列依照展開後的完整排序（DFS: 第一章 → 1-1 → 1-2 → 第二章 → 2-1）

    Example: 第一個章節（第一章）永遠可觀看
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 200（第一章）的鎖定狀態為 unlocked

    Example: 未完成第一個章節時，其餘章節全部鎖定
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 201（1-1）的鎖定狀態為 locked
      And 章節 202（1-2）的鎖定狀態為 locked
      And 章節 203（第二章）的鎖定狀態為 locked
      And 章節 204（2-1）的鎖定狀態為 locked

    Example: 完成第一章後，1-1 解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 201（1-1）的鎖定狀態為 unlocked
      And 章節 202（1-2）的鎖定狀態為 locked

    Example: 依序完成到 1-2 後，第二章解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 203（第二章）的鎖定狀態為 unlocked
      And 章節 204（2-1）的鎖定狀態為 locked

  # ========== 取消完成後重新鎖定 ==========

  Rule: 取消前面章節的完成狀態後，後續章節重新鎖定

    Example: 取消 1-1 完成後，1-2 及後續章節重新鎖定
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作成功
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 201（1-1）的鎖定狀態為 unlocked
      And 章節 202（1-2）的鎖定狀態為 locked
      And 章節 203（第二章）的鎖定狀態為 locked

    Example: 取消完成不清除後續章節的 finished_at 紀錄
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應不為空
      And 章節 202 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  # ========== 管理員 / 講師豁免 ==========

  Rule: 持有 manage_woocommerce 權限的管理員不受線性觀看限制

    Example: 管理員可自由存取所有章節
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Admin" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的鎖定狀態均為 unlocked

  Rule: 課程講師（teacher_ids）不受線性觀看限制

    Example: 講師可自由存取所有章節
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Teacher" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的鎖定狀態均為 unlocked

  # ========== 功能關閉時 ==========

  Rule: enable_linear_mode 為 no 時，所有章節均不鎖定

    Example: 未開啟線性觀看時，所有章節自由存取
      Given 課程 100 的 enable_linear_mode 為 "no"
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的鎖定狀態均為 unlocked
