@ignore @command
Feature: 切換章節完成與線性觀看連動

  當課程啟用線性觀看時，切換章節完成狀態會連動影響後續章節的解鎖狀態。
  此 Feature 覆蓋線性觀看模式下 toggle-finish-chapters API 的額外行為。

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

  # ========== 鎖定章節不可切換完成 ==========

  Rule: 學員不能對鎖定的章節執行完成切換

    Example: 嘗試完成鎖定章節被拒
      Given 用戶 "Alice" 無任何章節完成紀錄
      # 只有 200 解鎖，201-204 鎖定
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作失敗，錯誤為「章節尚未解鎖，請先完成前面的章節」

  # ========== 完成後 API 回傳更新的解鎖狀態 ==========

  Rule: 完成章節後，toggle-finish-chapters API 回應包含 next_unlocked_chapter_id

    Example: 完成第一章後回傳下一個解鎖的章節
      Given 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 回應 data 中 is_this_chapter_finished 應為 true
      And 回應 data 中 next_unlocked_chapter_id 應為 201

    Example: 完成最後一個章節時 next_unlocked_chapter_id 為 null
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 的 finished_at 為 "2025-06-01 13:00:00"
      And 用戶 "Alice" 在章節 204 無 finished_at
      When 用戶 "Alice" 切換章節 204 的完成狀態
      Then 操作成功
      And 回應 data 中 next_unlocked_chapter_id 應為 null

  # ========== 取消完成的連鎖影響 ==========

  Rule: 取消完成某章節不會連鎖清除後續已完成章節的 finished_at

    Example: 取消完成 1-1 後，已完成的 1-2 的 finished_at 保留
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應為空
      And 章節 202 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  Rule: 取消完成後，未完成的後續章節重新鎖定（前一個未完成則鎖定）

    Example: 取消 1-1 完成後，未完成的 1-3 和之後鎖定
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 無 finished_at
      And 用戶 "Alice" 在章節 203 無 finished_at
      # 取消 201
      When 用戶 "Alice" 切換章節 201 的完成狀態
      Then 操作成功
      # 取消後狀態：200已完成, 201未完成, 202未完成, 203未完成, 204未完成
      And 查詢用戶 "Alice" 在課程 100 各章節的解鎖狀態應為：
        | chapterId | is_locked |
        | 200       | false     |
        | 201       | false     |
        | 202       | true      |
        | 203       | true      |
        | 204       | true      |

  # ========== 未啟用線性觀看不受影響 ==========

  Rule: 未啟用線性觀看時，切換完成行為與既有一致

    Example: 關閉線性觀看時正常切換
      Given 課程 100 的 enable_linear_viewing 為 "no"
      And 用戶 "Alice" 無任何章節完成紀錄
      When 用戶 "Alice" 切換章節 204 的完成狀態
      Then 操作成功
      And 回應 data 中 is_this_chapter_finished 應為 true
