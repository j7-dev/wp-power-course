@ignore @query
Feature: 線性觀看章節鎖定

  課程啟用「線性觀看」模式（sequential_mode = yes）後，學員必須依照章節扁平順序逐一完成，
  才能解鎖後續章節。鎖定規則採用嚴格模式：一個章節可存取 ＝「已完成」或「前面所有章節都已完成」。
  第一個章節永遠可存取。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | sequential_mode |
      | 100      | Python 入門 | yes        | publish | yes             |
    And 課程 100 有以下章節（扁平順序）：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 1          |
      | 201       | 1-1        | 200         | 1          |
      | 202       | 1-2        | 200         | 2          |
      | 203       | 第二章     | 100         | 2          |
      | 204       | 2-1        | 203         | 1          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 核心鎖定規則 ==========

  Rule: 啟用線性觀看時，第一個章節永遠可存取

    Example: 新學員進入課程，第一個章節解鎖
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1        | true      |
        | 202       | 1-2        | true      |
        | 203       | 第二章     | true      |
        | 204       | 2-1        | true      |

  Rule: 嚴格規則 — 章節可存取 = 「已完成」或「前面所有章節都已完成」

    Example: 完成第一章後，下一章（1-1）解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1        | false     |
        | 202       | 1-2        | true      |
        | 203       | 第二章     | true      |
        | 204       | 2-1        | true      |

    Example: 跳著完成章節時，中間未完成的會阻擋後續章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1        | false     |
        | 202       | 1-2        | false     |
        | 203       | 第二章     | true      |
        | 204       | 2-1        | true      |
      And 章節 203 被鎖定的原因是「章節 201 (1-1) 尚未完成」

    Example: 所有章節都已完成，全部解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 的 finished_at 為 "2025-06-01 13:00:00"
      And 用戶 "Alice" 在章節 204 的 finished_at 為 "2025-06-01 14:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的 is_locked 應為 false

  # ========== 未啟用線性觀看 ==========

  Rule: 未啟用線性觀看的課程，所有章節皆可存取

    Example: sequential_mode 為 no 時，不鎖定任何章節
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | sequential_mode |
        | 101      | JS 課程    | yes        | publish | no              |
      And 課程 101 有以下章節（扁平順序）：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | Ch-1       | 101         | 1          |
        | 301       | Ch-2       | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 在課程 101 無任何章節完成紀錄
      When 用戶 "Alice" 查詢課程 101 的章節鎖定狀態
      Then 所有章節的 is_locked 應為 false

  # ========== 管理員豁免 ==========

  Rule: 管理員（manage_woocommerce 權限）不受線性觀看限制

    Example: 管理員可自由存取任何章節
      Given 用戶 "Admin" 未被加入課程 100
      When 管理員 "Admin" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的 is_locked 應為 false

  # ========== 後端存取阻擋 ==========

  Rule: 學員透過 URL 直接存取被鎖定章節時，後端重定向到第一個未完成章節

    Example: 嘗試直接存取被鎖定的章節 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      When 用戶 "Alice" 透過 URL 直接存取章節 202
      Then 系統重定向到章節 201 的頁面

    Example: 嘗試直接存取第一個章節（永遠可存取）
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 透過 URL 直接存取章節 200
      Then 正常載入章節 200 的內容

    Example: 管理員透過 URL 存取任何章節不受限制
      When 管理員 "Admin" 透過 URL 直接存取章節 204
      Then 正常載入章節 204 的內容

  # ========== 取消完成的級聯鎖定 ==========

  Rule: 取消章節完成標記後，後續未完成的章節重新鎖定，已完成的章節不受影響

    Example: 取消中間章節的完成標記，已完成的後續章節保持可存取
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 無 finished_at
      When 用戶 "Alice" 取消章節 200 的完成標記
      Then 章節鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1        | false     |
        | 202       | 1-2        | false     |
        | 203       | 第二章     | true      |
        | 204       | 2-1        | true      |
      And 章節 201 可存取因為其本身 finished_at 不為空
      And 章節 202 可存取因為其本身 finished_at 不為空

  # ========== 管理員關閉線性觀看 ==========

  Rule: 關閉線性觀看後，所有章節恢復自由存取

    Example: 管理員關閉 sequential_mode
      Given 管理員 "Admin" 更新課程 100，設定 sequential_mode 為 "no"
      And 用戶 "Alice" 在課程 100 僅完成章節 200
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的 is_locked 應為 false

  # ========== 銷售頁行為 ==========

  Rule: 課程銷售頁不顯示鎖定資訊，維持現有行為

    Example: 未購買的用戶瀏覽課程銷售頁
      Given 用戶 "Bob" 未被加入課程 100
      When 用戶 "Bob" 瀏覽課程 100 的銷售頁
      Then 章節列表以 context='course-product' 模式渲染
      And 不顯示任何鎖頭圖示或鎖定提示
