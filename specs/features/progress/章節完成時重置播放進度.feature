@ignore @command
Feature: 章節完成時重置播放進度

  當學員切換章節完成狀態為「已完成」時，
  系統將 last_visit_info 中的 video_progress_seconds 重置為 0，
  使學員回看已完成章節時從頭開始播放。
  取消完成時不恢復舊的 video_progress_seconds。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent |
      | 200       | 第一章     | 100         |
      | 201       | 第二章     | 100         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 完成時重置 ==========

  Rule: 標記章節為完成時，video_progress_seconds 重置為 0

    Example: 完成章節後 video_progress_seconds 歸零
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，video_progress_seconds 332
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 用戶 "Alice" 在課程 100 的 last_visit_info.video_progress_seconds 應為 0
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  # ========== 取消完成時不恢復 ==========

  Rule: 取消完成時，video_progress_seconds 維持為 0（不恢復舊值）

    Example: 取消完成後 video_progress_seconds 仍為 0
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，video_progress_seconds 0
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 用戶 "Alice" 切換章節 200 的完成狀態
      Then 操作成功
      And 用戶 "Alice" 在課程 100 的 last_visit_info.video_progress_seconds 應為 0
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應為空
