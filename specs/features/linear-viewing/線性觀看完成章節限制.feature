@ignore @command
Feature: 線性觀看完成章節限制

  當課程開啟線性觀看模式後：
  1. 禁止學員取消已完成的章節（不可切換回未完成）
  2. 學員完成章節後，前端 JS 局部解鎖下一章（無需重新載入頁面）
  3. 完成後顯示提示訊息告知下一章已解鎖

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                |
    And 課程 100 有以下章節（扁平 menu_order 排序）：
      | chapterId | post_title | menu_order |
      | 201       | 1-1        | 10         |
      | 202       | 1-2        | 20         |
      | 203       | 1-3        | 30         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 禁止取消完成 ==========

  Rule: 線性觀看模式下，已完成的章節不可取消完成

    Example: 學員嘗試取消完成被拒絕
      Given 用戶 "Alice" 已完成章節 201
      When 用戶 "Alice" 對章節 201 執行 toggle-finish（嘗試取消完成）
      Then 操作失敗
      And 錯誤訊息為「線性觀看模式下無法取消已完成的章節」
      And 章節 201 的 finished_at 應維持不變

    Example: 管理員可以取消完成（繞過限制）
      Given 用戶 "Alice" 已完成章節 201
      When 管理員 "Admin" 代替用戶 "Alice" 取消章節 201 的完成狀態
      Then 操作成功

  Rule: 線性觀看模式下，完成按鈕在已完成章節中隱藏「標示為未完成」功能

    Example: 已完成章節不顯示取消完成按鈕
      Given 用戶 "Alice" 已完成章節 201
      When 用戶 "Alice" 造訪章節 201
      Then 頁面不應顯示「標示為未完成」按鈕
      And 完成狀態 badge 應顯示「已完成」

  # ========== 未開啟線性觀看的課程不受影響 ==========

  Rule: 未開啟線性觀看的課程，取消完成正常運作

    Example: 一般課程可正常取消完成
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_mode |
        | 300      | CSS 進階課 | yes        | publish | no                 |
      And 課程 300 有以下章節：
        | chapterId | post_title | menu_order |
        | 301       | 基礎篇     | 10         |
      And 用戶 "Alice" 已被加入課程 300
      And 用戶 "Alice" 已完成章節 301
      When 用戶 "Alice" 對章節 301 執行 toggle-finish（取消完成）
      Then 操作成功
      And 章節 301 的 finished_at 應為空

  # ========== 完成後 JS 局部解鎖 ==========

  Rule: 完成章節後，toggle-finish API 回傳下一章解鎖資訊

    Example: API 回傳包含下一章解鎖狀態
      Given 用戶 "Alice" 已完成章節 201
      And 用戶 "Alice" 在章節 202 無 finished_at
      When 用戶 "Alice" 對章節 202 執行 toggle-finish（標記完成）
      Then 操作成功
      And API 回應 data 應包含：
        | 欄位                        | 值    |
        | is_this_chapter_finished    | true  |
        | next_chapter_id             | 203   |
        | next_chapter_unlocked       | true  |
      And 前端 JS 應局部更新側邊欄：
        | 動作                                       |
        | 章節 202 圖示從「影片圖示」變為「完成圖示」    |
        | 章節 203 圖示從「鎖頭圖示」變為「影片圖示」    |
        | 章節 203 的 li 元素移除鎖定樣式               |

  Rule: 完成最後一個章節時，不回傳下一章資訊

    Example: 完成最後章節
      Given 用戶 "Alice" 已完成章節 201, 202
      When 用戶 "Alice" 對章節 203 執行 toggle-finish（標記完成）
      Then 操作成功
      And API 回應 data 應包含：
        | 欄位                        | 值    |
        | is_this_chapter_finished    | true  |
        | next_chapter_id             | null  |
        | next_chapter_unlocked       | false |

  # ========== 完成後提示訊息 ==========

  Rule: 完成章節後，彈出提示告知下一章已解鎖（不自動跳轉）

    Example: 完成後顯示解鎖提示
      Given 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 對章節 201 執行 toggle-finish（標記完成）
      Then 彈出對話框應顯示：
        | 元素 | 內容                           |
        | 標題 | 「成功」                        |
        | 訊息 | 「單元 1-1 已標示為完成！下一章節已解鎖」 |
      And 用戶可手動關閉對話框
      And 不自動跳轉至下一章節

  # ========== 鎖定章節不可被完成 ==========

  Rule: 學員不可對鎖定的章節執行 toggle-finish

    Example: 嘗試完成鎖定章節被拒絕
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 對章節 202（已鎖定）執行 toggle-finish
      Then 操作失敗
      And 錯誤訊息為「此章節尚未解鎖，請先完成前面的章節」
