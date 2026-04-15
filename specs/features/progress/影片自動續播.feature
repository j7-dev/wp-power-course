@ignore @query
Feature: 影片自動續播

  學員進入已有播放記錄的章節時，VidStack 播放器自動 seek 到上次播放位置。
  支援 Bunny Stream (HLS)、YouTube、Vimeo 三種影片類型。
  不支援 code（自訂 iframe）類型。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 2      | Alice | alice@test.com |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | chapter_video_type | chapter_video_duration |
      | 200       | 第一章     | 100         | bunny-stream-api   | 3600                   |
      | 201       | 第二章     | 100         | youtube            | 1800                   |
      | 202       | 第三章     | 100         | none               | 0                      |
      | 203       | 第四章     | 100         | vimeo              | 600                    |
      | 204       | 第五章     | 100         | code               | 0                      |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 自動 seek 行為 ==========

  Rule: 有播放記錄時，播放器自動 seek 到記錄位置

    Example: Bunny Stream 影片自動續播
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，video_progress_seconds 332
      When 用戶 "Alice" 進入章節 200 的教室頁面
      Then VidStack 播放器應在影片載入後 seek 到 332 秒

    Example: YouTube 影片自動續播
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 201，video_progress_seconds 765
      When 用戶 "Alice" 進入章節 201 的教室頁面
      Then VidStack 播放器應在影片載入後 seek 到 765 秒

    Example: Vimeo 影片自動續播
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 203，video_progress_seconds 180
      When 用戶 "Alice" 進入章節 203 的教室頁面
      Then VidStack 播放器應在影片載入後 seek 到 180 秒

  # ========== 無記錄時從頭播放 ==========

  Rule: 無播放記錄時，影片從頭播放

    Example: 首次進入章節
      Given 用戶 "Alice" 在課程 100 無 last_visit_info 記錄
      When 用戶 "Alice" 進入章節 200 的教室頁面
      Then VidStack 播放器從 0 秒開始播放

    Example: last_visit_info 中無 video_progress_seconds（舊資料向下相容）
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，last_visit_at "2025-06-01 10:30:00"（無 video_progress_seconds 欄位）
      When 用戶 "Alice" 進入章節 200 的教室頁面
      Then VidStack 播放器從 0 秒開始播放

  # ========== 影片被替換的容錯 ==========

  Rule: 記錄秒數超過影片長度時，fallback 到開頭

    Example: 影片被替換為較短影片
      Given 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 203，video_progress_seconds 900
      And 章節 203 的影片長度實際為 600 秒
      When 用戶 "Alice" 進入章節 203 的教室頁面
      Then VidStack 播放器應 fallback 到 0 秒播放
      And 不產生 JavaScript 錯誤

  # ========== 無影片的章節 ==========

  Rule: 無影片的章節不進行 seek

    Example: 純文字章節（type=none）
      When 用戶 "Alice" 進入章節 202 的教室頁面
      Then 頁面不渲染 VidStack 播放器
      And 不進行任何 seek 行為

    Example: code 類型章節不支援自動續播
      When 用戶 "Alice" 進入章節 204 的教室頁面
      Then 使用 code embed 渲染（非 VidStack）
      And 不進行任何 seek 行為

  # ========== 完成章節後的 seek 行為 ==========

  Rule: 已完成章節重新進入時，video_progress_seconds 已被重置為 0

    Example: 已完成章節回看從頭開始
      Given 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在課程 100 的 last_visit_info 為 chapter_id 200，video_progress_seconds 0
      When 用戶 "Alice" 進入章節 200 的教室頁面
      Then VidStack 播放器從 0 秒開始播放

  # ========== 前端節流機制 ==========

  Rule: 播放中每 15 秒回報一次進度

    Example: 影片播放中的定期回報
      Given 用戶 "Alice" 正在章節 200 觀看影片
      When 影片播放至第 45 秒
      Then 前端應已發送 3 次進度回報（15s, 30s, 45s）
      And 每次回報呼叫 POST /power-course/v2/chapters/200/video-progress

  Rule: 暫停、離開頁面時立即回報最後進度

    Example: 暫停影片時回報
      Given 用戶 "Alice" 正在章節 200 觀看影片至 22 秒
      When 用戶 "Alice" 暫停影片
      Then 前端應立即回報 video_progress_seconds 為 22

    Example: 離開頁面時回報（beforeunload）
      Given 用戶 "Alice" 正在章節 200 觀看影片至 48 秒
      When 用戶 "Alice" 關閉或離開頁面
      Then 前端應使用 fetch + keepalive 回報 video_progress_seconds 為 48
