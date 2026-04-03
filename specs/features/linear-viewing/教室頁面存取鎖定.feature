@ignore @query
Feature: 教室頁面存取鎖定（後端）

  開啟線性觀看的課程中，後端在教室頁面模板渲染前檢查章節解鎖狀態。
  學員嘗試直接存取被鎖定章節的 URL 時，系統將導向至最新可存取章節。
  此為後端強制鎖定，防止學員透過 URL 繞過前端限制。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | linear_chapter_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                 |
    And 課程 100 有以下章節（攤平順序）：
      | chapterId | post_title | menu_order | post_parent | post_name |
      | 200       | 第一章     | 1          | 100         | ch-1      |
      | 201       | 1-1        | 1          | 200         | ch-1-1    |
      | 202       | 1-2        | 2          | 200         | ch-1-2    |
      | 203       | 第二章     | 2          | 100         | ch-2      |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 後端鎖定 ==========

  Rule: 存取被鎖定章節 URL 時導向至最新可存取章節

    Example: 學員直接輸入 URL 存取被鎖定的 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 以瀏覽器直接存取章節 202（ch-1-2）的 URL
      Then 系統導向至章節 201（1-1）的 URL
      And HTTP 回應狀態碼為 302

    Example: 新學員直接輸入 URL 存取第二章
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 以瀏覽器直接存取章節 203（ch-2）的 URL
      Then 系統導向至章節 200（第一章）的 URL
      And HTTP 回應狀態碼為 302

  Rule: 已解鎖的章節可正常存取

    Example: 學員存取第一個章節（固定解鎖）
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 以瀏覽器直接存取章節 200（ch-1）的 URL
      Then 頁面正常渲染教室內容

    Example: 學員存取已完成的章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 以瀏覽器直接存取章節 200（ch-1）的 URL
      Then 頁面正常渲染教室內容

  # ========== 管理員例外 ==========

  Rule: 管理員不受後端鎖定限制

    Example: 管理員可直接存取任何章節
      Given 系統中有以下用戶：
        | userId | name  | email          | role          |
        | 1      | Admin | admin@test.com | administrator |
      When 用戶 "Admin" 以瀏覽器直接存取章節 203（ch-2）的 URL
      Then 頁面正常渲染教室內容（管理員預覽模式）

  # ========== 線性觀看關閉 ==========

  Rule: 線性觀看關閉時不執行存取鎖定

    Example: 關閉線性觀看後可自由存取任何章節
      Given 課程 100 的 linear_chapter_mode 為 "no"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 以瀏覽器直接存取章節 203（ch-2）的 URL
      Then 頁面正常渲染教室內容
