# Issue #145: 課程章節線性觀看 — 需求澄清紀錄

## 需求摘要

管理員可針對每門課程個別設定是否啟用「線性觀看」模式。啟用後，學員必須按照章節排列順序依序完成，才能解鎖觀看下一個章節。

## 澄清結果

| # | 問題 | 答案 | 說明 |
|---|------|------|------|
| Q1 | 父章節是否納入線性序列 | **B: 是** | 父章節也必須標記完成才能解鎖下一章 |
| Q2 | 取消完成後已完成的後續章節 | **A: 仍可觀看** | `finished_at` 紀錄保留，已完成=已解鎖 |
| Q3 | 鎖定章節的前台 UI | **A: 不可點擊** | 灰色、cursor:not-allowed、鎖頭圖示 |
| Q4 | API 是否回傳鎖定狀態 | **B: 是** | `is_locked` 欄位加入 chapter 回應 |
| Q5 | 重排序後解鎖狀態 | **B: 已完成不受影響** | 已完成章節維持解鎖，未完成依新順序 |
| Q6 | URL 直接存取的阻擋方式 | **A: 302 重導向** | 導到下一個應完成的章節 |
| Q7 | Meta key 命名 | **C: enable_linear_viewing** | 符合 `enable_` 前綴慣例 |

## 關鍵設計決策

### 鎖定判定邏輯（偽碼）

```
flat_chapters = get_flatten_post_ids(course_id)  // 扁平化序列含父章節
for i, chapter in flat_chapters:
    if i == 0:
        is_locked = false  // 第一個永遠解鎖
    elif chapter.finished_at:
        is_locked = false  // 已完成=已解鎖
    elif flat_chapters[i-1].finished_at:
        is_locked = false  // 前一章已完成，解鎖
    else:
        is_locked = true   // 鎖定
```

### 影響的現有元件

| 元件 | 檔案 | 修改項目 |
|------|------|---------|
| Course API | `inc/classes/Api/Course.php` | 新增 `enable_linear_viewing` meta 讀寫 |
| Chapter Utils | `inc/classes/Resources/Chapter/Utils/Utils.php` | 新增 `is_chapter_locked()` 方法 |
| Chapter HTML | `get_children_posts_html_uncached()` | 鎖定章節的 HTML 樣式 |
| Chapter API | `inc/classes/Resources/Chapter/Core/Api.php` | GET 回應加 `is_locked` |
| Toggle Finish API | `post_toggle_finish_chapters_with_id_callback()` | 回應加鎖定變更資訊 |
| Chapter LifeCycle | `inc/classes/Resources/Chapter/Core/LifeCycle.php` | 302 重導向邏輯 |
| Classroom Template | `inc/templates/pages/classroom/chapters.php` | 鎖定 UI + 提示訊息 |
| Course Settings UI | `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 新增 FiSwitch |

### 新增的 Product Meta

| Meta Key | 型別 | 預設值 | 說明 |
|----------|------|--------|------|
| `enable_linear_viewing` | `yes`/`no` | `no` | 啟用線性觀看模式 |

### 新增的 API 回應欄位

| 端點 | 欄位 | 型別 | 說明 |
|------|------|------|------|
| GET /chapters | `is_locked` | boolean | 章節是否被鎖定 |
| POST /toggle-finish-chapters/{id} | `next_unlocked_chapter_id` | int\|null | 下一個解鎖的章節 |
| POST /toggle-finish-chapters/{id} | `locked_chapter_ids` | int[] | 重新鎖定的章節列表 |

## 技術依賴

無新增 library，全部使用現有技術棧實作。
