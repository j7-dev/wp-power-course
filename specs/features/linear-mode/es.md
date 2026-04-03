# Event Storming — 課程線性觀看模式

## Domain Events

| # | Event | Aggregate | Trigger |
|---|-------|-----------|---------|
| E1 | LinearModeEnabled | Course | Command: EnableLinearMode |
| E2 | LinearModeDisabled | Course | Command: DisableLinearMode |
| E3 | ChapterLocked | Chapter | Policy: LinearModePolicy |
| E4 | ChapterUnlocked | Chapter | Policy: LinearModePolicy |
| E5 | ChapterAccessBlocked | Chapter | Policy: LinearModePolicy |
| E6 | ChapterAccessRedirected | Chapter | Policy: LinearModePolicy |

## Commands

| # | Command | Actor | Aggregate | Input |
|---|---------|-------|-----------|-------|
| C1 | EnableLinearMode | Admin | Course | course_id |
| C2 | DisableLinearMode | Admin | Course | course_id |
| C3 | AccessChapter | Student | Chapter | chapter_id (URL) |
| C4 | ToggleFinishChapter (existing) | Student | Chapter | chapter_id, course_id |

## Read Models

| # | Read Model | Data |
|---|------------|------|
| R1 | ChapterUnlockStatus | course_id, user_id → unlocked_chapter_ids[] |
| R2 | CurrentLearningPosition | course_id, user_id → next_chapter_id |

## Policies

| # | Policy | Trigger Event | Resulting Command/Event |
|---|--------|---------------|------------------------|
| P1 | LinearModePolicy | ChapterFinished / ChapterUnfinished | Recalculate ChapterUnlockStatus |
| P2 | LinearModeAccessPolicy | AccessChapter | Block or Allow based on ChapterUnlockStatus |

## Aggregates

| Aggregate | Key Fields |
|-----------|-----------|
| Course | id, enable_linear_mode (meta, yes/no, default: no) |
| Chapter | id, course_id, finished_at (per user), menu_order |

## Actors

| Actor | Description |
|-------|-------------|
| Admin | 擁有 manage_woocommerce 權限的用戶，不受線性觀看限制 |
| Student | 已購買課程的學員，受線性觀看規則約束 |

## 解鎖演算法

```
function get_unlocked_chapter_ids(course_id, user_id):
    if !is_linear_mode_enabled(course_id):
        return all_flatten_chapter_ids

    if user_can('manage_woocommerce'):
        return all_flatten_chapter_ids

    flatten_ids = get_flatten_post_ids(course_id)
    unlocked = []

    for i, chapter_id in enumerate(flatten_ids):
        if i == 0:
            unlocked.append(chapter_id)  # 第一個永遠解鎖
            continue

        prev_chapter_id = flatten_ids[i - 1]
        if is_finished(prev_chapter_id, user_id):
            unlocked.append(chapter_id)
        else:
            break  # 一旦遇到前一個未完成，後面全部鎖定

    return unlocked
```

## 系統邊界

### 改動的現有元件
1. **toggle-finish-chapters API** (`Chapter/Core/Api.php`): 新增 `unlocked_chapter_ids` 到回應 data
2. **single-pc_chapter.php**: 新增線性模式存取檢查與重導邏輯
3. **chapters.php**: sidebar 章節列表新增鎖定 UI
4. **header.php**: 「前往下一章節」按鈕新增 disabled 狀態
5. **finishChapter.ts**: 新增解鎖 DOM 更新邏輯
6. **CourseOther/index.tsx**: 新增 `enable_linear_mode` 開關
7. **Course API** (`Api/Course.php`): 新增 `enable_linear_mode` meta 的儲存與讀取
8. **get_chapter_icon_html()** (`Chapter/Utils/Utils.php`): 新增鎖頭圖示邏輯

### 新增的元件
1. **LinearMode utility** (`Chapter/Utils/LinearMode.php`): 解鎖演算法、解鎖狀態計算
2. **鎖頭 icon template** (`templates/icon/lock.php`): SVG 鎖頭圖示
3. **線性模式 CSS**: `.pc-locked` 樣式（鎖定外觀、disabled 互動）

## 決策記錄

| # | 決策 | 選項 | 理由 |
|---|------|------|------|
| D1 | 鎖定粒度 | 扁平化順序，頂層也參與 | 用戶明確要求 1-2 完成才能存取第二章（頂層） |
| D2 | 提示文字 | 通用提示「請先完成前面的章節」 | 實作成本低，避免長章節名排版問題 |
| D3 | 下一章按鈕 | 未完成時 disabled | 禁用但可見，比隱藏更直覺 |
| D4 | 管理員限制 | 不受限 | 與現有 manage_woocommerce 跳過機制一致 |
| D5 | Meta key | `enable_linear_mode` | 用戶指定的命名 |
| D6 | 解鎖策略 | API 回傳 unlocked_chapter_ids | 後端計算更可靠，避免前後端邏輯不一致 |
| D7 | URL 直接存取 | 重導到正確章節 + toast 提示 | 最友善的體驗 |
