@ignore @query
Feature: 繼續上課入口

  在「我的課程」頁面中，學員最後造訪的課程應排在第一位，
  並顯示醒目的「繼續上課」Badge，引導學員快速回到上次中斷處。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
      | 101      | JS 進階課  | yes        | publish |
      | 102      | CSS 入門   | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | chapter_video_type |
      | 200       | PHP 第一章 | 100         | bunny-stream-api   |
    And 課程 101 有以下章節：
      | chapterId | post_title | post_parent | chapter_video_type |
      | 300       | JS 第一章  | 101         | youtube            |
    And 課程 102 有以下章節：
      | chapterId | post_title | post_parent | chapter_video_type |
      | 400       | CSS 第一章 | 102         | vimeo              |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Alice" 已被加入課程 101，expire_date 0
    And 用戶 "Alice" 已被加入課程 102，expire_date 0

  # ========== 排序邏輯 ==========

  Rule: 最後造訪的課程排在列表第一位

    Example: 有 pc_last_active_course 時，該課程排在第一位
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 101，last_active_at "2025-06-01 10:00:00"
      When 用戶 "Alice" 進入「我的課程」頁面
      Then 課程列表的第一張卡片應為課程 101

    Example: 無 pc_last_active_course 時，維持原始排序
      Given 用戶 "Alice" 無 pc_last_active_course 記錄
      When 用戶 "Alice" 進入「我的課程」頁面
      Then 課程列表維持預設排序（不改變順序）

  # ========== Badge 標示 ==========

  Rule: 最後造訪的課程卡片顯示「繼續上課」Badge

    Example: 有 pc_last_active_course 的課程顯示 Badge
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 101，last_active_at "2025-06-01 10:00:00"
      When 用戶 "Alice" 進入「我的課程」頁面
      Then 課程 101 的卡片右上角應顯示「繼續上課」Badge
      And 其他課程卡片應顯示原有的狀態 Badge（可觀看/未開課/已到期）

    Example: 最後造訪的課程已到期時，不顯示「繼續上課」Badge
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 101，last_active_at "2025-06-01 10:00:00"
      And 用戶 "Alice" 在課程 101 的 expire_date 為 1609459200
      When 用戶 "Alice" 進入「我的課程」頁面
      Then 課程 101 的卡片應顯示「已到期」Badge（非「繼續上課」）

  # ========== 連結導向 ==========

  Rule: 課程卡片連結導向最後造訪的章節

    Example: 有 last_visit_info 時導向最後造訪章節
      Given 用戶 "Alice" 在課程 101 的 last_visit_info 為 chapter_id 300，last_visit_at "2025-06-01 10:30:00"，video_progress_seconds 765
      When 用戶 "Alice" 點擊課程 101 的卡片
      Then 頁面應導向章節 300 的教室頁面

    Example: 無 last_visit_info 時導向第一個章節
      Given 用戶 "Alice" 在課程 102 無 last_visit_info 記錄
      When 用戶 "Alice" 點擊課程 102 的卡片
      Then 頁面應導向課程 102 的第一個章節（按 menu_order 排序）

  # ========== 新學員邊界情境 ==========

  Rule: 新學員首次進入我的課程頁面

    Example: 新學員無任何造訪記錄
      Given 用戶 "Bob" 已被加入課程 100，expire_date 0
      And 用戶 "Bob" 無 pc_last_active_course 記錄
      And 用戶 "Bob" 在課程 100 無 last_visit_info 記錄
      When 用戶 "Bob" 進入「我的課程」頁面
      Then 課程列表維持預設排序
      And 課程 100 的卡片連結導向第一個章節（200）
      And 無任何課程顯示「繼續上課」Badge
