@ignore @query
Feature: 查詢章節鎖定狀態

  啟用線性觀看的課程，系統根據章節的扁平排序（menu_order）與學員完成狀態，
  計算每個章節的鎖定 / 解鎖狀態。

  判定邏輯：
  - 章節依 menu_order 排序後扁平化（含父章節與子章節）
  - 第一個章節永遠解鎖
  - 後續章節需前一個章節已完成（finished_at 不為空）才解鎖
  - 已完成的章節（自身有 finished_at）永遠解鎖，不受前方章節狀態影響
  - 父章節也納入線性序列，必須標記完成才能解鎖下一章

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
      | 204       | 2-1 小節   | 203         | 10         | 1     |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 未啟用線性觀看的課程，所有章節皆不鎖定

    Example: 自由觀看模式下所有章節解鎖
      Given 課程 100 的 enable_linear_viewing 為 "no"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 所有章節的 is_locked 應為 false

  Rule: 前置（狀態）- 學員必須有該課程存取權才能查詢

    Example: 無存取權時查詢失敗
      Given 用戶 "Bob" 未被加入課程 100
      When 用戶 "Bob" 查詢課程 100 的章節鎖定狀態
      Then 操作失敗，錯誤為「無此課程存取權」

  # ========== 後置（回應）- 基礎解鎖邏輯 ==========

  Rule: 後置（回應）- 第一個章節（扁平序列第一個）永遠解鎖

    Example: 新學員僅第一個章節解鎖
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 各章節的鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1 小節   | true      |
        | 202       | 1-2 小節   | true      |
        | 203       | 第二章     | true      |
        | 204       | 2-1 小節   | true      |

  Rule: 後置（回應）- 完成前一章節後，下一章節解鎖

    Example: 完成第一章後 1-1 解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 各章節的鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1 小節   | false     |
        | 202       | 1-2 小節   | true      |
        | 203       | 第二章     | true      |
        | 204       | 2-1 小節   | true      |

  Rule: 後置（回應）- 父章節納入線性序列，必須標記完成才能解鎖子章節

    Example: 依序完成到 1-2 小節後，第二章解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 10:10:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 10:20:00"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 各章節的鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1 小節   | false     |
        | 202       | 1-2 小節   | false     |
        | 203       | 第二章     | false     |
        | 204       | 2-1 小節   | true      |

  # ========== 後置（回應）- 取消完成的連鎖影響 ==========

  Rule: 後置（回應）- 取消完成某章節後，後續未完成的章節重新鎖定

    Example: 取消 1-1 完成後，1-2 重新鎖定
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 無 finished_at
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 章節 201 的 is_locked 應為 false
      And 章節 202 的 is_locked 應為 true

  Rule: 後置（回應）- 已完成的章節即使前方有未完成章節，仍然解鎖（已完成=已解鎖）

    Example: 1-2 已完成但 1-1 被取消完成，1-2 仍可觀看
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 10:20:00"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 各章節的鎖定狀態應為：
        | chapterId | post_title | is_locked |
        | 200       | 第一章     | false     |
        | 201       | 1-1 小節   | false     |
        | 202       | 1-2 小節   | false     |
        | 203       | 第二章     | true      |
        | 204       | 2-1 小節   | true      |

  # ========== 後置（回應）- 章節重排序 ==========

  Rule: 後置（回應）- 重排序後，已完成章節不受影響，未完成章節依新順序判定

    Example: 重排序後已完成章節維持解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 10:10:00"
      And 管理員將章節 203 的 menu_order 改為 5（排到最前面）
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 章節 200 的 is_locked 應為 false
      And 章節 201 的 is_locked 應為 false

  # ========== 後置（回應）- 邊界情境 ==========

  Rule: 後置（回應）- 只有一個章節的課程，該章節始終解鎖

    Example: 單章節課程
      Given 系統中有以下課程：
        | courseId | name     | _is_course | status  | enable_linear_viewing |
        | 101      | 單章節課 | yes        | publish | yes                   |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent |
        | 300       | 唯一章節   | 101         |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      When 用戶 "Alice" 查詢課程 101 的章節列表
      Then 操作成功
      And 章節 300 的 is_locked 應為 false

  Rule: 後置（回應）- 中途啟用線性觀看時，已完成章節的進度被保留

    Example: 中途啟用線性觀看，已完成章節仍解鎖
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 10:10:00"
      And 管理員 "Admin" 設定課程 100 的 enable_linear_viewing 為 "yes"
      When 用戶 "Alice" 查詢課程 100 的章節列表
      Then 操作成功
      And 章節 200 的 is_locked 應為 false
      And 章節 201 的 is_locked 應為 false
      And 章節 202 的 is_locked 應為 false
      And 章節 203 的 is_locked 應為 true
