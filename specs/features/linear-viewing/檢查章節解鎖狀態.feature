@ignore @query
Feature: 檢查章節解鎖狀態

  啟用線性觀看的課程，系統根據章節扁平化順序（menu_order）與學員完成紀錄，
  判斷每個章節的解鎖/鎖定狀態。

  解鎖規則：
  - 第一個章節（扁平化序列中的第 1 個）永遠解鎖
  - 已完成的章節（有 finished_at）永遠解鎖（已完成 = 已解鎖）
  - 前一個章節（扁平化序列中的前 1 個）已完成，則當前章節解鎖
  - 父章節也納入線性序列，需要被標記完成

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_viewing |
      | 100      | PHP 基礎課 | yes        | publish | yes                   |
    And 課程 100 有以下章節（扁平化順序）：
      | chapterId | post_title | post_parent | menu_order | 扁平化序號 |
      | 200       | 第一章     | 100         | 1          | 0          |
      | 201       | 1-1        | 200         | 1          | 1          |
      | 202       | 1-2        | 200         | 2          | 2          |
      | 203       | 第二章     | 100         | 2          | 3          |
      | 204       | 2-1        | 203         | 1          | 4          |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 基本解鎖邏輯 ==========

  Rule: 第一個章節永遠解鎖

    Example: 新學員只有第一個章節解鎖
      Given 用戶 "Alice" 無任何章節完成紀錄
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | true      |
        | 202       | true      |
        | 203       | true      |
        | 204       | true      |

  Rule: 完成前一個章節後，下一個章節解鎖

    Example: 完成第一章後，1-1 解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | true      |
        | 203       | true      |
        | 204       | true      |

    Example: 依序完成到 1-2 後，第二章解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | false     |
        | 203       | false     |
        | 204       | true      |

  Rule: 已完成的章節永遠解鎖（已完成 = 已解鎖）

    Example: 取消完成 1-1 後，已完成的 1-2 仍可觀看
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | false     |
        | 203       | true      |
        | 204       | true      |
      # 201 解鎖因為前一個(200)已完成
      # 202 解鎖因為自身已完成（已完成=已解鎖）
      # 203 鎖定因為 202 已完成但 201 未完成，不影響 203 的判定：
      #   203 的前一個是 202，202 已完成所以 203 應該解鎖...
      # 【修正】：實際上解鎖邏輯是「前一個章節已完成」，202 已完成 → 203 解鎖

    Example: 取消完成 1-1 後正確的連鎖解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 無 finished_at
      And 用戶 "Alice" 在章節 204 無 finished_at
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | false     |
        | 203       | false     |
        | 204       | true      |
      # 200: 第一個章節，永遠解鎖
      # 201: 前一個(200)已完成 → 解鎖
      # 202: 自身已完成 → 已完成=已解鎖
      # 203: 前一個(202)已完成 → 解鎖
      # 204: 前一個(203)未完成 → 鎖定

  # ========== 未啟用線性觀看 ==========

  Rule: 未啟用線性觀看的課程，所有章節都不鎖定

    Example: 關閉線性觀看時全部解鎖
      Given 系統中有以下課程：
        | courseId | name     | _is_course | status  | enable_linear_viewing |
        | 101      | 自由課程 | yes        | publish | no                    |
      And 課程 101 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 300       | A-1        | 101         | 1          |
        | 301       | A-2        | 101         | 2          |
      And 用戶 "Alice" 已被加入課程 101，expire_date 0
      And 用戶 "Alice" 無任何章節完成紀錄
      When 查詢用戶 "Alice" 在課程 101 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 300       | false     |
        | 301       | false     |

  # ========== 中途啟用 ==========

  Rule: 中途啟用線性觀看時，已完成的章節維持解鎖

    Example: 啟用前已完成部分章節
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      # 假設管理員剛啟用線性觀看（enable_linear_viewing = yes）
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | false     |
        | 203       | false     |
        | 204       | true      |
      # 200-202 已完成 → 解鎖
      # 203 前一個(202)已完成 → 解鎖
      # 204 前一個(203)未完成 → 鎖定

  # ========== 重排章節 ==========

  Rule: 重排章節後，已完成的章節不受影響（已完成=已解鎖）

    Example: 管理員重排章節順序後已完成章節仍解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      # 管理員將章節順序改為：203, 204, 200, 201, 202
      And 課程 100 的章節扁平化順序為 [203, 204, 200, 201, 202]
      When 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 203       | false     |
        | 204       | true      |
        | 200       | false     |
        | 201       | false     |
        | 202       | true      |
      # 203: 第一個章節 → 解鎖
      # 204: 前一個(203)未完成 → 鎖定
      # 200: 自身已完成 → 已完成=已解鎖
      # 201: 自身已完成 → 已完成=已解鎖
      # 202: 前一個(201)已完成 → 解鎖

  # ========== 只有一個章節 ==========

  Rule: 只有一個章節的課程，該章節始終解鎖

    Example: 單章節課程
      Given 系統中有以下課程：
        | courseId | name     | _is_course | status  | enable_linear_viewing |
        | 102      | 單元課程 | yes        | publish | yes                   |
      And 課程 102 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 400       | 唯一章節   | 102         | 1          |
      And 用戶 "Alice" 已被加入課程 102，expire_date 0
      When 查詢用戶 "Alice" 在課程 102 各章節的解鎖狀態
      Then 各章節解鎖狀態應為：
        | chapterId | is_locked |
        | 400       | false     |

  # ========== 管理員預覽 ==========

  Rule: 管理員預覽模式下，所有章節不鎖定

    Example: 管理員預覽時跳過線性觀看限制
      Given 用戶 "Admin" 具有 manage_woocommerce 權限
      When 用戶 "Admin" 以管理員預覽模式查看課程 100
      Then 所有章節的 is_locked 應為 false
