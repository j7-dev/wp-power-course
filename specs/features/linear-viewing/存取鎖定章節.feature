@ignore @command
Feature: 存取鎖定章節

  啟用線性觀看後，學員嘗試存取鎖定章節時的系統行為：
  - 前台頁面：鎖定章節不可點擊（灰色 + 鎖頭圖示 + cursor:not-allowed）
  - URL 直接存取：HTTP 302 重導向到下一個應完成的章節
  - API 存取：回應中包含 is_locked 狀態

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_viewing |
      | 100      | PHP 基礎課 | yes        | publish | yes                   |
    And 課程 100 有以下章節（扁平化順序）：
      | chapterId | post_title | post_parent | menu_order | 扁平化序號 |
      | 200       | 第一章     | 100         | 1          | 0          |
      | 201       | 1-1        | 200         | 1          | 1          |
      | 202       | 1-2        | 200         | 2          | 2          |
      | 203       | 第二章     | 100         | 2          | 3          |
      | 204       | 2-1        | 203         | 1          | 4          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 前台 URL 直接存取 ==========

  Rule: 學員透過 URL 直接存取鎖定章節時，302 重導向到下一個應完成的章節

    Example: 學員嘗試直接存取鎖定的 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      # 201 解鎖（前一個200已完成），202 鎖定
      When 用戶 "Alice" 透過 URL 存取章節 202 的頁面
      Then 系統回應 HTTP 302 重導向
      And 重導向目標為章節 201 的頁面 URL
      And 重導向 URL 包含 query parameter "pc_locked=1"

    Example: 學員嘗試存取最後一個鎖定章節
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 存取章節 204 的頁面
      Then 系統回應 HTTP 302 重導向
      And 重導向目標為章節 200 的頁面 URL
      And 重導向 URL 包含 query parameter "pc_locked=1"

  Rule: 學員存取已解鎖的章節不受阻擋

    Example: 學員正常存取第一個章節
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 存取章節 200 的頁面
      Then 系統正常載入章節 200 的頁面內容

    Example: 學員存取已完成的章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 透過 URL 存取章節 200 的頁面
      Then 系統正常載入章節 200 的頁面內容

  # ========== 未啟用線性觀看 ==========

  Rule: 未啟用線性觀看的課程，所有章節可自由存取

    Example: 關閉線性觀看時不做重導向
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 存取章節 204 的頁面
      Then 系統正常載入章節 204 的頁面內容

  # ========== 重導向提示訊息 ==========

  Rule: 重導向後頁面顯示提示訊息

    Example: 重導向後顯示鎖定提示
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 存取章節 201 的頁面
      And 系統 302 重導向到章節 200 並帶有 pc_locked=1
      Then 頁面應顯示提示訊息「請先完成前面的章節，才能觀看該章節」

  # ========== 管理員不受限 ==========

  Rule: 管理員（含預覽模式）不受線性觀看限制

    Example: 管理員直接存取鎖定章節不被重導向
      Given 用戶 "Admin" 具有 manage_woocommerce 權限
      When 用戶 "Admin" 透過 URL 存取章節 204 的頁面
      Then 系統正常載入章節 204 的頁面內容
