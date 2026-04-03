@ignore @query @command
Feature: 線性觀看章節鎖定

  當課程開啟線性觀看（enable_sequential_learning = 'yes'）時，
  學員必須按照 get_flatten_post_ids 展平順序逐步完成章節。
  第一個章節預設解鎖，後續章節必須前一個完成才解鎖。
  鎖定判斷邏輯：章節 N 解鎖條件 = 章節 N-1 的 finished_at 不為空。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  | enable_sequential_learning |
      | 100      | Python 入門課 | yes        | publish | yes                        |
    And 課程 100 有以下章節（展平順序）：
      | chapterId | post_title   | post_parent | menu_order |
      | 300       | 第一單元     | 100         | 1          |
      | 301       | 1-1 簡介     | 300         | 1          |
      | 302       | 1-2 安裝     | 300         | 2          |
      | 303       | 1-3 第一支   | 300         | 3          |
      | 310       | 第二單元     | 100         | 2          |
      | 311       | 2-1 變數     | 310         | 1          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 鎖定狀態計算 ==========

  Rule: 展平序列中第一個章節永遠解鎖

    Example: 新學員只有第一章解鎖
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 300 應為「解鎖」狀態
      And 章節 301 應為「鎖定」狀態
      And 章節 302 應為「鎖定」狀態
      And 章節 303 應為「鎖定」狀態
      And 章節 310 應為「鎖定」狀態
      And 章節 311 應為「鎖定」狀態

  Rule: 完成前一章後，下一章解鎖

    Example: 完成第一單元後 1-1 解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 300 應為「已完成」狀態
      And 章節 301 應為「解鎖」狀態
      And 章節 302 應為「鎖定」狀態

    Example: 完成連續章節後正確計算解鎖範圍
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 10:30:00"
      And 用戶 "Alice" 在章節 302 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 300 應為「已完成」狀態
      And 章節 301 應為「已完成」狀態
      And 章節 302 應為「已完成」狀態
      And 章節 303 應為「解鎖」狀態
      And 章節 310 應為「鎖定」狀態

  Rule: 鎖定跨越章節層級（展平順序，不區分父子）

    Example: 完成第一單元所有子章節後，第二單元解鎖
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 10:30:00"
      And 用戶 "Alice" 在章節 302 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 303 的 finished_at 為 "2025-06-01 11:30:00"
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 310 應為「解鎖」狀態
      And 章節 311 應為「鎖定」狀態

  # ========== 伺服器端存取控制（PHP） ==========

  Rule: 鎖定的章節不可被存取，伺服器端重導到第一個未完成章節

    Example: 學員透過 URL 直接存取鎖定章節時被重導
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 無 finished_at
      When 用戶 "Alice" 直接存取章節 311 的 URL
      Then 系統重導用戶到章節 301（第一個未完成章節）

    Example: 學員透過 URL 存取已解鎖章節正常顯示
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 直接存取章節 301 的 URL
      Then 頁面正常顯示章節 301 的內容

  Rule: 第一個章節永遠可直接存取

    Example: 新學員存取第一章正常顯示
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 直接存取章節 300 的 URL
      Then 頁面正常顯示章節 300 的內容

  # ========== 鎖定章節的視覺呈現 ==========

  Rule: 鎖定章節在側邊欄顯示鎖頭圖示

    Example: 鎖定章節顯示鎖頭圖示而非影片/完成圖示
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 301 的圖示應為鎖頭圖示
      And 章節 302 的圖示應為鎖頭圖示

  Rule: 點擊鎖定章節時顯示提示訊息，不導航

    Example: 學員點擊鎖定章節看到友善提示
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 在教室頁面點擊章節 301
      Then 顯示提示訊息「請先完成前面的章節」
      And 頁面不跳轉，保持在目前章節

  Rule: 下一章節鎖定時，「前往下一章節」按鈕為灰色禁用狀態

    Example: 未完成當前章節時下一章按鈕禁用
      Given 用戶 "Alice" 在章節 300 無 finished_at
      When 用戶 "Alice" 進入章節 300 的頁面
      Then 「前往下一章節」按鈕應為禁用狀態（灰色）
      And hover 按鈕時顯示提示「請先完成本章節」

  # ========== 完成章節限制 ==========

  Rule: 線性觀看開啟時，不可取消已完成的章節

    Example: 已完成章節無法切換為未完成
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 300 的完成狀態
      Then 操作失敗，錯誤為「線性觀看模式下無法取消已完成章節」

    Example: 完成按鈕在已完成章節顯示為不可操作狀態
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 進入章節 300 的頁面
      Then 完成按鈕應顯示為「已完成」不可點擊狀態

  Rule: 線性觀看開啟時，只能完成已解鎖的章節

    Example: 嘗試完成鎖定章節失敗
      Given 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 切換章節 301 的完成狀態
      Then 操作失敗，錯誤為「必須先完成前面的章節」

  # ========== 即時 UI 更新（JS） ==========

  Rule: 完成章節後，下一章節即時解鎖（不重整頁面）

    Example: 完成章節後側邊欄即時更新
      Given 用戶 "Alice" 在章節 300 無 finished_at
      When 用戶 "Alice" 在章節 300 的頁面點擊「標示為已完成」
      Then 操作成功
      And 章節 300 的圖示即時更新為已完成圖示
      And 章節 301 的鎖頭圖示即時消失，變為可觀看圖示
      And 「前往下一章節」按鈕即時從禁用變為可點擊

  # ========== 線性觀看關閉時 ==========

  Rule: 線性觀看關閉時，所有章節自由瀏覽

    Example: 關閉線性觀看後所有章節無鎖定
      Given 課程 100 的 enable_sequential_learning 為 "no"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 所有章節應為「解鎖」狀態
      And 章節列表無鎖頭圖示

    Example: 關閉線性觀看後可正常取消完成
      Given 課程 100 的 enable_sequential_learning 為 "no"
      And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 300 的完成狀態
      Then 操作成功
      And 章節 300 對用戶 "Alice" 的 chaptermeta finished_at 應為空

  # ========== 邊界情境 ==========

  Rule: 站長中途開啟線性觀看時，依據已完成章節正確計算解鎖範圍

    Example: 已有部分進度的學員看到正確的解鎖範圍
      Given 課程 100 的 enable_sequential_learning 為 "yes"
      And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 的 finished_at 為 "2025-06-01 10:30:00"
      And 用戶 "Alice" 在章節 303 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 章節 300 應為「已完成」狀態
      And 章節 301 應為「已完成」狀態
      And 章節 302 應為「解鎖」狀態
      And 章節 303 應為「鎖定」狀態
      Note: 章節 303 雖有 finished_at，但因 302 未完成，303 仍為鎖定

  Rule: 站長中途關閉線性觀看時，所有章節立即恢復自由瀏覽

    Example: 關閉後即使有未完成章節也全部解鎖
      Given 課程 100 的 enable_sequential_learning 為 "no"
      And 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 301 無 finished_at
      When 用戶 "Alice" 進入課程 100 的教室頁面
      Then 所有章節應為「解鎖」狀態

  Rule: 課程只有一個章節時，該章節永遠解鎖

    Example: 單章節課程不受線性觀看影響
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_sequential_learning |
        | 200      | 單元課程    | yes        | publish | yes                        |
      And 課程 200 有以下章節（展平順序）：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | 唯一章節   | 200         | 1          |
      And 用戶 "Alice" 已被加入課程 200，expire_date 0
      When 用戶 "Alice" 進入課程 200 的教室頁面
      Then 章節 400 應為「解鎖」狀態
