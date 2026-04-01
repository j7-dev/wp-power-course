@ignore @command
Feature: 線性觀看存取控制

  啟用線性觀看的課程，學員無法透過點擊或直接輸入 URL 存取鎖定的章節。
  系統在前台模板與後端 API 兩層進行阻擋。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_viewing |
      | 100      | PHP 基礎課 | yes        | publish | yes                   |
    And 課程 100 有以下章節（按 menu_order 排序後的扁平序列）：
      | chapterId | post_title | post_parent | menu_order | depth |
      | 200       | 第一章     | 100         | 10         | 0     |
      | 201       | 1-1 小節   | 200         | 10         | 1     |
      | 202       | 1-2 小節   | 200         | 20         | 1     |
      | 203       | 第二章     | 100         | 20         | 0     |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Alice" 無任何章節完成紀錄

  # ========== 前台頁面存取控制 ==========

  Rule: 前台 - 學員透過 URL 存取鎖定章節時，302 重導向到下一個應完成的章節

    Example: 直接存取鎖定的 1-1 章節時重導向到第一章
      When 用戶 "Alice" 透過 URL 存取章節 201 的頁面
      Then 系統回應 HTTP 302
      And 重導向目標為章節 200 的 URL
      And 重導向 URL 包含 query parameter "pc_locked=1"

    Example: 完成第一章後存取 1-2 章節時重導向到 1-1
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 透過 URL 存取章節 202 的頁面
      Then 系統回應 HTTP 302
      And 重導向目標為章節 201 的 URL
      And 重導向 URL 包含 query parameter "pc_locked=1"

  Rule: 前台 - 重導向後顯示友善提示訊息

    Example: 重導向到目標章節後顯示提示
      Given 用戶 "Alice" 被重導向到章節 200，URL 帶有 pc_locked=1
      When 頁面載入完成
      Then 頁面上顯示提示訊息「請先完成本章節，才能觀看後面的章節」

  Rule: 前台 - 解鎖的章節可正常存取，不受重導向影響

    Example: 存取已解鎖的第一章節
      When 用戶 "Alice" 透過 URL 存取章節 200 的頁面
      Then 頁面正常載入
      And 不發生重導向

    Example: 完成第一章後存取 1-1
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 透過 URL 存取章節 201 的頁面
      Then 頁面正常載入
      And 不發生重導向

  Rule: 前台 - 已完成的章節即使位於鎖定區間，仍可存取

    Example: 前方章節取消完成，但已完成的後方章節仍可存取
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 10:20:00"
      When 用戶 "Alice" 透過 URL 存取章節 202 的頁面
      Then 頁面正常載入
      And 不發生重導向

  # ========== 教室頁面章節列表 UI ==========

  Rule: 前台 UI - 鎖定章節不可點擊，顯示灰色樣式與鎖頭圖示

    Example: 教室頁面顯示鎖定狀態
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節列表顯示如下：
        | chapterId | post_title | 狀態     | 可點擊 |
        | 200       | 第一章     | 可觀看   | true   |
        | 201       | 1-1 小節   | 鎖定     | false  |
        | 202       | 1-2 小節   | 鎖定     | false  |
        | 203       | 第二章     | 鎖定     | false  |

    Example: 鎖定章節的 HTML 樣式
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 鎖定章節的 li 元素應包含 class "pc-chapter-locked"
      And 鎖定章節的 li 元素應包含 class "opacity-50"
      And 鎖定章節的 li 元素應包含 class "cursor-not-allowed"
      And 鎖定章節的 li 元素不應有 data-href 屬性
      And 鎖定章節的圖示應為鎖頭圖示

  Rule: 前台 UI - 完成章節後，下一章節的鎖定狀態即時更新

    Example: 標記章節完成後 AJAX 回應包含下一章節解鎖資訊
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And API 回應中 next_unlocked_chapter_id 應為 201
      And 前端更新章節 201 的顯示為可觀看狀態

  # ========== API 層存取控制 ==========

  Rule: API - toggle-finish-chapters 回應增加 is_locked 相關資訊

    Example: 完成章節後 API 回傳下一個解鎖的章節
      When 用戶 "Alice" 對章節 200 呼叫 toggle-finish-chapters API
      Then 操作成功
      And 回應中 data.next_unlocked_chapter_id 應為 201

    Example: 取消完成章節後 API 回傳鎖定變更的章節列表
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 對章節 200 呼叫 toggle-finish-chapters API（取消完成）
      Then 操作成功
      And 回應中 data.locked_chapter_ids 應包含 201

  Rule: API - GET chapters 端點回傳 is_locked 欄位

    Example: 已登入學員查詢章節列表時包含鎖定狀態
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 每個章節物件應包含 is_locked 欄位（boolean）

    Example: 未登入用戶查詢章節列表時 is_locked 全為 false
      When 未登入訪客查詢課程 100 的章節列表
      Then 操作成功
      And 所有章節的 is_locked 應為 false

  # ========== 未啟用線性觀看時的行為 ==========

  Rule: 未啟用 - 所有存取控制邏輯不生效

    Example: 未啟用線性觀看的課程，學員可自由存取任何章節
      Given 課程 100 的 enable_linear_viewing 為 "no"
      When 用戶 "Alice" 透過 URL 存取章節 203 的頁面
      Then 頁面正常載入
      And 不發生重導向

    Example: 未啟用線性觀看的課程，API 不回傳 is_locked
      Given 課程 100 的 enable_linear_viewing 為 "no"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 所有章節的 is_locked 應為 false
