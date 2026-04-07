@ignore @command
Feature: 記錄跨課程最後上課資訊

  當學員進入教室頁面（觸發 power_course_visit_chapter hook）時，
  系統同步更新 wp_usermeta 中的 pc_last_active_course，
  記錄學員在所有課程中最後造訪的課程 ID 與時間。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
      | 101      | JS 進階課  | yes        | publish |
      | 102      | CSS 入門   | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent |
      | 200       | PHP 第一章 | 100         |
    And 課程 101 有以下章節：
      | chapterId | post_title | post_parent |
      | 300       | JS 第一章  | 101         |
      | 301       | JS 第二章  | 101         |
    And 課程 102 有以下章節：
      | chapterId | post_title | post_parent |
      | 400       | CSS 第一章 | 102         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Alice" 已被加入課程 101，expire_date 0
    And 用戶 "Alice" 已被加入課程 102，expire_date 0

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 進入教室時更新 pc_last_active_course

    Example: 首次進入課程時建立 pc_last_active_course
      Given 用戶 "Alice" 無 pc_last_active_course 記錄
      When 用戶 "Alice" 進入課程 100 的章節 200 教室頁面
      Then 用戶 "Alice" 的 pc_last_active_course 應包含：
        | 欄位           | 期望值 |
        | course_id      | 100    |
      And pc_last_active_course 的 last_active_at 應為當前時間

    Example: 切換課程時更新 pc_last_active_course
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 100，last_active_at "2025-06-01 09:00:00"
      When 用戶 "Alice" 進入課程 101 的章節 300 教室頁面
      Then 用戶 "Alice" 的 pc_last_active_course 應包含：
        | 欄位           | 期望值 |
        | course_id      | 101    |
      And pc_last_active_course 的 last_active_at 應晚於 "2025-06-01 09:00:00"

    Example: 同課程不同章節時更新 last_active_at
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 101，last_active_at "2025-06-01 09:00:00"
      When 用戶 "Alice" 進入課程 101 的章節 301 教室頁面
      Then 用戶 "Alice" 的 pc_last_active_course.course_id 應為 101
      And pc_last_active_course 的 last_active_at 應晚於 "2025-06-01 09:00:00"

  # ========== 後置（狀態）- 與 last_visit_info 同步 ==========

  Rule: 後置（狀態）- pc_last_active_course 與 last_visit_info 在同一 hook 中更新

    Example: visit_chapter 同時更新 last_visit_info 和 pc_last_active_course
      When 用戶 "Alice" 進入課程 100 的章節 200 教室頁面
      Then 用戶 "Alice" 在課程 100 的 last_visit_info.chapter_id 應為 200
      And 用戶 "Alice" 的 pc_last_active_course.course_id 應為 100
