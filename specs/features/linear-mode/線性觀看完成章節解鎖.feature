@ignore @command
Feature: 線性觀看模式 — 完成章節後即時解鎖

  當課程啟用線性觀看模式時，學員點擊「完成章節」後，
  toggle-finish-chapters API 應額外回傳更新後的解鎖章節 ID 列表（unlocked_chapter_ids），
  前端據此即時更新 DOM，解鎖下一個可觀看的章節。

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

  # ========== 完成章節後回傳解鎖清單 ==========

  Rule: API 回傳 unlocked_chapter_ids — 完成章節後解鎖下一章

    Example: 完成第一章後，API 回傳包含 1-1 的解鎖清單
      Given 用戶 "Alice" 無任何章節完成記錄
      When 用戶 "Alice" 切換章節 300 的完成狀態
      Then 操作成功
      And API 回應中 data.unlocked_chapter_ids 應包含 [300, 200]
      And API 回應中 data.unlocked_chapter_ids 應不包含 201

    Example: 完成 1-1 後，API 回傳包含 1-2 的解鎖清單
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And API 回應中 data.unlocked_chapter_ids 應包含 [300, 200, 201]
      And API 回應中 data.unlocked_chapter_ids 應不包含 301

  # ========== 取消完成後回傳縮減的解鎖清單 ==========

  Rule: API 回傳 unlocked_chapter_ids — 取消完成後鎖定後續章節

    Example: 取消 1-1 完成後，解鎖清單縮減
      Given 用戶 "Alice" 在章節 300 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And API 回應中 data.unlocked_chapter_ids 應包含 [300, 200]
      And API 回應中 data.unlocked_chapter_ids 應不包含 201
      And API 回應中 data.unlocked_chapter_ids 應不包含 301

  # ========== 非線性模式不回傳解鎖清單 ==========

  Rule: 課程未啟用線性觀看時，API 不回傳 unlocked_chapter_ids

    Example: 非線性模式的 API 回應無 unlocked_chapter_ids
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_mode |
        | 101      | JS 入門課  | yes        | publish | no                 |
      And 課程 101 有以下章節（扁平順序）：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | 第一章     | 101         | 1          |
        | 401       | 第二章     | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      When 用戶 "Alice" 切換章節 400 的完成狀態
      Then 操作成功
      And API 回應中 data 應不包含 unlocked_chapter_ids 欄位

  # ========== 前端即時更新行為 ==========

  Rule: 前端根據 unlocked_chapter_ids 即時更新側邊章節列表 DOM

    Example: 完成章節後，下一章節鎖頭圖示消失並變為可點擊
      Given 用戶 "Alice" 無任何章節完成記錄
      And 用戶 "Alice" 正在瀏覽章節 300（第一章）
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then 側邊列表中章節 200（1-1）的鎖頭圖示應消失
      And 側邊列表中章節 200（1-1）應變為可點擊狀態
      And 側邊列表中章節 201（1-2）應仍顯示鎖頭圖示

  # ========== 「前往下一章節」按鈕行為 ==========

  Rule: 線性模式下，當前章節未完成時「前往下一章節」按鈕應 disabled

    Example: 未完成當前章節時，按鈕禁用
      Given 用戶 "Alice" 正在瀏覽章節 300（第一章），且未完成
      Then 「前往下一章節」按鈕應為 disabled 狀態
      And 按鈕 hover 時應顯示提示「請先完成本章節」

    Example: 完成當前章節後，按鈕啟用
      Given 用戶 "Alice" 正在瀏覽章節 300（第一章）
      When 用戶 "Alice" 點擊「標示為已完成」按鈕
      Then 「前往下一章節」按鈕應變為可點擊狀態
