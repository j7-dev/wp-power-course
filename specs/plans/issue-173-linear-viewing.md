# 實作計劃：課程線性觀看功能（Issue #173）

## 概述

為 Power Course 新增「線性觀看」模式，讓管理員可以為每門課程個別開啟此功能。開啟後學員必須按照章節攤平順序逐步完成，才能解鎖下一個章節。涵蓋後端 302 導向鎖定、前端即時解鎖 UI、管理後台設定開關。

## 範圍模式：HOLD SCOPE（維持）

雖然是新功能，但需求已經過完整澄清、規格文件齊備，影響約 12 個檔案，不需要擴展也不需要縮減。

## 需求（來源：specs/features/linear-viewing/）

- 每門課程可獨立開啟/關閉 `linear_chapter_mode`（預設 `no`）
- 所有章節攤平為線性序列（含父章節），依 `get_flatten_post_ids()` 順序
- 第一個章節固定解鎖，第 N 章需第 N-1 章 `finished_at` 存在才解鎖
- 線性模式下 toggle 為單向（只能標記完成，不可取消）
- 前端 + 後端雙重鎖定（後端 302 導向，前端鎖頭圖示 + 提示）
- 管理員（`manage_woocommerce`）不受鎖定限制，但能看到鎖定 UI
- 完成章節後 JS 即時更新鎖定 UI（無需 reload）
- 關閉線性觀看不影響已記錄的 `finished_at`

## 架構變更

### 新增檔案
| 檔案 | 說明 |
|------|------|
| `inc/templates/components/icon/lock.php` | 鎖頭 SVG 圖示模板 |

### 修改檔案
| 檔案 | 說明 |
|------|------|
| `inc/classes/Api/Course.php` | `format_course_records()` 新增 `linear_chapter_mode` 欄位 |
| `inc/classes/Utils/Course.php` | 新增 `is_linear_chapter_mode()` 靜態方法 |
| `inc/classes/Resources/Chapter/Utils/Utils.php` | 新增 `is_chapter_unlocked()` / `get_first_unlocked_chapter_id()` / `get_chapter_lock_icon_html()` 方法 |
| `inc/classes/Resources/Chapter/Core/Api.php` | `toggle-finish-chapters` 增加線性觀看檢查 |
| `inc/templates/single-pc_chapter.php` | 新增章節解鎖檢查 + 302 導向邏輯 |
| `inc/templates/pages/classroom/header.php` | 完成按鈕 + 下一章按鈕條件渲染 |
| `inc/templates/pages/classroom/chapters.php` | 章節列表鎖頭圖示渲染 + JS 點擊鎖定處理 |
| `inc/assets/src/store.ts` | atom 新增 `next_chapter_id`、`next_chapter_unlocked`、`linear_chapter_mode` |
| `inc/assets/src/events/finishChapter.ts` | 完成後 JS 更新：解鎖下一章圖示、啟用下一章按鈕 |
| `js/src/pages/admin/Courses/List/types/index.ts` | `TCourseRecord` 新增 `linear_chapter_mode` 型別 |
| `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 新增 `linear_chapter_mode` 開關 |

## 資料流分析

### 學員進入教室 → 章節解鎖判定

```
學員進入章節 URL
       │
       ▼
single-pc_chapter.php
       │
       ├─ 取得 course_product
       ├─ is_linear_chapter_mode($product_id)?
       │     │
       │     ├─ NO → 正常渲染（現有邏輯）
       │     │
       │     └─ YES → is_admin(manage_woocommerce)?
       │                │
       │                ├─ YES → 正常渲染（管理員豁免）
       │                │
       │                └─ NO → is_chapter_unlocked($chapter_id, $user_id, $course_id)?
       │                          │
       │                          ├─ YES → 正常渲染
       │                          │
       │                          └─ NO → 302 導向至 get_first_unlocked_chapter_id()
       │
       ▼
渲染教室頁面
  ├─ header.php: 完成按鈕 + 下一章按鈕狀態
  ├─ chapters.php: 側邊欄章節列表（鎖頭/打勾/正常圖示）
  └─ body.php: 主內容區
```

### 學員完成章節 → 即時解鎖

```
學員點擊「標示為已完成」
       │
       ▼
finishChapter.ts (AJAX POST)
       │
       ▼
toggle-finish-chapters API
       │
       ├─ 檢查 linear_chapter_mode?
       │     │
       │     └─ YES
       │          ├─ 章節已完成？→ 403 cannotUncomplete「線性觀看模式下無法取消」
       │          ├─ 章節未解鎖？→ 403 chapterLocked「此章節尚未解鎖」
       │          └─ 章節已解鎖且未完成 → 正常完成流程
       │
       ├─ 新增 finished_at
       ├─ 計算 next_chapter_id（下一章的 ID）
       ├─ 計算 next_chapter_unlocked（完成後下一章是否解鎖 = true）
       │
       ▼
回傳 JSON { is_this_chapter_finished, progress, icon_html, next_chapter_id, next_chapter_unlocked }
       │
       ▼
finishChapter.ts (回應處理)
       │
       ├─ 更新當前章節圖示為打勾 ✅
       ├─ 移除下一章鎖頭圖示 🔒 → 正常圖示
       ├─ 下一章 <li> 的 data-locked 移除、恢復 cursor/opacity
       ├─ 啟用「前往下一章節」按鈕（移除 disabled、更新 href）
       └─ 更新進度條百分比
```

### Shadow Paths

| 階段 | nil path | empty path | error path |
|------|----------|------------|------------|
| 取得 linear_chapter_mode | postmeta 不存在 → 預設 `no` | - | - |
| get_flatten_post_ids | 課程無章節 → 空陣列 → 所有判定直接通過 | 陣列為空 → 視同非線性 | - |
| is_chapter_unlocked | user_id 為 0（未登入）→ false，由上層已處理 | chapter_id 不在攤平列表中 → false | - |
| toggle-finish-chapters | product 不存在 → 400 | chapter_ids 為空 → 不做線性檢查 | AVLChapterMeta::add 失敗 → 400 |
| get_first_unlocked_chapter_id | 無已解鎖章節（不可能，第一章固定解鎖）→ 攤平列表第 0 個 | - | - |
| JS 解鎖更新 | next_chapter_id 為 null（最後一章）→ 不執行 DOM 操作 | - | AJAX 失敗 → 不更新 UI，顯示錯誤 dialog |

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `toggle-finish-chapters` (取消完成) | 線性模式下嘗試取消 | 403 `cannotUncomplete` | 回傳錯誤訊息 | 是：「線性觀看模式下無法取消已完成的章節」 |
| `toggle-finish-chapters` (完成鎖定章節) | 章節未解鎖 | 403 `chapterLocked` | 回傳錯誤訊息 | 是：「此章節尚未解鎖，請先完成前面的章節」 |
| `single-pc_chapter.php` (URL 直接存取) | 章節被鎖定 | 302 redirect | 導向至最新可存取章節 | 是：頁面跳轉 |
| JS 點擊鎖定章節 | 前端點擊攔截 | - | 顯示 alert/tooltip 提示 | 是：「請先完成前面的章節後再觀看此章節」 |
| `get_flatten_post_ids` 返回空陣列 | 課程無章節 | - | 視同非線性模式，不做任何鎖定 | 靜默 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|-----------|---------|
| 後端 302 導向 | 攤平列表為空 → 無法計算導向目標 | 需處理 | 需測試 | 靜默（不導向） | 允許存取 |
| toggle API | 並發完成同一章節（重複 click） | 部分處理（已有 finished_at 存在判斷） | 需測試 | 靜默 | 第二次 click 為取消 → 被線性模式阻擋 → 403 |
| JS 即時解鎖 | AJAX 成功但 DOM 節點不存在 | 需處理（Optional chaining） | 需測試 | 靜默 | 頁面重載恢復 |
| 管理員關閉線性模式 | 學員正在教室中 → 鎖定 UI 殘留 | 不處理（下次頁面載入恢復正常） | 不需要 | 可能暫時看到殘留鎖頭 | 重新載入頁面 |

---

## 實作步驟

### 第一階段：後端核心邏輯（無 UI 依賴）

#### 步驟 1.1：新增 `Course::is_linear_chapter_mode()` 工具方法
**檔案**：`inc/classes/Utils/Course.php`
- **行動**：在 class 中新增 `public static function is_linear_chapter_mode( int $product_id ): bool` 方法
- **邏輯**：`return \wc_string_to_bool( (string) \get_post_meta( $product_id, 'linear_chapter_mode', true ) );`
- **原因**：所有後續步驟都會使用此方法判斷線性模式是否開啟，作為核心基礎設施
- **依賴**：無
- **風險**：低

#### 步驟 1.2：新增章節解鎖判定工具方法
**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
- **行動**：新增以下靜態方法：

```php
/**
 * 判斷章節是否已解鎖（線性觀看模式）
 *
 * @param int $chapter_id 章節 ID
 * @param int $user_id 用戶 ID
 * @param int $course_id 課程 ID
 * @return bool
 */
public static function is_chapter_unlocked( int $chapter_id, int $user_id, int $course_id ): bool
```

- **邏輯**：
  1. 取得攤平章節列表 `get_flatten_post_ids($course_id)`
  2. 若列表為空或 `$chapter_id` 不在列表中 → 返回 `true`
  3. 找到 `$chapter_id` 的 index
  4. 若 index === 0（第一章）→ 返回 `true`
  5. 取得前一章 ID：`$prev_id = $chapter_ids[$index - 1]`
  6. 檢查前一章的 `finished_at`：`$prev_chapter = new Chapter($prev_id, $user_id); return (bool) $prev_chapter->finished_at;`

```php
/**
 * 取得用戶在課程中第一個未解鎖的（最新可存取）章節 ID
 * 用於 302 導向目標
 *
 * @param int $user_id 用戶 ID
 * @param int $course_id 課程 ID
 * @return int|null
 */
public static function get_first_unlocked_chapter_id( int $user_id, int $course_id ): ?int
```

- **邏輯**：遍歷攤平列表，找到第一個「前一章已完成但自己未完成」的章節；若全部已完成則返回最後一章
- **原因**：被 single-pc_chapter.php（302 導向）和 toggle-finish-chapters API（驗證）共同使用
- **依賴**：步驟 1.1
- **風險**：中（需確保 `Chapter` model 在無 `finished_at` 時正確返回 null/false）

#### 步驟 1.3：修改 `toggle-finish-chapters` API 加入線性觀看檢查
**檔案**：`inc/classes/Resources/Chapter/Core/Api.php`（`post_toggle_finish_chapters_with_id_callback` 方法，約 line 261）
- **行動**：在現有的 `$is_this_chapter_finished` 判斷之後，加入線性觀看模式的檢查邏輯

在 line 289（`wp_cache_delete` 之後）插入：

```php
// 線性觀看模式檢查
$is_linear = CourseUtils::is_linear_chapter_mode($course_id);

if ($is_linear) {
    // 禁止取消完成
    if ($is_this_chapter_finished) {
        return new \WP_REST_Response(
            [
                'code'    => '403',
                'message' => '線性觀看模式下無法取消已完成的章節',
            ],
            403
        );
    }

    // 檢查章節是否已解鎖
    if (!ChapterUtils::is_chapter_unlocked($chapter_id, $user_id, $course_id)) {
        return new \WP_REST_Response(
            [
                'code'    => '403',
                'message' => '此章節尚未解鎖，請先完成前面的章節',
            ],
            403
        );
    }
}
```

- **行動（續）**：在完成章節的成功回應（line 326-340）中，新增 `next_chapter_id` 和 `next_chapter_unlocked` 欄位：

```php
// 計算下一章資訊（線性模式用）
$next_chapter_id       = ChapterUtils::get_next_post_id($chapter_id);
$next_chapter_unlocked = $next_chapter_id ? true : false; // 完成當前章後，下一章必定解鎖

// 在回應 data 中加入：
'next_chapter_id'          => $next_chapter_id,
'next_chapter_unlocked'    => $next_chapter_unlocked,
'next_chapter_permalink'   => $next_chapter_id ? \get_permalink($next_chapter_id) : null,
```

- **原因**：核心業務邏輯 — 防止線性模式下取消完成、防止完成未解鎖章節、提供前端即時解鎖所需資訊
- **依賴**：步驟 1.1、1.2
- **風險**：中（修改核心 API，需確保非線性模式下行為不變）

#### 步驟 1.4：新增 `linear_chapter_mode` 到課程 API 回應
**檔案**：`inc/classes/Api/Course.php`（`format_course_records()` 方法，約 line 263-315）
- **行動**：在 `$extra_array` 中加入一行：

```php
'linear_chapter_mode'       => (string) $product->get_meta( 'linear_chapter_mode' ) ?: 'no',
```

- **插入位置**：在 `'is_popular'`（line 275）附近，與其他 `yes/no` 開關欄位放在一起
- **原因**：讓管理後台 React SPA 可以讀取和顯示此設定
- **依賴**：無
- **風險**：低

---

### 第二階段：後端模板渲染（302 導向 + 鎖頭 UI）

#### 步驟 2.1：新增鎖頭圖示模板
**檔案**：`inc/templates/components/icon/lock.php`（**新增**）
- **行動**：建立鎖頭 SVG 圖示模板，遵循現有 `icon/check.php`、`icon/video.php` 的模式
- **參數**：`class`（預設 `size-6`）、`color`（預設 `Base::GRAY_COLOR` 或 `#9ca3af`）
- **原因**：側邊欄章節列表中被鎖定章節需要顯示鎖頭圖示
- **依賴**：無
- **風險**：低

#### 步驟 2.2：修改 `single-pc_chapter.php` 加入 302 導向
**檔案**：`inc/templates/single-pc_chapter.php`（約 line 42-64 的權限檢查區塊）
- **行動**：在現有的 `if (!current_user_can('manage_woocommerce'))` 區塊內，在 `if ($is_expired)` 檢查之後（line 58 之後），加入線性觀看鎖定檢查：

```php
// 線性觀看模式：章節鎖定檢查（在 expired 檢查之後）
$product_id = $course_product ? $course_product->get_id() : 0;
if ($product_id && CourseUtils::is_linear_chapter_mode($product_id)) {
    if (!ChapterUtils::is_chapter_unlocked((int) $chapter_post->ID, $current_user_id, $product_id)) {
        $target_chapter_id = ChapterUtils::get_first_unlocked_chapter_id($current_user_id, $product_id);
        if ($target_chapter_id) {
            \wp_safe_redirect(\get_permalink($target_chapter_id));
            exit;
        }
    }
}
```

- **插入位置**：在 line 58（`exit;` after expired check）之後、line 60（`if ('publish' !== $post->post_status)`）之前
- **原因**：後端強制鎖定，防止學員透過 URL 直接存取被鎖定章節
- **依賴**：步驟 1.1、1.2
- **風險**：中（需確保 `$course_product` 不為 null、不影響管理員預覽）

#### 步驟 2.3：修改 `single-pc_chapter.php` 注入前端資料
**檔案**：`inc/templates/single-pc_chapter.php`（約 line 91-110 的 `window.pc_data` 區塊）
- **行動**：在 `window.pc_data` JavaScript 物件中新增線性觀看模式資料：

```php
"linear_chapter_mode": "<?php echo esc_js($course_product ? (string) $course_product->get_meta('linear_chapter_mode') : 'no'); ?>",
"is_admin_preview": <?php echo current_user_can('manage_woocommerce') ? 'true' : 'false'; ?>
```

- **原因**：前端 JS 需要知道線性模式是否開啟、是否為管理員預覽，才能正確處理 UI 行為
- **依賴**：無
- **風險**：低

#### 步驟 2.4：修改 `chapters.php` 側邊欄章節列表
**檔案**：`inc/templates/pages/classroom/chapters.php`（約 line 28-155）

**2.4a：PHP 區塊修改**（line 28-35 附近）
- **行動**：取得線性模式資料並傳遞給章節渲染

在現有變數宣告區（line 29-31）之後加入：

```php
$product_id           = $product->get_id();
$is_linear            = CourseUtils::is_linear_chapter_mode($product_id);
$flatten_chapter_ids  = ChapterUtils::get_flatten_post_ids($product_id);
$current_user_id      = \get_current_user_id();
$is_admin             = \current_user_can('manage_woocommerce');
```

**2.4b：修改 `get_children_posts_html_uncached` 方法**
**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`（line 613）

這是修改量最大的地方。需要在 `get_children_posts_html_uncached` 的 `<li>` 渲染中，根據線性觀看狀態加入鎖頭圖示和 `data-locked` 屬性。

**方案**：不修改 `get_children_posts_html_uncached`（它也被課程商品頁使用），而是在 chapters.php 模板的 JavaScript 區塊中，使用 PHP 注入鎖定資訊，讓 JS 在頁面載入時套用鎖定樣式。

在 `<div id="pc-sider__main-chapters">` 加入 data attribute：

```php
<div id="pc-sider__main-chapters" 
    class="pc-sider-chapters overflow-y-auto lg:ml-0 lg:mr-0" 
    data-ancestor_ids="<?php echo $ancestor_ids_string; ?>"
    data-linear="<?php echo $is_linear ? 'yes' : 'no'; ?>"
    data-locked-ids="<?php echo $is_linear ? esc_attr(json_encode(self::get_locked_chapter_ids($current_user_id, $product_id))) : '[]'; ?>"
    data-is-admin="<?php echo $is_admin ? 'yes' : 'no'; ?>"
>
```

**新增 `get_locked_chapter_ids` 輔助方法**（步驟 1.2 延伸）：
**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`

```php
/**
 * 取得用戶在課程中所有被鎖定的章節 ID 列表
 *
 * @param int $user_id 用戶 ID
 * @param int $course_id 課程 ID
 * @return array<int>
 */
public static function get_locked_chapter_ids( int $user_id, int $course_id ): array
```

- **邏輯**：遍歷攤平列表，收集所有 `is_chapter_unlocked() === false` 的章節 ID
- **原因**：前端需要一次性知道所有鎖定章節，用於初始化 UI 和點擊攔截
- **風險**：低

**2.4c：JavaScript 區塊修改**（line 62-155）
- **行動**：在 `restore_expanded_post_ids()` 之後，新增線性觀看 UI 初始化邏輯：

```javascript
// 線性觀看模式：初始化鎖定 UI
const isLinear = $el.data('linear') === 'yes'
const isAdmin = $el.data('is-admin') === 'yes'
const lockedIds = $el.data('locked-ids') || []

if (isLinear && lockedIds.length > 0) {
    lockedIds.forEach(function(postId) {
        const $li = $el.find(`li[data-post-id="${postId}"]`)
        if ($li.length > 0) {
            $li.attr('data-locked', 'true')
            // 替換圖示為鎖頭
            const $icon = $li.find('.pc-chapter-icon')
            if ($icon.length > 0) {
                $icon.html('<div class="pc-tooltip pc-tooltip-right h-6" data-tip="請先完成前面的章節"><svg>...</svg></div>')
            }
            // 非管理員時禁用點擊
            if (!isAdmin) {
                $li.addClass('opacity-50 cursor-not-allowed')
                $li.removeAttr('data-href')
            }
        }
    })
}
```

- **修改點擊事件**：在現有 `$el.on('click', 'li', ...)` 中（line 72），加入鎖定檢查：

```javascript
// 在 href 跳轉之前檢查
if ($li.attr('data-locked') === 'true' && !isAdmin) {
    alert('請先完成前面的章節後再觀看此章節')
    return
}
```

- **依賴**：步驟 2.1（鎖頭 SVG）
- **風險**：中（需確保不影響非線性模式的章節列表）

#### 步驟 2.5：修改 `header.php` 完成按鈕 + 下一章按鈕
**檔案**：`inc/templates/pages/classroom/header.php`

**2.5a：完成按鈕修改**（line 47-64）
- **行動**：線性模式下，已完成章節的按鈕改為不可點擊的「已完成」狀態

在 line 51（`$is_this_chapter_finished` 賦值）之後加入：

```php
$is_linear = CourseUtils::is_linear_chapter_mode($product_id);
```

修改 `$finish_chapter_button_html`（line 53-64）：

```php
if ($is_linear && $is_this_chapter_finished) {
    // 線性模式下已完成 → 不可點擊的「已完成」按鈕
    $finish_chapter_button_html = sprintf(
        /*html*/'
        <button id="finish-chapter__button" data-course-id="%1$s" data-chapter-id="%2$s" class="pc-btn pc-btn-secondary pc-btn-sm px-0 lg:px-4 w-full lg:w-auto text-xs sm:text-base pc-btn-outline border-solid cursor-not-allowed opacity-70" disabled>
            <span>已完成</span>
        </button>',
        $product_id,
        $current_chapter_id
    );
} else {
    // 原有邏輯保持不變
    $finish_chapter_button_html = sprintf( /* 現有的 sprintf ... */ );
}
```

**2.5b：下一章按鈕修改**（line 67-96）
- **行動**：線性模式下，若當前章節未完成，「前往下一章節」按鈕為禁用狀態

在 line 78-95 的 `$next_chapter_button_html` 邏輯中，加入條件判斷：

```php
if ($is_linear && !$is_this_chapter_finished && false !== $next_chapter_id) {
    // 線性模式下未完成當前章節 → 下一章按鈕禁用
    $next_chapter_button_html = sprintf(
        /*html*/'
        <button id="next-chapter__button" data-next-href="%1$s" class="pc-btn pc-btn-sm pc-btn-primary px-0 lg:px-4 text-white cursor-not-allowed opacity-70 w-full lg:w-auto text-xs sm:text-base pc-tooltip" data-tip="請先完成當前章節" tabindex="-1" role="button" aria-disabled="true" disabled>
            前往下一章節
            <svg class="size-3 sm:size-4" ...>...</svg>
        </button>',
        \get_permalink($next_chapter_id)
    );
} elseif (false !== $next_chapter_id) {
    // 原有邏輯：可點擊的下一章按鈕
    $next_chapter_button_html = sprintf( /* 現有的 <a> ... */ );
}
```

- **關鍵**：禁用按鈕使用 `<button>` 而非 `<a>`，帶有 `data-next-href` 供 JS 完成後啟用時讀取
- **依賴**：步驟 1.1
- **風險**：中（需確保 HTML 結構與 JS 事件正確配合）

---

### 第三階段：前端 JS 即時解鎖

#### 步驟 3.1：擴展 Jotai Store
**檔案**：`inc/assets/src/store.ts`
- **行動**：在 `finishChapterAtom` 中新增欄位：

```typescript
export const finishChapterAtom = atom({
    course_id: undefined,
    chapter_id: undefined,
    isError: false,
    isSuccess: false,
    isLoading: false,
    showDialog: false,
    dialogMessage: '',
    isFinished: undefined,
    progress: undefined,
    icon_html: '',
    // 新增：線性觀看模式
    next_chapter_id: undefined as number | undefined,
    next_chapter_unlocked: undefined as boolean | undefined,
    next_chapter_permalink: undefined as string | undefined,
})
```

- **原因**：AJAX 回應需要攜帶下一章資訊，供 UI 更新邏輯使用
- **依賴**：無
- **風險**：低

#### 步驟 3.2：修改 `finishChapter.ts` 完成後即時解鎖
**檔案**：`inc/assets/src/events/finishChapter.ts`

**3.2a：修改 `store.sub` 回調**（line 10-79）

在現有的 `isSuccess` 處理區塊（line 31-65）中，`isFinished === true` 分支內加入：

```typescript
if (isFinished === true) {
    // 現有邏輯保持...
    FinishButton.removeClass('text-white').addClass('pc-btn-outline border-solid')
        .find('span:first-child').text('標示為未完成')
    // ...badge 更新...

    // === 新增：線性觀看模式 UI 更新 ===
    const isLinear = (window as any).pc_data?.linear_chapter_mode === 'yes'

    if (isLinear) {
        // 1. 禁用完成按鈕（線性模式下不可取消完成）
        FinishButton.prop('disabled', true)
            .addClass('cursor-not-allowed opacity-70')
            .find('span:first-child').text('已完成')

        // 2. 解鎖下一章側邊欄圖示
        const { next_chapter_id, next_chapter_permalink } = store.get(finishChapterAtom)
        if (next_chapter_id) {
            const $nextLi = $(`li[data-post-id="${next_chapter_id}"]`)
            if ($nextLi.length > 0) {
                $nextLi.removeAttr('data-locked')
                    .removeClass('opacity-50 cursor-not-allowed')
                    .attr('data-href', next_chapter_permalink || '')

                // 替換鎖頭圖示為正常播放圖示
                const $nextIcon = $nextLi.find('.pc-chapter-icon')
                if ($nextIcon.length > 0) {
                    // 使用 video icon 的 HTML（server-rendered icon 格式）
                    $nextIcon.html('<div class="pc-tooltip pc-tooltip-right h-6" data-tip="點擊觀看"><svg class="size-6" viewBox="0 0 24 24" ...>...</svg></div>')
                }
            }
        }

        // 3. 啟用「前往下一章節」按鈕
        const NextButton = $('#next-chapter__button')
        if (NextButton.length > 0 && next_chapter_permalink) {
            // 將 <button> 替換為 <a>
            const $newLink = $(`<a href="${next_chapter_permalink}" id="next-chapter__button" class="pc-btn pc-btn-primary pc-btn-sm px-0 lg:px-4 text-white w-full lg:w-auto text-xs sm:text-base">前往下一章節 <svg ...>...</svg></a>`)
            NextButton.replaceWith($newLink)
        }
    }
}
```

**3.2b：修改 AJAX `complete` 回調**（line 118-133）

在 `complete` 回調中，解析新的回應欄位：

```typescript
complete(xhr) {
    const message = xhr?.responseJSON?.message || '發生錯誤，請稍後再試'
    const is_this_chapter_finished = xhr?.responseJSON?.data?.is_this_chapter_finished
    const progress = xhr?.responseJSON?.data?.progress
    const icon_html = xhr?.responseJSON?.data?.icon_html
    // 新增
    const next_chapter_id = xhr?.responseJSON?.data?.next_chapter_id
    const next_chapter_unlocked = xhr?.responseJSON?.data?.next_chapter_unlocked
    const next_chapter_permalink = xhr?.responseJSON?.data?.next_chapter_permalink

    store.set(finishChapterAtom, (prev) => ({
        ...prev,
        isLoading: false,
        showDialog: true,
        dialogMessage: message,
        isFinished: is_this_chapter_finished,
        progress,
        icon_html,
        // 新增
        next_chapter_id,
        next_chapter_unlocked,
        next_chapter_permalink,
    }))
},
```

**3.2c：修改 `FinishButton.on('click')` 點擊事件**（line 82-136）

加入線性模式下的點擊攔截（防止重複 click 觸發取消完成）：

```typescript
FinishButton.on('click', function (e) {
    // 線性模式下，已完成的按鈕不可點擊
    if ($(this).prop('disabled')) {
        return
    }
    // ... 現有邏輯 ...
})
```

- **原因**：核心的前端即時解鎖體驗，學員完成章節後無需重新載入頁面
- **依賴**：步驟 1.3（API 回應擴充）、步驟 2.3（window.pc_data 注入）
- **風險**：高（DOM 操作較複雜，需仔細測試各種場景）

---

### 第四階段：管理後台 React SPA

#### 步驟 4.1：新增 TypeScript 型別
**檔案**：`js/src/pages/admin/Courses/List/types/index.ts`
- **行動**：在 `TCourseRecord` 中（line 66-96）新增：

```typescript
linear_chapter_mode: 'yes' | 'no' | ''
```

- **插入位置**：在 `is_featured` 之後（約 line 80）
- **依賴**：無
- **風險**：低

#### 步驟 4.2：新增管理後台開關
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`
- **行動**：在 `<Heading>課程介紹區域</Heading>` 之前（line 39 之前），新增一個獨立的區塊：

```tsx
<Heading>教室設定</Heading>
<div className="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-3 gap-6">
    <FiSwitch
        formItemProps={{
            name: ['linear_chapter_mode'],
            label: (
                <div className="flex gap-x-2">
                    <span className="bg-blue-100 text-blue-600 text-xs flex items-center px-2 py-1 rounded-md size-fit mb-1">
                        🔒 線性觀看
                    </span>
                    <span>開啟後學員必須按順序完成章節</span>
                </div>
            ),
            tooltip: '開啟後，學員必須按照章節順序逐步完成才能觀看下一章節。第一個章節預設為解鎖狀態。',
        }}
    />
</div>
```

- **原因**：管理員需要一個直覺的開關來控制線性觀看功能
- **依賴**：步驟 1.4（API 回應包含此欄位）、步驟 4.1（型別定義）
- **風險**：低（遵循現有 FiSwitch 模式，後端 meta 儲存已自動處理）

---

## 測試策略

### PHP Integration Test

> 測試 `is_chapter_unlocked()`、`get_first_unlocked_chapter_id()`、`get_locked_chapter_ids()` 的核心邏輯

1. **測試 `is_chapter_unlocked`**：
   - 第一個章節固定解鎖
   - 前一章已完成 → 當前章解鎖
   - 前一章未完成 → 當前章鎖定
   - 跳躍式完成（1,3 完成，2 未完成）→ 3 被鎖定
   - 攤平列表為空 → 返回 true
   - 非線性模式 → 不呼叫此方法（上層判斷）

2. **測試 `toggle-finish-chapters` API（線性模式）**：
   - 完成已解鎖章節 → 200 成功 + `next_chapter_id` 正確
   - 嘗試完成被鎖定章節 → 403 `chapterLocked`
   - 嘗試取消已完成章節 → 403 `cannotUncomplete`
   - 完成最後一章 → 200 成功 + `next_chapter_id` 為 null
   - 非線性模式下取消完成 → 200 成功（現有行為不變）

3. **測試 `is_linear_chapter_mode`**：
   - postmeta 為 `yes` → true
   - postmeta 為 `no` → false
   - postmeta 不存在 → false

### E2E Test（Playwright）

> 測試前端 UI 的即時解鎖效果和後端 302 導向

1. **管理員開啟線性觀看**：
   - 進入課程編輯頁 → 找到「教室設定」→ 開啟「線性觀看」開關 → 儲存 → 重載確認設定保留

2. **學員教室頁面**：
   - 新學員進入教室 → 第一章可點擊 → 後續章節顯示鎖頭圖示
   - 點擊鎖定章節 → 出現提示訊息 → 頁面不跳轉
   - 完成第一章 → 第二章鎖頭消失 → 「前往下一章節」按鈕啟用
   - 完成按鈕變為不可點擊的「已完成」

3. **後端 302 導向**：
   - 直接輸入 URL 存取被鎖定章節 → 被導向至第一個可存取章節
   - 管理員直接存取被鎖定章節 → 正常顯示

4. **管理員關閉線性觀看**：
   - 關閉設定 → 學員教室頁面所有章節恢復可點擊 → 無鎖頭圖示

### 測試執行指令

```bash
composer run test                      # PHP Integration Test
pnpm run test:e2e:frontend            # 前台 E2E
pnpm run test:e2e:admin               # 管理端 E2E
pnpm run test:e2e:integration         # 整合 E2E
```

### 關鍵邊界情況

- 課程無章節（攤平列表為空）→ 線性模式無效果
- 只有一個章節 → 該章節永遠解鎖
- 所有章節都已完成 → 所有章節都可存取
- 管理員開啟線性模式時，學員正在教室中 → 下次頁面載入才生效
- 並發完成（快速雙擊按鈕）→ 第二次請求被 `isLoading` 攔截 + 後端冪等性

---

## 風險與緩解措施

- **風險**：修改 `toggle-finish-chapters` API 可能影響現有完成/取消完成行為
  - 緩解措施：所有線性模式邏輯都包在 `if ($is_linear)` 條件中，非線性模式完全不受影響。加入 Integration Test 確保現有行為不變。

- **風險**：`get_children_posts_html_uncached` 有 transient cache，可能導致鎖頭圖示不更新
  - 緩解措施：不修改此方法。改用 JavaScript 在頁面載入時根據 `data-locked-ids` 動態套用鎖定樣式，繞過 cache 問題。

- **風險**：前端 DOM 操作（jQuery）的脆弱性，DOM 結構變更可能導致選擇器失效
  - 緩解措施：使用 `data-post-id` 和 `data-locked` 等 data attribute 選擇器而非 class 選擇器，減少 CSS 變更的影響。E2E 測試覆蓋關鍵互動。

- **風險**：效能影響 — `is_chapter_unlocked` 需要查詢多個章節的 `finished_at`
  - 緩解措施：`get_locked_chapter_ids` 一次性批量查詢，而非每個章節獨立查詢。利用 WordPress object cache。教室頁面只有在進入時查一次。

- **風險**：TypeScript 編譯錯誤 — `window.pc_data` 類型擴展
  - 緩解措施：使用 `(window as any).pc_data` 已有先例（見現有 `finishChapter.ts` line 100），保持一致。

---

## 成功標準

- [ ] 管理員可在課程編輯頁面開啟/關閉「線性觀看」開關
- [ ] 開關預設為關閉，儲存後重載保持設定
- [ ] 開啟後第一個章節可自由存取，後續章節被鎖定
- [ ] 學員完成章節後，下一章即時解鎖（無需重載）
- [ ] 「前往下一章節」按鈕在未完成時禁用，完成後即時啟用
- [ ] 線性模式下「完成」為單向操作，不可取消
- [ ] 直接輸入 URL 存取被鎖定章節 → 302 導向至最新可存取章節
- [ ] 管理員不受鎖定限制，但能看到鎖定 UI
- [ ] 關閉線性觀看後所有章節恢復自由觀看
- [ ] 現有非線性課程的行為完全不受影響
- [ ] `pnpm run lint:php` 通過
- [ ] `pnpm run lint:ts` 通過
- [ ] `pnpm run build` 通過
- [ ] E2E 測試通過
