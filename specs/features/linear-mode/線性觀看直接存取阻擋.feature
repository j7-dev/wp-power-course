@ignore @command
Feature: 線性觀看模式 — 直接 URL 存取阻擋

  當課程啟用線性觀看模式時，學員透過瀏覽器網址列直接存取被鎖定章節的 URL，
  系統應自動重導到目前應觀看的章節（最後連續完成序列的下一個），並顯示提示訊息。

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
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 重導行為 ==========

  Rule: 存取被鎖定章節時，重導到目前應觀看的章節

    Example: 完全無進度時，存取 1-2 重導到第一章
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 直接存取章節 201（1-2 IDE 設定）的 URL
      Then 頁面應重導到章節 300（第一章）的 URL
      And 頁面應顯示提示訊息「此章節尚未解鎖，已為您導向目前的學習進度」

    Example: 完成到 1-1 後，存取 2-1 重導到 1-2
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Alice" 直接存取章節 202（2-1 變數）的 URL
      Then 頁面應重導到章節 201（1-2 IDE 設定）的 URL
      And 頁面應顯示提示訊息「此章節尚未解鎖，已為您導向目前的學習進度」

  # ========== 已解鎖章節不阻擋 ==========

  Rule: 存取已解鎖章節時正常顯示

    Example: 存取已解鎖的第一章正常顯示
      When 用戶 "Alice" 直接存取章節 300（第一章）的 URL
      Then 頁面應正常顯示章節 300 的內容

    Example: 存取已完成的章節正常顯示
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 直接存取章節 300（第一章）的 URL
      Then 頁面應正常顯示章節 300 的內容

  # ========== 管理員不受限 ==========

  Rule: 管理員直接存取任何章節不被阻擋

    Example: 管理員存取被鎖定章節正常顯示
      Given 系統中有以下用戶：
        | userId | name  | email          | role          |
        | 1      | Admin | admin@test.com | administrator |
      When 用戶 "Admin" 直接存取章節 202（2-1 變數）的 URL
      Then 頁面應正常顯示章節 202 的內容

  # ========== 未啟用時不阻擋 ==========

  Rule: 課程未啟用線性觀看時，直接存取任何章節不被阻擋

    Example: 未啟用線性觀看的課程直接存取正常顯示
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_mode |
        | 101      | JS 入門課  | yes        | publish | no                 |
      And 課程 101 有以下章節（扁平順序）：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | 第一章     | 101         | 1          |
        | 401       | 第二章     | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      When 用戶 "Alice" 直接存取章節 401（第二章）的 URL
      Then 頁面應正常顯示章節 401 的內容
