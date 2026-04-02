@ignore @query
Feature: 章節線性存取驗證

  課程啟用「線性觀看」(is_sequential) 後，學員必須按照章節排序順序
  （DFS 前序遍歷：父章節 → 子章節，依 menu_order ASC）逐一完成，
  才能存取下一個章節。父章節也需要被完成才能進入其子章節（Q1:B）。

  解鎖規則：章節 N 可存取 ⟺ 序列中 N 之前的所有章節皆已完成，或 N 是序列中的第一個章節。
  存取被拒時：自動導向目前應觀看的章節，並顯示提示訊息（Q2:A）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | is_sequential |
      | 100      | Python 入門 | yes        | publish | yes           |
    And 課程 100 有以下章節結構（DFS 前序排列）：
      | chapterId | post_title | post_parent | menu_order | depth |
      | 300       | 第一章     | 0           | 1          | 0     |
      | 301       | 1-1        | 300         | 1          | 1     |
      | 302       | 1-2        | 300         | 2          | 1     |
      | 400       | 第二章     | 0           | 2          | 0     |
      | 401       | 2-1        | 400         | 1          | 1     |
      | 402       | 2-2        | 400         | 2          | 1     |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # 解鎖順序（DFS 前序）：第一章(300) → 1-1(301) → 1-2(302) → 第二章(400) → 2-1(401) → 2-2(402)

  # ========== 基本存取規則 ==========

  Rule: 第一個章節永遠可存取（不受線性觀看限制）

    Example: 無任何完成紀錄時可存取第一章
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 300
      Then 結果為可存取

    Example: 第一個章節永遠不會被鎖定
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 查詢用戶 "Alice" 在課程 100 的章節存取清單
      Then 章節 300 的 is_locked 應為 false

  Rule: 已完成前一個章節後，下一個章節可存取

    Example: 完成第一章後可存取 1-1
      Given 用戶 "Alice" 已完成章節 [300]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 301
      Then 結果為可存取

    Example: 完成第一章和 1-1 後可存取 1-2
      Given 用戶 "Alice" 已完成章節 [300, 301]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 302
      Then 結果為可存取

    Example: 完成 1-2 後可跨父章節存取第二章
      Given 用戶 "Alice" 已完成章節 [300, 301, 302]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 400
      Then 結果為可存取

    Example: 完成第二章後可存取 2-1
      Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 401
      Then 結果為可存取

  Rule: 未完成前一個章節時，後續章節不可存取

    Example: 未完成第一章時不可存取 1-1
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 301
      Then 結果為不可存取

    Example: 完成第一章但未完成 1-1 時不可存取 1-2
      Given 用戶 "Alice" 已完成章節 [300]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 302
      Then 結果為不可存取

    Example: 未完成 1-2 時不可跨區段存取第二章
      Given 用戶 "Alice" 已完成章節 [300, 301]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 400
      Then 結果為不可存取

    Example: 不可跳過中間章節存取最後章節
      Given 用戶 "Alice" 已完成章節 [300]
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 402
      Then 結果為不可存取

  # ========== 非連續完成情境（既有學員開啟線性觀看後） ==========

  Rule: 開啟線性觀看後，即使有非連續完成紀錄，仍以第一個未完成章節為分界鎖定

    Example: 跳過 1-1 完成了 1-2 和第二章，開啟線性觀看後只能看到第一章和 1-1
      Given 用戶 "Alice" 已完成章節 [300, 302, 400]
      When 查詢用戶 "Alice" 在課程 100 的章節存取清單
      Then 章節 300 的 is_locked 應為 false
      And 章節 301 的 is_locked 應為 false
      And 章節 302 的 is_locked 應為 true
      And 章節 400 的 is_locked 應為 true
      And 章節 401 的 is_locked 應為 true
      And 章節 402 的 is_locked 應為 true

  # ========== 取消完成的連鎖鎖定 ==========

  Rule: 取消完成某章節後，該章節之後所有章節根據新的完成狀態重新計算鎖定

    Example: 取消完成第一章後 1-1 及之後章節全部鎖定
      Given 用戶 "Alice" 已完成章節 [300, 301, 302]
      When 用戶 "Alice" 取消完成章節 300
      Then 章節 300 對用戶 "Alice" 應為可存取
      And 章節 301 對用戶 "Alice" 應為不可存取
      And 章節 302 對用戶 "Alice" 應為不可存取
      And 章節 400 對用戶 "Alice" 應為不可存取

    Example: 取消完成中間章節後僅影響其後方
      Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400, 401]
      When 用戶 "Alice" 取消完成章節 301
      Then 章節 300 對用戶 "Alice" 應為可存取
      And 章節 301 對用戶 "Alice" 應為可存取
      And 章節 302 對用戶 "Alice" 應為不可存取
      And 章節 400 對用戶 "Alice" 應為不可存取
      And 章節 401 對用戶 "Alice" 應為不可存取

  # ========== 線性觀看關閉時 ==========

  Rule: is_sequential 為 false 時所有章節可自由存取

    Example: 未啟用線性觀看時可存取任何章節
      Given 課程 100 的 is_sequential 為 false
      And 用戶 "Alice" 無任何章節完成紀錄
      When 檢查用戶 "Alice" 是否可存取課程 100 的章節 402
      Then 結果為可存取

    Example: 關閉線性觀看後所有章節立即恢復自由存取
      Given 課程 100 的 is_sequential 為 true
      And 用戶 "Alice" 已完成章節 [300]
      And 章節 302 對用戶 "Alice" 為不可存取
      When 管理員將課程 100 的 is_sequential 設為 false
      Then 章節 302 對用戶 "Alice" 應為可存取
      And 用戶 "Alice" 的完成紀錄不受影響

  # ========== 單層章節支援 ==========

  Rule: 無子章節的單層課程也支援線性觀看，依 menu_order 排序

    Example: 單層課程的線性觀看
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | is_sequential |
        | 200      | JS 基礎    | yes        | publish | yes           |
      And 課程 200 有以下章節結構（DFS 前序排列）：
        | chapterId | post_title | post_parent | menu_order | depth |
        | 500       | 第一課     | 0           | 1          | 0     |
        | 501       | 第二課     | 0           | 2          | 0     |
        | 502       | 第三課     | 0           | 3          | 0     |
      And 用戶 "Alice" 已被加入課程 200，expire_date 0
      And 用戶 "Alice" 已完成章節 [500]
      When 查詢用戶 "Alice" 在課程 200 的章節存取清單
      Then 章節 500 的 is_locked 應為 false
      And 章節 501 的 is_locked 應為 false
      And 章節 502 的 is_locked 應為 true

  # ========== 存取被拒時的行為（PHP 前台） ==========

  Rule: 學員嘗試存取被鎖定的章節時，自動導向目前應觀看的章節

    Example: 嘗試存取鎖定章節時導向下一個可觀看章節
      Given 用戶 "Alice" 已完成章節 [300]
      When 用戶 "Alice" 透過 URL 直接存取章節 400
      Then 系統將用戶導向章節 301
      And 顯示提示訊息「請先完成前面的章節，才能觀看此章節喔！」

    Example: 無任何完成紀錄時導向第一個章節
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 直接存取章節 302
      Then 系統將用戶導向章節 300
      And 顯示提示訊息「請先完成前面的章節，才能觀看此章節喔！」

  # ========== API 存取保護 ==========

  Rule: 後端 API 拒絕對鎖定章節的操作，防止繞過前端限制

    Example: 嘗試透過 API 完成鎖定章節時返回錯誤
      Given 用戶 "Alice" 已完成章節 [300]
      When 用戶 "Alice" 切換章節 302 的完成狀態
      Then 操作失敗，錯誤為「請先完成前面的章節」

  # ========== 完成所有章節 ==========

  Rule: 所有章節完成後全部可存取

    Example: 完成所有章節後全部解鎖
      Given 用戶 "Alice" 已完成章節 [300, 301, 302, 400, 401, 402]
      When 查詢用戶 "Alice" 在課程 100 的章節存取清單
      Then 章節 300 的 is_locked 應為 false
      And 章節 301 的 is_locked 應為 false
      And 章節 302 的 is_locked 應為 false
      And 章節 400 的 is_locked 應為 false
      And 章節 401 的 is_locked 應為 false
      And 章節 402 的 is_locked 應為 false
