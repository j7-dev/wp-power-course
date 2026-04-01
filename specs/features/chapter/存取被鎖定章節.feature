@ignore @command
Feature: 存取被鎖定章節

  當課程開啟線性觀看模式（enable_linear_mode = yes），學員嘗試存取被鎖定的章節時，
  Template 層（直接 URL）和 REST API 層都必須阻擋，不洩漏影片內容或章節正文。

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
      | 201       | 第二章     | 100         | 20         |
      | 202       | 第三章     | 100         | 30         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== Template 層阻擋 ==========

  Rule: 學員透過 URL 直接存取被鎖定章節時，Template 層顯示鎖定提示頁面

    Example: 學員 URL 存取被鎖定章節 — 顯示提示頁面
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 以瀏覽器存取章節 201 的 permalink
      Then 頁面不顯示章節影片內容
      And 頁面不顯示章節正文
      And 頁面顯示鎖定提示訊息「請先完成前面的章節才能觀看此章節」

    Example: 學員 URL 存取已解鎖章節 — 正常顯示
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 以瀏覽器存取章節 201 的 permalink
      Then 頁面正常顯示章節影片內容
      And 頁面正常顯示章節正文

  # ========== REST API 層阻擋 ==========

  Rule: REST API 拒絕回傳被鎖定章節的影片與內容

    Example: 學員 API 請求被鎖定章節的內容 — 回傳 403
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 請求 GET /wp-json/power-course/chapters/201
      Then 回應狀態碼為 403
      And 回應 message 包含「請先完成前面的章節」
      And 回應不包含 chapter_video 資料
      And 回應不包含 description 資料

  # ========== 管理員 / 講師不受限 ==========

  Rule: 管理員透過 URL 存取被鎖定章節時不受限

    Example: 管理員可 URL 存取任何章節
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Admin" 以瀏覽器存取章節 202 的 permalink
      Then 頁面正常顯示章節影片內容

  Rule: 講師透過 URL 存取被鎖定章節時不受限

    Example: 講師可 URL 存取任何章節
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Teacher" 以瀏覽器存取章節 202 的 permalink
      Then 頁面正常顯示章節影片內容

  # ========== 功能關閉時 ==========

  Rule: enable_linear_mode 為 no 時不阻擋任何存取

    Example: 線性觀看關閉 — 學員可自由存取任何章節
      Given 課程 100 的 enable_linear_mode 為 "no"
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 以瀏覽器存取章節 202 的 permalink
      Then 頁面正常顯示章節影片內容

  # ========== 第一個章節永遠可存取 ==========

  Rule: 線性觀看序列的第一個章節永遠可存取

    Example: 第一個章節不受鎖定影響
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 以瀏覽器存取章節 200 的 permalink
      Then 頁面正常顯示章節影片內容
