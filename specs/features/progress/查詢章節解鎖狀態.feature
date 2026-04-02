@ignore @query
Feature: 查詢章節解鎖狀態

  學員查詢課程進度時，若課程開啟線性觀看，回應中應包含各章節的解鎖狀態，
  以及「下一個可觀看章節」的 ID，供前端渲染鎖定/解鎖圖示。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name           | _is_course | status  | enable_linear_viewing |
      | 100      | Python 從零到一 | yes        | publish | yes                   |
    And 課程 100 有以下章節（攤平排序）：
      | chapterId | post_title  | post_parent | menu_order |
      | 200       | 1-1 基礎語法 | 100         | 10         |
      | 201       | 1-2 變數型別 | 100         | 20         |
      | 202       | 1-3 流程控制 | 100         | 30         |
      | 203       | 2-1 函式入門 | 100         | 40         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 線性觀看開啟時，回傳 linear_viewing 與 unlocked_chapters

    Example: 學員無完成紀錄時，僅第一章解鎖
      Given 用戶 "Alice" 在課程 100 無任何章節完成紀錄
      When 用戶 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 linear_viewing 應為 true
      And 回應中 unlocked_chapters 應為 [200]
      And 回應中 next_chapter_id 應為 200

    Example: 完成 1-1 後，解鎖章節含 1-1 和 1-2
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 unlocked_chapters 應為 [200, 201]
      And 回應中 next_chapter_id 應為 201

    Example: 完成所有章節後，全部解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 11:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      And 用戶 "Alice" 在章節 203 的 finished_at 為 "2025-06-01 13:00:00"
      When 用戶 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 unlocked_chapters 應為 [200, 201, 202, 203]
      And 回應中 next_chapter_id 應為 null

  Rule: 後置（回應）- 連續完成鏈斷裂時，僅解鎖到斷裂處

    Example: 跳著完成的情況下，依連續完成鏈解鎖
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 201 無 finished_at
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 用戶 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 unlocked_chapters 應為 [200, 201]
      And 回應中 next_chapter_id 應為 201
      # 雖然 202 已完成，但連續鏈在 201 斷裂，所以 202 和 203 都未解鎖

  Rule: 後置（回應）- 線性觀看關閉時，不回傳 unlocked_chapters

    Example: 線性觀看關閉時，所有章節視為已解鎖
      Given 課程 100 的 enable_linear_viewing 為 "no"
      When 用戶 "Alice" 查詢課程 100 的進度
      Then 操作成功
      And 回應中 linear_viewing 應為 false
      And 回應中不包含 unlocked_chapters 欄位
      And 回應中不包含 next_chapter_id 欄位
