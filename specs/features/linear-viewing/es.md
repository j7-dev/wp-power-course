# Event Storming：課程線性觀看功能

**Issue**: #173
**Date**: 2026-04-03
**Status**: Confirmed

---

## 1. 功能概述

讓管理員可以為每門課程個別開啟「線性觀看」模式，開啟後學員必須按照章節排列順序逐步完成，才能解鎖下一個章節。

## 2. Domain Events

| # | Event | Trigger | Aggregate |
|---|-------|---------|-----------|
| E1 | LinearChapterModeEnabled | 管理員開啟線性觀看 | Course |
| E2 | LinearChapterModeDisabled | 管理員關閉線性觀看 | Course |
| E3 | ChapterUnlocked | 學員完成前一章（線性模式） | ChapterProgress |
| E4 | ChapterAccessBlocked | 學員嘗試存取被鎖定章節 | ChapterProgress |
| E5 | ChapterUncompleteRejected | 線性模式下嘗試取消完成 | ChapterProgress |

## 3. Commands

| # | Command | Actor | Preconditions |
|---|---------|-------|---------------|
| C1 | UpdateLinearChapterMode | Admin | 課程存在、有 manage_woocommerce 權限 |
| C2 | ToggleFinishChapter (修改) | Student | 課程存取權、未到期、章節已解鎖（線性模式）、線性模式下不可取消完成 |
| C3 | AccessChapter (修改) | Student | 課程存取權、未到期、章節已解鎖或管理員（線性模式） |

## 4. Read Models / Queries

| # | Query | Consumer | Data |
|---|-------|----------|------|
| R1 | GetChapterUnlockStatus | ClassroomTemplate / JS | 每個章節的鎖定狀態（locked/unlocked/completed） |
| R2 | GetCourseLinearMode | ClassroomTemplate | 課程是否開啟線性觀看 |
| R3 | GetFirstUnlockedChapter | RedirectLogic | 最新可存取的章節 ID（用於 302 導向） |

## 5. Aggregates

### Course (修改)
- 新增屬性：`linear_chapter_mode` (yes/no, 預設 no)
- 儲存位置：`wp_postmeta` (key: `linear_chapter_mode`)

### ChapterProgress (現有)
- 利用既有 `pc_avl_chaptermeta.finished_at` 判斷解鎖
- 解鎖邏輯：章節攤平後，第 N 個章節需第 N-1 個章節的 `finished_at` 存在
- 第一個章節固定解鎖

## 6. Policies / Business Rules

| # | Rule | Description |
|---|------|-------------|
| P1 | 攤平排序 | 所有章節（含父章節）按 `get_flatten_post_ids()` 順序攤平為線性序列 |
| P2 | 第一章免鎖 | 攤平序列的第一個章節固定為已解鎖 |
| P3 | 嚴格線性 | 第 N 章解鎖條件：第 N-1 章 `finished_at` 存在 |
| P4 | 單向完成 | 線性模式下，toggle 為單向（只能標記完成，不可取消） |
| P5 | 管理員豁免 | `manage_woocommerce` 權限者不受鎖定限制 |
| P6 | 後端強制 | 教室模板渲染前檢查解鎖狀態，被鎖定則 302 導向 |
| P7 | 前端即時 | 完成章節後 JS 即時更新鎖定 UI（無需 reload） |
| P8 | 進度保留 | 關閉線性觀看不影響已記錄的 `finished_at` |

## 7. 資料模型變更

### 新增 postmeta
```
key:     linear_chapter_mode
value:   yes | no
default: no
owner:   Course (WooCommerce Product)
```

### API 回應擴充（toggle-finish-chapters）
```json
{
  "next_chapter_id": 201,        // 新增：下一章 ID（線性模式下）
  "next_chapter_unlocked": true  // 新增：下一章是否已解鎖
}
```

## 8. 影響範圍

### PHP 後端
| 檔案 | 變更 |
|------|------|
| `inc/classes/Resources/Chapter/Core/Api.php` | `toggle-finish-chapters` 增加線性觀看檢查 |
| `inc/templates/single-pc_chapter.php` | 新增章節解鎖檢查 + 302 導向邏輯 |
| `inc/templates/pages/classroom/header.php` | 完成按鈕 + 下一章按鈕條件渲染 |
| `inc/templates/pages/classroom/sider.php` | 章節列表鎖頭圖示渲染 |
| `inc/classes/Resources/Chapter/Utils/Utils.php` | 新增 `is_chapter_unlocked()` / `get_first_unlocked_chapter_id()` 工具方法 |
| `inc/classes/Utils/Course.php` | 新增 `is_linear_chapter_mode()` 工具方法 |
| `inc/classes/Resources/Course/Core/Api.php` | 課程更新 API 支援 `linear_chapter_mode` 參數 |

### 前端（Vanilla TS，教室頁面）
| 檔案 | 變更 |
|------|------|
| `inc/assets/src/events/finishChapter.ts` | 完成後 JS 更新：解鎖下一章圖示、啟用下一章按鈕 |
| `inc/assets/src/store.ts` | atom 新增 `next_chapter_id`、`next_chapter_unlocked` |

### 前端（React SPA，管理後台）
| 檔案 | 變更 |
|------|------|
| 課程編輯頁面 | 新增 `linear_chapter_mode` 開關 |
