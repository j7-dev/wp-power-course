@ignore @ui
Feature: 線性觀看模式 — 側邊章節列表鎖定 UI

  當課程啟用線性觀看模式時，側邊章節列表中被鎖定的章節需顯示明確的視覺提示。

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
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 鎖定狀態視覺提示 ==========

  Rule: 被鎖定的章節應顯示鎖頭圖示取代原本的影片/完成圖示

    Example: 鎖定章節顯示鎖頭圖示
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 進入教室頁面瀏覽章節 300
      Then 側邊列表中章節 300（第一章）應顯示一般圖示（可觀看）
      And 側邊列表中章節 200（1-1）應顯示鎖頭圖示
      And 側邊列表中章節 201（1-2）應顯示鎖頭圖示

  Rule: 被鎖定的章節連結應禁用（不可點擊）

    Example: 鎖定章節的 <li> 不應有 data-href 或 href 應被移除
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 進入教室頁面
      Then 側邊列表中章節 200（1-1）的 <li> 應加上 CSS class "pc-locked"
      And 側邊列表中章節 200（1-1）應無法透過點擊跳轉頁面

  Rule: 被鎖定的章節 hover 時應顯示提示文字

    Example: hover 鎖定章節顯示提示
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 將滑鼠移到側邊列表中章節 200（1-1）上
      Then 應顯示 tooltip 提示「請先完成前面的章節」

  # ========== 解鎖狀態恢復 ==========

  Rule: 解鎖後章節應恢復正常的圖示與點擊行為

    Example: 完成第一章後，1-1 恢復正常狀態
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 進入教室頁面
      Then 側邊列表中章節 200（1-1）應顯示一般圖示（可觀看）
      And 側邊列表中章節 200（1-1）應可透過點擊跳轉頁面
      And 側邊列表中章節 200（1-1）不應有 CSS class "pc-locked"
