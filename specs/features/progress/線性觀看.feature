@ignore @query @command
Feature: 線性觀看（Sequential Chapter Viewing）

  課程管理員可為每門課程開啟「線性觀看」模式。
  開啟後，學員必須按照章節排序（get_flatten_post_ids）依序完成，
  前一章節未完成前，後續章節將被鎖定。
  管理員與講師（manage_woocommerce / teacher_ids）不受限制。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
      | 3      | Bob   | bob@test.com   | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | teacher_ids |
      | 100      | PHP 基礎課 | yes        | publish | [1]         |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 10         |
      | 201       | 1-1        | 200         | 10         |
      | 202       | 1-2        | 200         | 20         |
      | 203       | 第二章     | 100         | 20         |
      | 204       | 2-1        | 203         | 10         |
    And 展開後的完整排序為 [200, 201, 202, 203, 204]
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Bob" 已被加入課程 100，expire_date 0

  # ==========================================================================
  # 管理設定：enable_linear_mode meta
  # ==========================================================================

  Rule: 課程 meta enable_linear_mode 預設為 'no'，管理員可切換為 'yes'

    Example: 課程預設不開啟線性觀看
      Given 課程 100 未設定 enable_linear_mode
      When 查詢課程 100 的 enable_linear_mode
      Then enable_linear_mode 應為 "no"

    Example: 管理員開啟線性觀看
      When 管理員將課程 100 的 enable_linear_mode 設為 "yes"
      Then 課程 100 的 enable_linear_mode 應為 "yes"

    Example: 管理員關閉線性觀看
      Given 課程 100 的 enable_linear_mode 為 "yes"
      When 管理員將課程 100 的 enable_linear_mode 設為 "no"
      Then 課程 100 的 enable_linear_mode 應為 "no"

  # ==========================================================================
  # 線性觀看關閉：所有章節自由存取
  # ==========================================================================

  Rule: 線性觀看關閉時，所有章節均可自由存取

    Example: 線性觀看關閉時，學員可存取任意章節
      Given 課程 100 的 enable_linear_mode 為 "no"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為可存取

  # ==========================================================================
  # 線性觀看開啟：第一個章節永遠可存取
  # ==========================================================================

  Rule: 線性觀看開啟時，展開排序中的第一個章節永遠可存取

    Example: 第一個章節（排序首位）始終可存取
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 檢查章節 200 (第一章) 的存取權
      Then 章節 200 應為可存取

  # ==========================================================================
  # 線性觀看開啟：前一章節未完成則鎖定後續章節
  # ==========================================================================

  Rule: 前一章節未完成時，後續章節被鎖定

    Example: 第一章未完成時，1-1 被鎖定
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 檢查章節 201 (1-1) 的存取權
      Then 章節 201 應為鎖定

    Example: 第一章未完成時，2-1 被鎖定
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為鎖定

    Example: 依序完成後下一章節解鎖
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 檢查章節 201 (1-1) 的存取權
      Then 章節 201 應為可存取

    Example: 跳過中間章節仍然被鎖定
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 檢查章節 202 (1-2) 的存取權
      Then 章節 202 應為鎖定

    Example: 所有前面章節完成後最後一個章節可存取
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 的 finished_at 為 "2025-06-01 13:00:00"
      When 用戶 "Alice" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為可存取

  # ==========================================================================
  # 線性觀看開啟：取消完成後重新鎖定後續章節
  # ==========================================================================

  Rule: 取消一個章節的完成後，該章節之後的所有章節重新鎖定

    Example: 取消 1-1 完成後，1-2 重新鎖定
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 章節 201 對用戶 "Alice" 的 finished_at 應為空
      When 用戶 "Alice" 檢查章節 202 (1-2) 的存取權
      Then 章節 202 應為鎖定

    Example: 取消完成不會清除後續章節的完成紀錄
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 章節 200 對用戶 "Alice" 的 finished_at 應為空
      And 章節 201 對用戶 "Alice" 的 finished_at 應不為空
      And 章節 202 對用戶 "Alice" 的 finished_at 應不為空

  # ==========================================================================
  # 管理員/講師豁免
  # ==========================================================================

  Rule: 管理員（manage_woocommerce）和講師（teacher_ids）不受線性觀看限制

    Example: 管理員可存取任意鎖定章節
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Admin" 未完成任何章節
      When 用戶 "Admin" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為可存取

    Example: 講師可存取任意鎖定章節
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Teacher" 是課程 100 的講師
      And 用戶 "Teacher" 未完成任何章節
      When 用戶 "Teacher" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為可存取

  # ==========================================================================
  # Template 層鎖定：直接 URL 存取
  # ==========================================================================

  Rule: 學員透過直接 URL 存取被鎖定章節時，顯示鎖定提示頁面

    Example: 直接 URL 存取鎖定章節顯示鎖定頁面
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 透過 URL 存取章節 201 (1-1)
      Then 頁面應顯示「請先完成前面的章節才能觀看此章節」提示
      And 頁面不應顯示章節 201 的影片內容
      And 頁面不應顯示章節 201 的正文內容

  # ==========================================================================
  # API 層鎖定：REST API 拒絕回傳
  # ==========================================================================

  Rule: REST API 拒絕回傳被鎖定章節的內容

    Example: API 取得被鎖定章節時回傳 403
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 透過 API 切換章節 201 的完成狀態
      Then API 回傳 403
      And 回應訊息包含「章節已鎖定」

  # ==========================================================================
  # 教室頁面：章節列表鎖定狀態
  # ==========================================================================

  Rule: 教室頁面章節列表中，被鎖定章節顯示鎖頭圖示

    Example: 學員在教室頁面看到鎖定章節的鎖頭圖示
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 查看課程 100 的教室頁面章節列表
      Then 章節 200 應顯示完成圖示
      And 章節 201 應無鎖定圖示（已解鎖）
      And 章節 202 應顯示鎖頭圖示
      And 章節 203 應顯示鎖頭圖示
      And 章節 204 應顯示鎖頭圖示

  Rule: 點擊鎖定章節時顯示 Toast 提示，不跳轉頁面

    Example: 點擊鎖定章節顯示 Toast
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 在教室頁面點擊章節 201 (1-1)
      Then 頁面顯示 Toast 提示「請先完成前面的章節才能觀看此章節」
      And 頁面不跳轉

  # ==========================================================================
  # 邊界情境：開啟後既有進度正確計算
  # ==========================================================================

  Rule: 開啟線性觀看後，已有完成紀錄的章節保持完成，解鎖根據現有進度計算

    Example: 已完成部分章節後開啟線性觀看
      Given 課程 100 的 enable_linear_mode 為 "no"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      When 管理員將課程 100 的 enable_linear_mode 設為 "yes"
      Then 章節 200 對用戶 "Alice" 的 finished_at 應不為空
      And 章節 201 對用戶 "Alice" 的 finished_at 應不為空
      When 用戶 "Alice" 檢查章節 202 (1-2) 的存取權
      Then 章節 202 應為可存取

    Example: 非連續完成後開啟線性觀看，中斷處之後鎖定
      Given 課程 100 的 enable_linear_mode 為 "no"
      And 用戶 "Bob" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Bob" 在章節 201 無 finished_at
      And 用戶 "Bob" 在章節 204 的 finished_at 為 "2025-06-01 12:00:00"
      When 管理員將課程 100 的 enable_linear_mode 設為 "yes"
      And 用戶 "Bob" 檢查章節 202 (1-2) 的存取權
      Then 章節 202 應為鎖定
      When 用戶 "Bob" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為鎖定

  # ==========================================================================
  # 邊界情境：關閉線性觀看後所有章節解鎖
  # ==========================================================================

  Rule: 關閉線性觀看後，所有章節恢復自由存取

    Example: 關閉線性觀看後所有章節可存取
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 管理員將課程 100 的 enable_linear_mode 設為 "no"
      And 用戶 "Alice" 檢查章節 204 (2-1) 的存取權
      Then 章節 204 應為可存取

  # ==========================================================================
  # 邊界情境：不同學員進度互不影響
  # ==========================================================================

  Rule: 不同學員的進度獨立，解鎖狀態互不影響

    Example: Alice 的進度不影響 Bob
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Bob" 未完成任何章節
      When 用戶 "Alice" 檢查章節 201 (1-1) 的存取權
      Then 章節 201 應為可存取
      When 用戶 "Bob" 檢查章節 201 (1-1) 的存取權
      Then 章節 201 應為鎖定

  # ==========================================================================
  # 邊界情境：課程無章節
  # ==========================================================================

  Rule: 課程無章節時，線性觀看開關不影響任何行為

    Example: 空課程開啟線性觀看不報錯
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  |
        | 101      | 空白課程   | yes        | publish |
      And 課程 101 無任何章節
      When 管理員將課程 101 的 enable_linear_mode 設為 "yes"
      Then 設定成功儲存

  # ==========================================================================
  # 下一章節按鈕行為
  # ==========================================================================

  Rule: 教室 header 的「前往下一章節」按鈕在下一章節被鎖定時應禁用

    Example: 下一章節被鎖定時按鈕禁用
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 未完成任何章節
      When 用戶 "Alice" 在章節 200 的教室頁面
      Then 「前往下一章節」按鈕應為禁用狀態
      And 「標示為已完成」按鈕應正常顯示

    Example: 完成當前章節後下一章節按鈕啟用
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 在章節 200 的教室頁面
      Then 「前往下一章節」按鈕應為啟用狀態
