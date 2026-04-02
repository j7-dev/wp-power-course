# 課程線性觀看功能 — Event Storming 規格

## 功能摘要

課程級別的「線性觀看」設定（`enable_sequential`），強制學員按照章節排序順序（`menu_order`）依序完成學習。

## 確認的設計決策

| # | 問題 | 決策 | 說明 |
|---|------|------|------|
| Q1 | 父章節是否需要完成 | **B: 需要** | 父章節（depth 0）也在順序中，必須完成才能進入子章節。順序為：第一章→1-1→1-2→第二章→2-1 |
| Q2 | 直接 URL 存取鎖定章節 | **A: 導向正確章節** | 導向到目前應觀看的章節（最後完成章節的下一個），並顯示提示訊息 |
| Q3 | 完成後即時解鎖方式 | **B: 前端狀態更新** | API 回傳 `next_unlocked_chapter_id`，前端 JS 直接更新 DOM |
| Q4 | 全域預設設定 | **A: 不需要** | 只有每門課程個別設定，預設為 `no` |
| Q5 | 設定位置 | **A: 其他設定** | 放在課程編輯頁的「其他設定」分頁（CourseOther） |
| Q6 | 教室頁面提示橫幅 | **C: 不需要** | 鎖頭圖示已夠直覺，不需要額外文字橫幅 |
| Q7 | 單層章節支援 | **A: 支援** | 無父子結構的課程也能使用線性觀看，依 `menu_order` 排序 |

## 資料模型變更

### 新增欄位

| Table | 欄位 | 類型 | 預設值 | 說明 |
|-------|------|------|--------|------|
| courses | `enable_sequential` | pc_yes_no | `'no'` | postmeta: `enable_sequential`，課程級線性觀看開關 |

### API 回傳變更

| API 端點 | 變更 | 說明 |
|----------|------|------|
| `POST /progress/toggle/{chapter_id}` | 新增 `next_unlocked_chapter_id` 欄位 | 僅在 `enable_sequential=yes` 且操作為「完成」時回傳，值為下一個解鎖章節 ID 或 null |
| `GET /courses/{id}` | `enable_sequential` 在回傳中 | 課程詳情包含此欄位 |
| `POST /courses/{id}` | 接受 `enable_sequential` 參數 | 更新課程設定時可設定此欄位 |

## 章節排序順序計算（Flatten Order）

線性觀看的解鎖順序依照「深度優先遍歷」的扁平化順序：

```
課程
├── 第一章 (depth 0, menu_order 1)     → 順序 1
│   ├── 1-1 (depth 1, menu_order 1)    → 順序 2
│   └── 1-2 (depth 1, menu_order 2)    → 順序 3
├── 第二章 (depth 0, menu_order 2)     → 順序 4
│   ├── 2-1 (depth 1, menu_order 1)    → 順序 5
│   └── 2-2 (depth 1, menu_order 2)    → 順序 6
```

現有的 `ChapterUtils::get_flatten_post_ids($course_id)` 已實作此邏輯。

## 存取控制規則

### 判斷章節是否可存取

```
function is_chapter_accessible(chapter_id, user_id, course_id):
  course = get_course(course_id)
  if course.enable_sequential != 'yes':
    return true  // 線性觀看未開啟，所有章節可存取

  flatten_ids = get_flatten_post_ids(course_id)
  chapter_index = flatten_ids.indexOf(chapter_id)

  if chapter_index == 0:
    return true  // 第一個章節永遠可存取

  previous_chapter_id = flatten_ids[chapter_index - 1]
  return has_finished(previous_chapter_id, user_id)
```

### 判斷導向目標章節

```
function get_current_progress_chapter(user_id, course_id):
  flatten_ids = get_flatten_post_ids(course_id)
  for each chapter_id in flatten_ids:
    if not has_finished(chapter_id, user_id):
      return chapter_id
  return flatten_ids.last()  // 全部完成，回到最後一個
```

## 前端行為

### 教室側邊欄（PHP 渲染 + JS 強化）

1. **鎖頭圖示**：未解鎖章節的 `<li>` 加上 `.pc-locked` CSS class，顯示 🔒 圖示
2. **禁止點擊**：`.pc-locked` 章節的 `<a>` 標籤加上 `pointer-events: none` 或 JS 攔截
3. **點擊提示**：點擊鎖定章節時顯示 toast/alert：「請先完成前面的章節，才能觀看此章節喔！」

### 完成章節後即時更新

1. 完成 API 回傳 `next_unlocked_chapter_id`
2. 前端 JS 找到對應 `[data-post-id="{id}"]` 的元素
3. 移除 `.pc-locked` class，移除鎖頭圖示，啟用點擊

### 取消完成確認

1. 在線性觀看模式下，點擊已完成章節的「完成」按鈕
2. 彈出確認對話框：「取消完成此章節後，後續章節將重新鎖定，確定要繼續嗎？」
3. 用戶確認後才發送 API 請求
4. API 回傳後，重新計算鎖定狀態，更新 DOM

## 後台管理（React Admin SPA）

### CourseOther 分頁

在「其他設定」分頁（`CourseOther/index.tsx`）新增一組設定：

```
<Heading>學習設定</Heading>
<FiSwitch
  formItemProps={{
    name: ['enable_sequential'],
    label: '線性觀看（按順序學習）',
    tooltip: '開啟後，學員必須完成前一個章節才能進入下一個章節',
  }}
/>
```

## 規格文件清單

| 檔案 | 類型 | 說明 |
|------|------|------|
| `features/linear-viewing/設定課程線性觀看.feature` | Command | 管理員設定線性觀看開關 |
| `features/linear-viewing/線性觀看存取控制.feature` | Query | 學員存取章節的權限判斷 |
| `features/linear-viewing/線性觀看取消完成連鎖.feature` | Command | 完成/取消完成的連鎖效應 |
| `entity/erm.dbml` | Entity | 新增 `enable_sequential` 欄位 |
| `activities/學員學習旅程.activity` | Activity | 更新學習旅程流程 |
| `specs/ui/教室頁面.md` | UI | 更新教室頁面描述 |
