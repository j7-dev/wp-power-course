@ignore @ui
Feature: 教室頁面章節鎖定 UI

  啟用線性觀看後，教室頁面側邊欄的章節列表需顯示鎖定狀態。
  鎖定章節：灰色 + 鎖頭圖示 + cursor:not-allowed + 不可點擊。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_viewing |
      | 100      | PHP 基礎課 | yes        | publish | yes                   |
    And 課程 100 有以下章節（扁平化順序）：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 1          |
      | 201       | 1-1        | 200         | 1          |
      | 202       | 1-2        | 200         | 2          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Alice" 無任何章節完成紀錄

  # ========== 鎖定章節視覺狀態 ==========

  Rule: 鎖定章節顯示鎖頭圖示並且不可點擊

    Example: 側邊欄中鎖定章節的視覺表現
      When 用戶 "Alice" 進入課程 100 教室頁面的章節 200
      Then 側邊欄章節列表中：
        | chapterId | 圖示     | 可點擊 | 樣式               |
        | 200       | 影片圖示 | true   | 正常樣式           |
        | 201       | 鎖頭圖示 | false  | 灰色 opacity-50    |
        | 202       | 鎖頭圖示 | false  | 灰色 opacity-50    |

  Rule: 鎖定章節顯示提示文字

    Example: 鎖定章節的 tooltip 顯示提示
      When 用戶 "Alice" 進入課程 100 教室頁面的章節 200
      Then 側邊欄中章節 201 的 tooltip 應顯示「請先完成前面的章節」
      And 側邊欄中章節 202 的 tooltip 應顯示「請先完成前面的章節」

  # ========== 完成章節後即時更新 ==========

  Rule: 完成當前章節後，下一個章節從鎖定變為解鎖

    Example: 完成第一章後 1-1 即時解鎖
      Given 用戶 "Alice" 在課程 100 教室頁面觀看章節 200
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then 側邊欄中章節 201 的圖示從鎖頭變為影片圖示
      And 側邊欄中章節 201 的樣式恢復為正常（可點擊）
      And 側邊欄中章節 202 仍為鎖頭圖示

  # ========== 前往下一章節按鈕 ==========

  Rule: Header 中「前往下一章節」按鈕在鎖定時停用

    Example: 下一章節被鎖定時按鈕停用
      Given 用戶 "Alice" 在課程 100 教室頁面觀看章節 200
      And 章節 200 尚未完成
      Then Header 中「前往下一章節」按鈕應停用（disabled）
      And 按鈕顯示 tooltip「請先完成當前章節」

    Example: 下一章節已解鎖時按鈕正常
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在課程 100 教室頁面觀看章節 201
      Then Header 中「前往下一章節」按鈕應可點擊

  # ========== 完成完成狀態按鈕 ==========

  Rule: 鎖定章節的完成按鈕不顯示（因為無法進入鎖定章節的頁面）

    # 此規則由「存取鎖定章節」Feature 的重導向機制保證：
    # 學員無法進入鎖定章節的頁面，因此不會看到該章節的完成按鈕。
    # 此 Rule 為防禦性驗證。

    Example: 無法透過 URL 進入鎖定章節看到完成按鈕
      When 用戶 "Alice" 透過 URL 直接存取章節 202 的頁面
      Then 系統 302 重導向到章節 200
      And 用戶不會看到章節 202 的完成按鈕
