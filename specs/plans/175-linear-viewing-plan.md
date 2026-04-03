# 實作計劃：課程線性觀看功能 (Issue #175)

## 概述

為 Power Course 新增「線性觀看」功能，讓管理員可以針對個別課程開啟強制順序觀看模式。開啟後，學員必須依照章節排列順序完成前一個章節才能觀看下一個章節。所有章節平攤為一維順序，以最遠已完成章節位置為解鎖基準。功能包含後端驗證、前端即時解鎖、鎖定 UI 提示、與管理後台開關。

## 範圍模式：HOLD SCOPE

本次是明確的新功能需求，但規格已由 clarifier 完整定義、邊界情況已釐清。影響約 12 個檔案（後端 5 + 前端模板 4 + 前端 JS 2 + Admin SPA 1），在 15 檔案限制內。

## 需求

- 管理員可在每門課程設定中開啟/關閉「線性觀看」（product meta `enable_linear_viewing`，預設 `'no'`）
- 開啟後，學員依章節平攤順序（menu_order）觀看，以「最遠進度模式」計算解鎖
- 鎖定章節在側邊欄顯示鎖定圖示與指名提示（「請先完成『{章節名稱}』才能觀看本章節」）
- 完成/取消完成章節後前端即時更新解鎖狀態（不重新載入頁面）
- 底部「下一個」按鈕在下一章鎖定時顯示但禁用；影片播完不自動跳轉
- 直接 URL 存取鎖定章節 → redirect 到當前應觀看章節 + toast 提示
- toggle-finish API 驗證：線性觀看模式下不允許完成被鎖定的章節
- 管理員預覽模式免除所有限制

## 架構變更

| # | 檔案路徑 | 變更類型 | 說明 |
|---|---------|----------|------|
| 1 | `inc/classes/Utils/LinearViewing.php` | **新增** | 線性觀看核心演算法（獨立 Utility class） |
| 2 | `inc/classes/Resources/Chapter/Core/Api.php` | 修改 | toggle API 新增線性觀看前置驗證 + 回應加入 `unlocked_chapter_ids` |
| 3 | `inc/classes/Api/Course.php` | 修改 | 課程 API 新增 `enable_linear_viewing` meta 讀寫 |
| 4 | `inc/templates/single-pc_chapter.php` | 修改 | 章節頁面新增線性觀看鎖定 redirect 邏輯 |
| 5 | `inc/templates/pages/classroom/chapters.php` | 修改 | 側邊欄章節列表新增鎖定狀態渲染 |
| 6 | `inc/templates/pages/classroom/header.php` | 修改 | 「前往下一章節」按鈕新增鎖定禁用邏輯 |
| 7 | `inc/templates/pages/classroom/body.php` | 修改 | 影片 `next_post_url` 在下一章鎖定時清空 |
| 8 | `inc/templates/components/related-posts/prev-next.php` | 修改 | 底部「下一個」鎖定時顯示禁用狀態 |
| 9 | `inc/assets/src/store.ts` | 修改 | `finishChapterAtom` 新增 `unlocked_chapter_ids` 欄位 |
| 10 | `inc/assets/src/events/finishChapter.ts` | 修改 | 完成章節後即時更新側邊欄鎖定狀態 + 下一章按鈕 |
| 11 | `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 修改 | 新增「啟用線性觀看」FiSwitch 開關 |

## 資料流分析

### 解鎖演算法（核心）

```
INPUT: course_id, user_id
  │
  ▼
GET enable_linear_viewing ──▶ 'no'? ──▶ 全部解鎖 (early return)
  │ 'yes'
  ▼
IS admin_preview? ──▶ true? ──▶ 全部解鎖 (early return)
  │ false
  ▼
GET flat_chapters[] ──▶ empty? ──▶ 回傳空陣列 (early return)
  │ (按 menu_order 排序)
  ▼
GET finished_chapter_ids[] ──▶ empty? ──▶ max_pos = -1
  │ (有完成紀錄)                           │
  ▼                                        ▼
FIND max_pos = max index of ──────▶ unlocked = [0..min(max_pos+1, last_pos)]
  finished chapters in flat list    locked = [max_pos+2..last_pos]
  │                                        │
  ▼                                        ▼
CALC current_chapter_id ────────▶ first unlocked && not finished
  │                                (null if all finished)
  ▼
BUILD locked_hints{} ──────────▶ key=locked_chapter_id
  │                               value={prerequisite_id, title, message}
  ▼
OUTPUT: {
  enabled: true,
  unlocked_chapter_ids: int[],
  current_chapter_id: int|null,
  locked_hints: {[chapter_id]: {prerequisite_chapter_id, prerequisite_chapter_title, message}}
}
```

### 章節完成切換流程（含線性觀看）

```
學員點擊「完成章節」
  │
  ▼
POST /toggle-finish-chapters/{id}
  │
  ▼
[前置驗證]
  ├── user_logged_in? ──▶ no → 403 "必須登入"
  ├── is_avl? ──▶ no → 403 "無此課程存取權"
  ├── is_expired? ──▶ yes → 403 "課程存取已到期"
  ├── chapter_exists? ──▶ no → 404 "章節不存在"
  └── [NEW] linear_viewing + 嘗試完成(非取消) + chapter_locked?
        ──▶ yes → 403 "該章節尚未解鎖，請先完成前面的章節"
  │
  ▼
[切換狀態]
  ├── 已完成 → 刪除 finished_at → do_action(UNFINISHED)
  └── 未完成 → 新增 finished_at → do_action(FINISHED)
  │
  ▼
[計算回應]
  ├── progress = CourseUtils::get_course_progress()
  ├── [NEW] unlocked_chapter_ids = LinearViewing::get_unlocked_chapter_ids() (若 linear enabled)
  └── icon_html = ChapterUtils::get_chapter_icon_html()
  │
  ▼
RESPONSE → 前端 JS → 即時更新側邊欄 + 下一章按鈕
```

### 直接 URL 存取鎖定章節流程

```
學員透過 URL 進入章節頁面
  │
  ▼
single-pc_chapter.php
  │
  ▼
[既有驗證]
  ├── logged_in? → no → redirect login
  ├── is_avl? → no → 404/buy
  ├── is_ready? → no → 404/not-ready
  └── is_expired? → yes → 404/expired
  │
  ▼
[NEW: 線性觀看驗證]
  ├── enable_linear_viewing = 'no'? → 跳過
  ├── is_admin_preview? → 跳過
  ├── chapter in unlocked_ids? → 正常顯示
  └── chapter locked? → redirect to current_chapter_id
        + set transient flash message
        → 目標頁面讀取 flash message 顯示 toast
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `LinearViewing::get_unlock_state()` | course_id 無效 / product 不存在 | InvalidArgument | 回傳全部解鎖（防禦性） | 靜默 |
| `LinearViewing::get_unlock_state()` | flat_chapters 為空 | EmptyCollection | 回傳空陣列 | 靜默 |
| `LinearViewing::get_unlock_state()` | user 無完成紀錄 | NilPath | max_pos = -1，僅解鎖第一章 | 透過 UI |
| `toggle API` 線性觀看驗證 | 嘗試完成被鎖定章節 | BusinessRule | 403 + 錯誤訊息 | 是，前端 dialog |
| `single-pc_chapter.php` redirect | 無 current_chapter_id（所有章節已完成） | EdgeCase | 不 redirect（全部解鎖） | 靜默 |
| `single-pc_chapter.php` redirect | redirect 目標章節也不存在 | DataIntegrity | fallback 到課程首頁 | redirect |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|-----------|---------|
| 解鎖演算法 | 章節排序變更後解鎖位置不對 | ✅ 每次查詢即時計算 | 需測試 | 是 | 自動修正 |
| toggle API | 並發請求導致 race condition | ✅ DB 層 add/delete 是原子操作 | — | 靜默 | 重試 |
| 前端即時更新 | AJAX 失敗時鎖定狀態未更新 | ✅ error handler 已存在 | 需測試 | 錯誤 dialog | 重新整理 |
| URL redirect | 無限 redirect loop | ✅ current_chapter_id 為 null 時不 redirect | 需測試 | 靜默 | — |
| product meta | meta 值為非預期值（非 yes/no） | ✅ 預設 'no'，用 `?: 'no'` | — | 靜默 | — |

## 實作步驟

### 第一階段：後端核心演算法

> 此階段建立線性觀看的核心邏輯，不涉及前端變更。完成後可獨立透過 API 測試。

#### 步驟 1.1：新增 `LinearViewing` Utility Class

**檔案**：`inc/classes/Utils/LinearViewing.php`（新建）

**行動**：
- 建立 `J7\PowerCourse\Utils\LinearViewing` final class
- 實作核心方法 `get_unlock_state(int $course_id, ?int $user_id = null): array`
  - 回傳結構：`['enabled' => bool, 'unlocked_chapter_ids' => int[], 'current_chapter_id' => int|null, 'locked_hints' => array]`
- 實作輔助方法：
  - `is_enabled(int $course_id): bool` — 讀取 product meta `enable_linear_viewing`
  - `is_chapter_unlocked(int $chapter_id, int $course_id, ?int $user_id = null): bool`
  - `get_unlocked_chapter_ids(int $course_id, ?int $user_id = null): array`

**演算法**：
```php
public static function get_unlock_state(int $course_id, ?int $user_id = null): array {
    $user_id = $user_id ?? get_current_user_id();
    $product = wc_get_product($course_id);

    // 未開啟 → 全部解鎖
    if (!self::is_enabled($course_id)) {
        return self::build_all_unlocked($course_id);
    }

    // 管理員預覽 → 全部解鎖
    if (CourseUtils::is_admin_preview($course_id)) {
        return self::build_all_unlocked($course_id);
    }

    $flat_ids = ChapterUtils::get_flatten_post_ids($course_id);
    if (empty($flat_ids)) {
        return ['enabled' => true, 'unlocked_chapter_ids' => [], 'current_chapter_id' => null, 'locked_hints' => []];
    }

    // 取得已完成章節 IDs
    $finished_ids = CourseUtils::get_finished_sub_chapters($course_id, $user_id, true);

    // 找最遠完成位置
    $max_pos = -1;
    foreach ($finished_ids as $fid) {
        $pos = array_search((int)$fid, $flat_ids, true);
        if ($pos !== false && $pos > $max_pos) {
            $max_pos = $pos;
        }
    }

    // 解鎖範圍: 0 到 min(max_pos + 1, last_pos)
    $unlock_boundary = min($max_pos + 1, count($flat_ids) - 1);
    $unlocked_ids = array_slice($flat_ids, 0, $unlock_boundary + 1);

    // current_chapter_id: 第一個解鎖且未完成
    $current_chapter_id = null;
    foreach ($unlocked_ids as $uid) {
        if (!in_array($uid, $finished_ids, true)) {
            $current_chapter_id = $uid;
            break;
        }
    }

    // locked_hints
    $prerequisite_id = $flat_ids[$unlock_boundary] ?? $flat_ids[0];
    $prerequisite_title = get_the_title($prerequisite_id);
    $locked_hints = [];
    $locked_ids = array_slice($flat_ids, $unlock_boundary + 1);
    foreach ($locked_ids as $lid) {
        $locked_hints[$lid] = [
            'prerequisite_chapter_id' => $prerequisite_id,
            'prerequisite_chapter_title' => $prerequisite_title,
            'message' => sprintf("請先完成『%s』才能觀看本章節", $prerequisite_title),
        ];
    }

    return [
        'enabled' => true,
        'unlocked_chapter_ids' => $unlocked_ids,
        'current_chapter_id' => $current_chapter_id,
        'locked_hints' => $locked_hints,
    ];
}
```

**依賴**：無（使用已有的 `ChapterUtils::get_flatten_post_ids()` 和 `CourseUtils::get_finished_sub_chapters()`）
**風險**：低 — 純計算邏輯，不修改任何既有程式碼

---

#### 步驟 1.2：修改 toggle-finish API 新增線性觀看驗證

**檔案**：`inc/classes/Resources/Chapter/Core/Api.php`
**方法**：`post_toggle_finish_chapters_with_id_callback()`（約 L261-342）

**行動**：
1. 在既有的 `$product` null check 之後（L279-287 後），新增線性觀看前置驗證：
   ```php
   // 線性觀看驗證：嘗試「完成」被鎖定的章節時拒絕（取消完成不受限制）
   if (!$is_this_chapter_finished && LinearViewing::is_enabled($course_id)) {
       if (!LinearViewing::is_chapter_unlocked($chapter_id, $course_id, $user_id)) {
           return new \WP_REST_Response(
               [
                   'code'    => 'rest_forbidden',
                   'message' => '該章節尚未解鎖，請先完成前面的章節',
               ],
               403
           );
       }
   }
   ```

2. 在兩處 response `data` 陣列中（L306-312 和 L330-337），新增 `unlocked_chapter_ids`：
   ```php
   'unlocked_chapter_ids' => LinearViewing::is_enabled($course_id)
       ? LinearViewing::get_unlocked_chapter_ids($course_id, $user_id)
       : null,
   ```

**依賴**：步驟 1.1
**風險**：中 — 修改現有 API 行為，需確保不影響非線性觀看課程
**注意**：`!$is_this_chapter_finished` 確保只在「嘗試完成」時驗證，「取消完成」不受限

---

#### 步驟 1.3：修改課程 API 新增 `enable_linear_viewing` meta 讀寫

**檔案**：`inc/classes/Api/Course.php`

**行動**：
1. 在 `format_course_details_records()` 的 `$extra_array` 中（約 L285 附近）新增：
   ```php
   'enable_linear_viewing' => (string) $product->get_meta('enable_linear_viewing') ?: 'no',
   ```
   位置：放在 `'enable_comment'` 附近，與其他 enable_* 設定分組

2. 不需修改 `handle_save_course_meta_data()`，因為現有邏輯已經用通用的 `foreach ($meta_data as $key => $value)` 迴圈處理所有 meta（L625-628），新的 meta key 會自動被保存

**依賴**：無
**風險**：低 — 僅新增一個 meta 讀取，寫入由既有通用邏輯處理

---

### 第二階段：後端頁面層鎖定

> 此階段在教室頁面模板中加入鎖定驗證與 redirect 邏輯。

#### 步驟 2.1：章節頁面新增線性觀看 redirect

**檔案**：`inc/templates/single-pc_chapter.php`

**行動**：
在既有的權限驗證區塊之後（L42-64 的 `if (!current_user_can('manage_woocommerce'))` 區塊結束後），新增線性觀看驗證：

```php
// 線性觀看：鎖定章節 redirect 到當前應觀看的章節
if (!current_user_can('manage_woocommerce')) {
    $linear_state = LinearViewing::get_unlock_state(
        $course_product->get_id(),
        $current_user_id
    );
    if ($linear_state['enabled']
        && !in_array($chapter_post->ID, $linear_state['unlocked_chapter_ids'], true)
    ) {
        $redirect_chapter_id = $linear_state['current_chapter_id'] ?? $linear_state['unlocked_chapter_ids'][0] ?? null;
        if ($redirect_chapter_id) {
            // 設定一次性 flash message（用 transient，5 秒過期）
            $transient_key = "pc_linear_redirect_{$current_user_id}";
            \set_transient($transient_key, '請先完成前面的章節才能觀看此內容', 5);
            \wp_safe_redirect(\get_permalink($redirect_chapter_id));
            exit;
        }
    }
}
```

在頁面 `<body>` 開始後，讀取 flash message 並注入到 `window.pc_data`：
```php
$transient_key = "pc_linear_redirect_{$current_user_id}";
$flash_message = \get_transient($transient_key);
if ($flash_message) {
    \delete_transient($transient_key);
}
```

然後在 `window.pc_data` JavaScript 物件中加入：
```javascript
"linear_flash_message": "<?php echo esc_js($flash_message ?: ''); ?>"
```

**依賴**：步驟 1.1
**風險**：中 — redirect 邏輯需仔細測試避免無限迴圈

---

#### 步驟 2.2：傳遞線性觀看狀態到前端模板

**檔案**：`inc/templates/single-pc_chapter.php`

**行動**：
在 `window.pc_data` JavaScript 物件中注入線性觀看狀態，供前端 JS 使用：

```php
$linear_state = $linear_state ?? LinearViewing::get_unlock_state(
    $course_product->get_id(),
    $current_user_id
);
```

然後在 `window.pc_data` 中加入：
```javascript
"linear_viewing": <?php echo wp_json_encode($linear_state); ?>
```

**依賴**：步驟 2.1
**風險**：低

---

### 第三階段：前端模板 UI 鎖定

> 此階段修改 PHP 模板渲染，在側邊欄、底部導航、影片區域加入鎖定 UI。

#### 步驟 3.1：側邊欄章節列表新增鎖定狀態

**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
**方法**：`get_children_posts_html_uncached()`（約 L660-718）

**行動**：
1. 方法新增參數 `?array $linear_state = null`
2. 在每個 `<li>` 的渲染中，判斷章節是否被鎖定：

```php
$is_locked = false;
$lock_hint = '';
if ($linear_state && $linear_state['enabled']) {
    $is_locked = !in_array($child_post->ID, $linear_state['unlocked_chapter_ids'], true);
    if ($is_locked && isset($linear_state['locked_hints'][$child_post->ID])) {
        $lock_hint = $linear_state['locked_hints'][$child_post->ID]['message'];
    }
}
```

3. 鎖定的章節：
   - `<li>` 加上 `data-locked="true"` 屬性和 `data-lock-hint="{提示文字}"`
   - 加上 CSS class `opacity-50 cursor-not-allowed`
   - `data-href` 設為空字串（阻止導航）
   - icon 區域改為鎖頭圖示（新建 `icon/lock` 模板或內聯 SVG）
   - 標題文字後加上 tooltip 提示

**檔案**：`inc/templates/pages/classroom/chapters.php`

**行動**：
1. 將 `$linear_state` 傳遞給 `get_children_posts_html_uncached()`：
   ```php
   $linear_state = $GLOBALS['pc_linear_state'] ?? null;
   $chapters_html = ChapterUtils::get_children_posts_html_uncached(
       $product->get_id(),
       null,
       0,
       'classroom',
       $linear_state
   );
   ```

2. 在 inline `<script>` 中，為鎖定的 `<li>` 加入點擊阻止：
   ```javascript
   // 點擊被鎖定的章節時顯示提示
   $el.on('click', 'li[data-locked="true"]', function(e) {
       e.preventDefault();
       e.stopPropagation();
       const hint = $(this).data('lock-hint');
       if (hint) {
           alert(hint); // 或使用更友善的 toast
       }
   });
   ```

**依賴**：步驟 2.2（需要 `$linear_state` 從 single-pc_chapter.php 傳入）
**風險**：中 — 修改核心 sidebar 渲染方法，需確保不影響非線性觀看課程和課程銷售頁（context='course-product'）

---

#### 步驟 3.2：Header 區域「前往下一章節」按鈕鎖定

**檔案**：`inc/templates/pages/classroom/header.php`

**行動**：
在「前往下一章節」按鈕邏輯（L67-96）中，判斷下一章是否被鎖定：

```php
// 檢查下一章節是否被線性觀看鎖定
$linear_state = $GLOBALS['pc_linear_state'] ?? null;
$is_next_locked = false;
if ($linear_state && $linear_state['enabled'] && $next_chapter_id) {
    $is_next_locked = !in_array($next_chapter_id, $linear_state['unlocked_chapter_ids'], true);
}
```

若 `$is_next_locked`，按鈕改為禁用狀態：
```php
if ($is_next_locked) {
    $next_chapter_button_html = sprintf(
        '<button id="pc-next-chapter-btn" class="pc-btn pc-btn-primary pc-btn-sm px-0 lg:px-4 text-white cursor-not-allowed opacity-70 w-full lg:w-auto text-xs sm:text-base" tabindex="-1" role="button" aria-disabled="true" data-locked="true">
            完成本章節後即可觀看下一章
        </button>'
    );
}
```

同時在 `<button>` 上加 `id="pc-next-chapter-btn"` 以便前端 JS 即時更新。

**依賴**：步驟 2.2
**風險**：低

---

#### 步驟 3.3：影片區域禁止自動跳轉

**檔案**：`inc/templates/pages/classroom/body.php`

**行動**：
在影片模板的 `next_post_url` 計算處（L41-42），加入線性觀看判斷：

```php
$next_post_id  = ChapterUtils::get_next_post_id( $chapter_id );
$next_post_url = $next_post_id ? ( \get_permalink( $next_post_id ) ?: '' ) : '';

// 線性觀看：下一章被鎖定時清空 next_post_url，阻止影片播完自動跳轉
$linear_state = $GLOBALS['pc_linear_state'] ?? null;
if ($linear_state && $linear_state['enabled'] && $next_post_id) {
    if (!in_array($next_post_id, $linear_state['unlocked_chapter_ids'], true)) {
        $next_post_url = ''; // 清空 → vidstack 不會自動跳轉
    }
}
```

**依賴**：步驟 2.2
**風險**：低 — 只影響 `next_post_url` 的傳遞值

---

#### 步驟 3.4：底部「下一個」導航按鈕鎖定

**檔案**：`inc/templates/components/related-posts/prev-next.php`

**行動**：
在 `$next_post` 的渲染區塊中（L39-53），加入鎖定判斷：

```php
$linear_state = $GLOBALS['pc_linear_state'] ?? null;
$is_next_locked = false;
if ($linear_state && $linear_state['enabled'] && $next_post_id) {
    $is_next_locked = !in_array($next_post_id, $linear_state['unlocked_chapter_ids'], true);
}

if ($next_post && $is_next_locked) {
    // 顯示禁用狀態的「下一個」
    printf(
        '<div class="pc-next-post group w-full rounded-box border border-solid border-base-content/10 p-4 flex items-center gap-x-2 md:gap-x-4 relative opacity-50 cursor-not-allowed" data-locked-next="true">
            <div class="flex-1 text-right pt-6">
                <p class="m-0 text-sm md:text-base text-base-content/50">%1$s</p>
                <p class="m-0 text-xs text-base-content/30 mt-1">🔒 完成本章節後即可觀看</p>
            </div>
            <svg class="size-4 md:size-6 stroke-base-content/30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">...</svg>
            <p class="m-0 text-xs md:text-sm text-base-content/50 absolute top-4 right-10 md:right-14">下一個</p>
        </div>',
        $next_post->post_title
    );
} elseif ($next_post) {
    // 原本的正常渲染
}
```

**依賴**：步驟 2.2
**風險**：低

---

### 第四階段：前端 JS 即時更新

> 此階段修改前端 JavaScript，在完成章節後即時更新鎖定狀態。

#### 步驟 4.1：擴展 `finishChapterAtom` 狀態

**檔案**：`inc/assets/src/store.ts`

**行動**：
在 `finishChapterAtom` 中新增 `unlocked_chapter_ids` 欄位：

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
    unlocked_chapter_ids: undefined as number[] | null | undefined, // 新增
})
```

**依賴**：無
**風險**：低

---

#### 步驟 4.2：完成章節後即時更新鎖定 UI

**檔案**：`inc/assets/src/events/finishChapter.ts`

**行動**：
1. 在 `complete` callback 中，讀取 `unlocked_chapter_ids`：
   ```typescript
   const unlocked_chapter_ids = xhr?.responseJSON?.data?.unlocked_chapter_ids
   
   store.set(finishChapterAtom, (prev) => ({
       ...prev,
       isLoading: false,
       showDialog: true,
       dialogMessage: message,
       isFinished: is_this_chapter_finished,
       progress,
       icon_html,
       unlocked_chapter_ids, // 新增
   }))
   ```

2. 在 `store.sub` 的 `isSuccess` 區塊中，新增鎖定狀態更新邏輯：
   ```typescript
   // 更新側邊欄鎖定狀態
   if (unlocked_chapter_ids !== undefined && unlocked_chapter_ids !== null) {
       // 解鎖匹配的章節
       $('li[data-locked="true"]').each(function() {
           const postId = Number($(this).data('post-id'))
           if (unlocked_chapter_ids.includes(postId)) {
               $(this)
                   .removeAttr('data-locked')
                   .removeClass('opacity-50 cursor-not-allowed')
                   .attr('data-href', /* 需要從 somewhere 取得 permalink */)
               // 恢復 icon（使用 API 回傳的 icon_html 或預設 video icon）
               $(this).find('.pc-chapter-icon .pc-lock-icon').replaceWith(
                   '<div class="pc-tooltip pc-tooltip-right h-6" data-tip="點擊觀看">...</div>'
               )
           }
       })
       
       // 重新鎖定不在列表中的章節
       $('#pc-sider__main-chapters li').each(function() {
           const postId = Number($(this).data('post-id'))
           if (!unlocked_chapter_ids.includes(postId) && !$(this).attr('data-locked')) {
               $(this)
                   .attr('data-locked', 'true')
                   .addClass('opacity-50 cursor-not-allowed')
                   .attr('data-href', '')
           }
       })
       
       // 更新 header 的「前往下一章節」按鈕
       const $nextBtn = $('#pc-next-chapter-btn, .pc-btn-primary:contains("前往下一章節")')
       // ... 根據 unlocked_chapter_ids 判斷下一章是否解鎖
   }
   ```

3. 在頁面載入時，讀取 `window.pc_data.linear_flash_message`，若存在則顯示 toast：
   ```typescript
   // 新增函式：顯示線性觀看 redirect 的 flash message
   const flashMsg = (window as any).pc_data?.linear_flash_message
   if (flashMsg) {
       // 使用既有的 dialog 或新建 toast
       const Dialog = $('#finish-chapter__dialog')
       Dialog.find('#finish-chapter__dialog__title').text('提示')
       Dialog.find('#finish-chapter__dialog__message').text(flashMsg)
       Dialog?.[0]?.showModal()
   }
   ```

**依賴**：步驟 4.1, 3.1
**風險**：高 — 這是最複雜的前端變更，需處理多種邊界情況
**注意**：
- 需要考慮側邊欄 `<li>` 的 `data-href` 恢復問題。目前 href 是在 PHP 渲染時設定的，JS 端無法直接取得。建議在 `<li>` 上增加 `data-original-href` 屬性，鎖定時清空 `data-href`，解鎖時從 `data-original-href` 恢復
- 取消完成也需要即時鎖定後續章節

---

### 第五階段：管理後台 Admin SPA

#### 步驟 5.1：新增「啟用線性觀看」開關

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`

**行動**：
在「課程資訊」區塊（`<Heading>課程資訊</Heading>` 之後的 grid，約 L199-262）中新增：

```tsx
<Heading>教室設定</Heading>
<div className="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-3 gap-6">
    <FiSwitch
        formItemProps={{
            name: ['enable_linear_viewing'],
            label: '啟用線性觀看（循序學習模式）',
            tooltip: '開啟後，學員必須依照章節順序完成前面的章節才能觀看後面的章節。第一個章節永遠可觀看。',
        }}
    />
</div>
```

位置建議：放在「課程詳情」區塊之前，新增一個「教室設定」區塊。

**依賴**：步驟 1.3（API 層需已支援讀寫此 meta）
**風險**：低 — 使用既有的 `FiSwitch` 組件，與其他設定模式完全一致

---

## 測試策略

### PHP Integration Test（由 test-creator 負責）

1. **解鎖演算法測試**（`LinearViewing::get_unlock_state()`）：
   - 線性觀看關閉 → 全部解鎖
   - 無完成紀錄 → 僅第一章解鎖
   - 連續完成 → 逐步解鎖
   - 跳躍完成（最遠進度模式）→ 忽略中間缺口
   - 全部完成 → 全部解鎖
   - 管理員預覽 → 全部解鎖
   - 巢狀子章節平攤排序
   - 取消最遠完成 → 重新計算邊界
   - 取消非最遠完成 → 不影響邊界

2. **Toggle API 驗證**：
   - 線性觀看開啟 + 嘗試完成鎖定章節 → 403
   - 線性觀看開啟 + 完成已解鎖章節 → 200 + `unlocked_chapter_ids`
   - 線性觀看開啟 + 取消完成 → 200（不受鎖定限制）
   - 線性觀看關閉 + 完成任何章節 → 200（無 `unlocked_chapter_ids`）
   - 回應中 `unlocked_chapter_ids` 格式正確

3. **課程 API**：
   - 建立/更新課程帶 `enable_linear_viewing` → 正確存取 product meta
   - 取得課程詳情 → 包含 `enable_linear_viewing` 欄位

### E2E 測試（由 test-creator 負責）

1. **管理端 E2E**（`tests/e2e/01-admin/`）：
   - `linear-viewing-setting.spec.ts`：在課程編輯頁開啟/關閉線性觀看開關

2. **前台 E2E**（`tests/e2e/02-frontend/`）：
   - `015-linear-viewing-sidebar.spec.ts`：鎖定章節在側邊欄顯示鎖定圖示
   - `016-linear-viewing-finish-unlock.spec.ts`：完成章節後下一章即時解鎖
   - `017-linear-viewing-url-redirect.spec.ts`：直接 URL 存取鎖定章節被 redirect
   - `018-linear-viewing-next-btn.spec.ts`：下一章鎖定時底部按鈕禁用

3. **測試執行指令**：
   - `composer run test` — PHPUnit
   - `pnpm run test:e2e:admin` — 管理端
   - `pnpm run test:e2e:frontend` — 前台

### 關鍵邊界情況

- 課程只有一個章節 → 該章節永遠解鎖，功能形同未開啟
- 所有章節都已完成 → 全部解鎖，current_chapter_id = null
- 管理員中途啟用線性觀看，學員已有跳躍完成紀錄 → 最遠進度模式正確計算
- 管理員中途關閉線性觀看 → 恢復自由觀看，所有功能不受影響
- 章節排序變更後 → 下次查詢自動適用新排序

## 風險與緩解措施

- **風險 1（高）**：前端即時更新的 DOM 操作複雜，可能在各種邊界情況下出現不一致
  - **緩解**：使用 `data-locked` / `data-original-href` 等 data 屬性統一管理狀態，避免硬編碼 class 判斷。前端狀態以 API 回傳的 `unlocked_chapter_ids` 為唯一真實來源。

- **風險 2（中）**：`get_children_posts_html_uncached()` 方法參數增加，影響其他呼叫點
  - **緩解**：`$linear_state` 參數預設為 `null`，非線性觀看場景完全不受影響。需確認所有呼叫點（classroom/chapters.php 和 collapse/chapters.php）。

- **風險 3（中）**：redirect 邏輯可能影響 SEO 或造成無限迴圈
  - **緩解**：redirect 只在非管理員、有課程存取權、章節被鎖定時觸發。redirect 目標永遠是已解鎖的章節，不會再次 redirect。加上 `current_chapter_id = null` 時不 redirect 的安全防護。

- **風險 4（低）**：效能影響 — 每次頁面載入多查詢一次解鎖狀態
  - **緩解**：`get_flatten_post_ids()` 已有 cache，`get_finished_sub_chapters()` 查詢量與章節數成正比（通常 < 100）。可考慮後續加入 transient cache。

## 成功標準

- [ ] 管理員可在課程設定中看到「啟用線性觀看」開關，預設為關閉
- [ ] 開啟線性觀看後，學員進入教室時只有第一個章節是解鎖狀態
- [ ] 已完成的章節之後的第一個未完成章節為解鎖狀態，其餘未完成章節為鎖定狀態
- [ ] 最遠進度模式：跳躍完成時以最遠完成位置為基準計算解鎖
- [ ] 鎖定的章節在側邊欄顯示鎖定圖示和指名提示文字
- [ ] 學員點擊鎖定章節時，顯示友善提示訊息而非導航
- [ ] 學員完成章節後，下一個章節即時解鎖（不需重新載入頁面）
- [ ] 學員取消完成章節後，後續章節即時重新鎖定
- [ ] 底部「下一個」按鈕在下一章鎖定時顯示但禁用
- [ ] 影片播完時下一章鎖定則不自動跳轉
- [ ] 直接 URL 存取鎖定章節 → redirect 到當前應觀看章節 + 提示訊息
- [ ] 後端 toggle API 驗證：不允許完成被鎖定的章節
- [ ] 管理員預覽模式免除所有限制
- [ ] 未開啟線性觀看的課程，行為與現有系統完全一致
- [ ] 所有章節平攤為一維順序，按 menu_order 判斷前後關係
- [ ] PHPStan level 9 通過
- [ ] ESLint / Prettier 通過
- [ ] E2E 測試全數通過
