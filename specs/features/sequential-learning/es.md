# Event Storming — 課程線性觀看功能

Issue: #171
Version: 1.0
Date: 2026-04-03

---

## 決策摘要

| 項目 | 決策 | 備註 |
|------|------|------|
| 鎖定粒度 | 展平序列全部鎖（DFS 順序） | 複用 `get_flatten_post_ids()`，不區分父子 |
| URL 存取鎖定章節 | 重導到第一個未完成章節 | `wp_safe_redirect()` in `single-pc_chapter.php` |
| 安全性 | PHP + JS 雙重驗證 | PHP 阻擋 URL 直接存取 + JS 處理即時 UI |
| 下一章按鈕 | 禁用（灰色），hover 提示 | `disabled` + tooltip「請先完成本章節」 |
| 取消完成 | 線性觀看開啟時禁止取消 | toggle API 變為單向（僅可完成） |
| 設定位置 | CourseOther 區塊 FiSwitch | meta: `enable_sequential_learning`, 預設 `'no'` |
| 手動解鎖 | v1 不做 | 管理員可透過現有「標記完成」功能替代 |

---

## Aggregates

### Course Aggregate（擴展）
- **新欄位**: `enable_sequential_learning: pc_yes_no` (product meta, default: 'no')
- **Command**: UpdateCourse（既有，新增 `enable_sequential_learning` 參數）

### ChapterProgress Aggregate（擴展）
- **Command**: ToggleFinishChapter（既有，新增限制條件）
  - 前置條件 1（新增）：若 `enable_sequential_learning = 'yes'` 且章節已完成 → 拒絕取消
  - 前置條件 2（新增）：若 `enable_sequential_learning = 'yes'` 且章節為鎖定 → 拒絕完成
  - 後置（新增）：回傳 `next_chapter_unlocked`, `next_chapter_id`, `next_chapter_icon_html`

---

## Commands

### 1. UpdateCourse（擴展）
- **Actor**: 管理員
- **Input**: `{ enable_sequential_learning: 'yes' | 'no' }`
- **Preconditions**: 管理員角色、課程存在
- **Events**: CourseUpdated
- **Side Effects**: 前台立即生效（無快取問題，PHP 每次請求即時讀取 meta）

### 2. ToggleFinishChapter（擴展）
- **Actor**: 學員
- **Input**: `{ chapter_id, course_id }`
- **Preconditions（新增）**:
  - 線性觀看開啟時，已完成章節不可取消（403）
  - 線性觀看開啟時，鎖定章節不可完成（403）
- **Events**: ChapterFinished（已有）
- **Side Effects（新增）**: 回傳下一章解鎖資訊供 JS 即時更新

---

## Read Models

### 3. ChapterLockStatus（新增）
- **Query**: 給定 course_id + user_id，計算所有章節的鎖定狀態
- **邏輯**:
  1. `flatten_ids = get_flatten_post_ids(course_id)`
  2. `finished_map = 批次查詢 pc_avl_chaptermeta WHERE meta_key='finished_at' AND user_id=X`
  3. 遍歷 `flatten_ids`：
     - index 0 → 永遠解鎖
     - index N → `finished_map[flatten_ids[N-1]]` 存在 → 解鎖，否則 → 鎖定
- **消費者**: 
  - PHP: `single-pc_chapter.php`（伺服器端重導）
  - PHP: `classroom/chapters.php`（渲染鎖頭圖示）
  - PHP: `classroom/header.php`（下一章按鈕狀態）

### 4. FirstUncompletedChapter（新增）
- **Query**: 給定 course_id + user_id，找到第一個未完成的章節 ID
- **邏輯**: 遍歷 `flatten_ids`，返回第一個 `finished_at` 為空的 chapter_id
- **消費者**: PHP 重導邏輯

---

## Events

| 事件 | 觸發者 | 新增/既有 |
|------|--------|-----------|
| CourseUpdated | UpdateCourse | 既有 |
| ChapterFinished | ToggleFinishChapter | 既有 |
| ~~ChapterUnfinished~~ | ~~ToggleFinishChapter~~ | 既有但線性觀看時禁用 |

---

## UI 變更

### 管理端（React SPA）

| 元件 | 檔案 | 變更 |
|------|------|------|
| CourseOther | `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 新增 FiSwitch `enable_sequential_learning`，label: 「線性觀看」，tooltip: 「開啟後學員須依序完成章節才能觀看下一章」 |

### 學員端（PHP 模板 + Vanilla JS）

| 元件 | 檔案 | 變更 |
|------|------|------|
| 教室頁面入口 | `inc/templates/single-pc_chapter.php` | 新增鎖定狀態檢查 + 重導邏輯 |
| 章節列表 | `inc/classes/Resources/Chapter/Utils/Utils.php` (`get_children_posts_html_uncached`) | 鎖定章節: 替換圖示為鎖頭 + 加 `data-locked="true"` |
| 章節列表 JS | `inc/templates/pages/classroom/chapters.php` | 鎖定章節點擊時顯示提示、阻止導航 |
| 頁面標頭 | `inc/templates/pages/classroom/header.php` | 完成按鈕: 線性觀看已完成 → 不可點擊「已完成」；下一章按鈕: 鎖定時 disabled + tooltip |
| 完成事件 JS | `inc/assets/src/events/finishChapter.ts` | 完成後更新下一章圖示、移除 `data-locked`、啟用下一章按鈕 |
| 完成按鈕 API | `inc/classes/Resources/Chapter/Core/Api.php` | 新增線性觀看前置條件檢查 + 回傳 next_chapter 資訊 |
| 鎖頭圖示 | `inc/templates/icon/lock.php`（新增） | 鎖頭 SVG 圖示模板 |

---

## 不做（v1 排除）

- 管理員手動為特定學員解鎖特定章節
- 全域設定 + 課程個別覆寫（只做課程級別設定）
- 取消完成的確認對話框（因為直接禁止取消完成）
- 章節間的解鎖動畫效果
