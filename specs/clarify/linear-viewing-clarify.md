# 課程章節線性觀看 — 需求澄清記錄

Issue: #145

## 澄清結果摘要

| # | 議題 | 決策 | 詳細說明 |
|---|------|------|---------|
| Q1 | 父章節（分類標題）是否納入線性序列？ | **B — 納入** | 父章節也是線性序列的一部分，學員必須連父章節也標記完成才能進入下一個章節 |
| Q2 | 取消完成後已完成的後續章節如何處理？ | **A — 保留** | 已完成的章節仍可觀看（finished_at 紀錄保留），不做連鎖清除。「已完成 = 已解鎖」 |
| Q3 | 鎖定章節的前台 UI 體驗 | **A — 不可點擊** | 灰色 + cursor:not-allowed + 鎖頭圖示 + tooltip 提示文字 |
| Q4 | 後端 API 是否回傳鎖定狀態 | **B — API 也回傳** | GET /chapters 和 toggle-finish-chapters 回應增加 `is_locked` 欄位 |
| Q5 | 啟用後重排章節的行為 | **B — 已完成不受影響** | 已完成的章節維持解鎖，只有未完成章節依新順序判定 |
| Q6 | URL 直接存取鎖定章節的阻擋 | **A — 302 重導向** | 重導向到下一個應完成的章節頁面，帶 `?pc_locked=1` 顯示提示 |
| Q7 | product meta key 命名 | **C — enable_linear_viewing** | 符合 `enable_` 前綴慣例（如 enable_comment） |

## 解鎖邏輯（核心演算法）

```
function is_chapter_locked(chapter_id, course_id, user_id):
  // 未啟用線性觀看 → 不鎖定
  if course.enable_linear_viewing != "yes": return false

  // 管理員不受限
  if user has manage_woocommerce: return false

  // 取得扁平化章節序列
  flat_ids = get_flatten_post_ids(course_id)

  // 第一個章節永遠解鎖
  if chapter_id == flat_ids[0]: return false

  // 已完成的章節永遠解鎖
  if has_finished_at(chapter_id, user_id): return false

  // 前一個章節已完成 → 解鎖
  index = flat_ids.indexOf(chapter_id)
  prev_id = flat_ids[index - 1]
  if has_finished_at(prev_id, user_id): return false

  // 其他情況 → 鎖定
  return true
```

## 影響範圍

### PHP 後端
- `inc/classes/Resources/Chapter/Utils/Utils.php` — 新增鎖定判斷工具方法
- `inc/classes/Resources/Chapter/Core/Api.php` — GET chapters 回傳增加 is_locked
- `inc/classes/Resources/Chapter/Core/Api.php` — toggle-finish 增加鎖定前置檢查
- `inc/templates/pages/classroom/chapters.php` — 章節列表 HTML 加入鎖定樣式
- `inc/templates/pages/classroom/header.php` — 前往下一章節按鈕在鎖定時停用
- `inc/templates/pages/classroom/body.php` — 載入前檢查鎖定並重導向
- `inc/classes/Api/Course.php` — 更新課程 API 接受 enable_linear_viewing

### 前台 JS
- `inc/assets/src/events/finishChapter.ts` — 完成後更新側邊欄鎖定狀態

### 管理後台 (React)
- `js/src/pages/admin/Courses/` — 課程設定頁面新增 enable_linear_viewing 開關

### 規格文件
- `specs/features/linear-viewing/` — 5 個 Feature 檔案
- `specs/api/linear-viewing-api.yml` — API 變更說明
- `specs/entity/erm.dbml` — courses 表新增 enable_linear_viewing 欄位
- `specs/activities/線性觀看學習流程.activity` — 活動流程圖
