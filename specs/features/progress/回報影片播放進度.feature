@ignore @command
Feature: 回報影片播放進度

  學員在教室頁面觀看影片時，前端定期回報播放秒數至後端，
  使學員下次進入同一章節時可從中斷處繼續播放。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | chapter_video_type |
      | 200       | 第一章     | 100         | bunny-stream-api   |
      | 201       | 第二章     | 100         | youtube            |
      | 202       | 第三章     | 100         | none               |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 必須已登入

    Example: 未登入訪客回報播放進度失敗
      When 未登入訪客回報章節 200 的播放進度為 120 秒
      Then 操作失敗，HTTP 狀態碼為 401

  Rule: 前置（狀態）- 學員必須擁有該章節所屬課程的存取權

    Example: 無課程存取權時回報失敗
      Given 用戶 "Bob" 未被加入課程 100
      When 用戶 "Bob" 回報章節 200 的播放進度為 120 秒
      Then 操作失敗，錯誤為「無此課程存取權」

  Rule: 前置（狀態）- 課程存取未到期

    Example: 課程已到期時回報失敗
      Given 用戶 "Alice" 在課程 100 的 expire_date 為 1609459200
      When 用戶 "Alice" 回報章節 200 的播放進度為 120 秒
      Then 操作失敗，錯誤為「課程存取已到期」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- chapter_id 必須存在且 post_type 為 pc_chapter

    Example: 章節不存在時回報失敗
      When 用戶 "Alice" 回報章節 9999 的播放進度為 120 秒
      Then 操作失敗，錯誤為「章節不存在」

  Rule: 前置（參數）- video_progress_seconds 必須為非負整數且不超過 86400

    Example: 負數秒數時回報失敗
      When 用戶 "Alice" 回報章節 200 的播放進度為 -10 秒
      Then 操作失敗，錯誤為「video_progress_seconds 必須為 0 到 86400 之間的整數」

    Example: 超過 24 小時的秒數時回報失敗
      When 用戶 "Alice" 回報章節 200 的播放進度為 100000 秒
      Then 操作失敗，錯誤為「video_progress_seconds 必須為 0 到 86400 之間的整數」

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功回報後更新 last_visit_info 中的 video_progress_seconds

    Example: Bunny Stream 影片回報播放進度
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，last_visit_at "2025-06-01 10:30:00"
      When 用戶 "Alice" 回報章節 200 的播放進度為 332 秒
      Then 操作成功
      And 用戶 "Alice" 在課程 100 的 last_visit_info 應包含：
        | 欄位                   | 期望值              |
        | chapter_id             | 200                 |
        | video_progress_seconds | 332                 |
      And last_visit_info 的 last_visit_at 應被更新為當前時間

    Example: YouTube 影片回報播放進度
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 201，last_visit_at "2025-06-01 10:30:00"
      When 用戶 "Alice" 回報章節 201 的播放進度為 765 秒
      Then 操作成功
      And 用戶 "Alice" 在課程 100 的 last_visit_info.video_progress_seconds 應為 765

  Rule: 後置（狀態）- 無影片的章節回報進度時 video_progress_seconds 寫入 0

    Example: 純文字章節回報進度
      When 用戶 "Alice" 回報章節 202 的播放進度為 60 秒
      Then 操作成功
      And 用戶 "Alice" 在課程 100 的 last_visit_info.video_progress_seconds 應為 0

  Rule: 後置（狀態）- 回報進度不觸發完整的 visit 流程（不寫入 student log）

    Example: 進度回報不產生 student log
      When 用戶 "Alice" 回報章節 200 的播放進度為 332 秒
      Then 操作成功
      And student_logs 中不應新增 CHAPTER_ENTERED 類型的紀錄

  Rule: 後置（狀態）- 回報進度不更新 pc_last_active_course user_meta

    Example: 進度回報不更新跨課程最後活動
      Given 用戶 "Alice" 的 pc_last_active_course 為 course_id 99，last_active_at "2025-06-01 09:00:00"
      When 用戶 "Alice" 回報章節 200 的播放進度為 332 秒
      Then 操作成功
      And 用戶 "Alice" 的 pc_last_active_course.course_id 應為 99

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 成功時回傳確認資訊

    Example: 成功回報後的回應格式
      When 用戶 "Alice" 回報章節 200 的播放進度為 332 秒
      Then 操作成功
      And 回應資料應包含：
        | 欄位                   | 期望值 |
        | video_progress_seconds | 332    |
