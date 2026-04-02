# 實作計劃：課程線性觀看功能 (Issue #160)

## 概述

Power Course LMS 外掛需要新增「線性觀看」(Sequential Viewing) 功能，讓管理員可以在每門課程開啟 `is_sequential` 設定，強制學員按照 DFS 前序遍歷順序（父章節 -> 子章節，依 menu_order ASC）逐一完成章節才能存取下一個。此功能涵蓋後端驗證、前台教室 UI 鎖定/導向、管理端設定開關，以及 API 回應擴展。

## 範圍模式：EXPANSION（全新功能）

預估影響 ~12 個檔案，屬於中等規模的新功能開發，不需要縮減範圍。

## 需求重述

### 誰在用
- **管理員**：在課程「其他設定」分頁開啟/關閉線性觀看
- **學員**：在教室前台按順序觀看課程章節

### 核心行為
1. 課程 meta `is_sequential`（`yes`/`no`，預設 `no`），存為 WooCommerce product postmeta
2. 開啟後，章節依 DFS 前序遍歷排序（已有 `get_flatten_post_ids` 實作）
3. 章節 N 可存取 <=> 序列中 N 之前的所有章節皆已完成 OR N 是序列中的第一個
4. 鎖定章節顯示鎖頭圖示，點擊顯示友善提示
5. 直接 URL 存取鎖定章節 -> 自動導向目前應觀看的章節
6. 完成章節後 API 回傳 `next_unlocked_chapter_id`，前端 JS 即時更新 DOM
7. 取消完成觸發連鎖鎖定重新計算
8. 後端 API 同步驗證，防止繞過
9. 關閉 `is_sequential` 後所有章節立即恢復自由存取，進度不受影響

### 成功標準
- [ ] 管理端可以開啟/關閉 `is_sequential`
- [ ] 前台教室側邊欄鎖定章節顯示鎖頭圖示
- [ ] 點擊鎖定章節顯示友善提示（不跳轉）
- [ ] 直接 URL 存取鎖定章節自動導向
- [ ] Toggle finish API 驗證鎖定狀態
- [ ] 完成章節後前端即時解鎖下一章節
- [ ] 取消完成後連鎖鎖定生效
- [ ] 非線性模式下所有章節自由存取
- [ ] 所有 Feature Spec 場景通過

## 已知風險（來自研究）

| 風險 | 說明 | 緩解措施 |
|------|------|----------|
| 既有學員非連續完成紀錄 | 開啟線性觀看後，已有跳過完成的學員會被「倒退鎖定」 | Spec 已覆蓋此場景：以第一個未完成章節為分界鎖定 |
| `get_flatten_post_ids` 快取 | 使用了 `wp_cache`（object cache），章節排序變更後快取可能過時 | 現有 LifeCycle 的 `delete_transient` 已清除 `prev_next` cache group |
| `get_children_posts_html` 快取 | 使用 `set_transient` 快取 24 小時，線性觀看的鎖頭圖示不應被快取（因每人不同） | 線性觀看模式下必須使用 `_uncached` 版本，或將鎖定狀態以 CSS/JS 在前端處理 |
| 效能：大量章節的鎖定計算 | 每次頁面載入需計算所有章節鎖定狀態 | `get_flatten_post_ids` 已有 object cache；完成狀態查詢可批次處理 |

## 架構變更

| # | 檔案路徑 | 變更類型 | 說明 |
|---|----------|----------|------|
| 1 | `inc/classes/Resources/Chapter/Utils/Utils.php` | 修改 | 新增 `get_sequential_access_map()`、`is_chapter_locked()`、`get_next_available_chapter_id()`、修改 `get_chapter_icon_html()` |
| 2 | `inc/classes/Resources/Chapter/Core/Api.php` | 修改 | `post_toggle_finish_chapters_with_id_callback` 增加鎖定驗證與 `next_unlocked_chapter_id` 回應 |
| 3 | `inc/classes/Api/Course.php` | 修改 | 課程 GET 回應增加 `is_sequential` 欄位 |
| 4 | `inc/templates/single-pc_chapter.php` | 修改 | 增加鎖定章節導向邏輯 |
| 5 | `inc/templates/pages/classroom/chapters.php` | 修改 | 傳遞 `is_sequential` 和鎖定狀態到前端 |
| 6 | `inc/templates/pages/classroom/sider.php` | 不變 | 僅透過 chapters.php 的變更間接受影響 |
| 7 | `inc/classes/Resources/Chapter/Utils/Utils.php` (`get_children_posts_html_uncached`) | 修改 | 鎖定章節渲染鎖頭圖示與 locked 樣式 |
| 8 | `inc/templates/components/icon/lock.php` | 新增 | 鎖頭 SVG 圖示模板 |
| 9 | `inc/assets/src/events/finishChapter.ts` | 修改 | 處理 `next_unlocked_chapter_id` 即時解鎖 |
| 10 | `inc/assets/src/store.ts` | 修改 | atom 增加 `next_unlocked_chapter_id` 欄位 |
| 11 | `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 修改 | 新增 `is_sequential` FiSwitch |

## 資料流分析

### 1. 章節存取判斷流程（前台頁面載入）

```
用戶存取章節 URL
    │
    ▼
single-pc_chapter.php
    │
    ├── 1. 課程存取權檢查（現有）
    │     └── [nil?] 未登入 → 導向登入頁
    │     └── [error?] 無權限 → 404/buy
    │
    ├── 2. 【新增】線性觀看鎖定檢查
    │     ├── 取得 is_sequential meta
    │     │     └── [nil/empty?] 預設 no → 跳過鎖定檢查
    │     ├── is_sequential = yes → 呼叫 Utils::is_chapter_locked()
    │     │     ├── 取得 flatten_post_ids → DFS 前序列表
    │     │     │     └── [empty?] 無章節 → 不鎖定
    │     │     ├── 取得用戶已完成章節 IDs（批次查詢）
    │     │     │     └── [empty?] 無完成紀錄 → 除第一個外全部鎖定
    │     │     └── 計算：目標章節之前的所有章節是否全部完成
    │     └── [locked?] 是 → wp_safe_redirect 到 next_available_chapter
    │           └── [error?] 無可用章節 → 導向第一個章節
    │
    └── 3. 渲染教室頁面（現有）
          └── 側邊欄章節列表帶鎖定狀態
```

### 2. 切換章節完成狀態流程（API）

```
POST /toggle-finish-chapters/{id}
    │
    ▼
VALIDATION
    ├── 用戶已登入？ [nil?] → 401
    ├── 章節存在？ [nil?] → 404
    ├── 課程存取權？ [error?] → 403 「無此課程存取權」
    ├── 課程未到期？ [error?] → 403 「課程存取已到期」
    └── 【新增】章節未鎖定？（is_sequential = yes 時）
          └── [error?] → 403 「請先完成前面的章節」
                         回傳 next_available_chapter_id
    │
    ▼
EXECUTE（現有邏輯）
    ├── 標記完成：AVLChapterMeta::add finished_at
    └── 取消完成：AVLChapterMeta::delete finished_at
    │
    ▼
RESPONSE
    ├── 現有欄位：chapter_id, course_id, is_this_chapter_finished, progress, icon_html
    └── 【新增】next_unlocked_chapter_id（僅 is_sequential = yes 且標記完成時）
          ├── [nil?] 所有章節已完成 → null
          └── [nil?] 非線性模式 → null
```

### 3. 管理端設定流程

```
React CourseOther 表單
    │
    ▼
FiSwitch (is_sequential: yes/no)
    │
    ▼
POST /courses/{id}（現有 API）
    │
    ▼
handle_save_course_meta_data()
    └── $product->update_meta_data('is_sequential', $value)
    └── $product->save_meta_data()
```

> 說明：`is_sequential` 作為一般 meta field 走現有的 course update 流程，不需要額外的 API 端點。FiSwitch 使用 `yes/no` 字串格式，與 `is_popular`、`is_free` 等欄位一致。

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|----------|----------|------------|
| `Utils::is_chapter_locked()` | chapter_id 不在課程的 flatten_post_ids 中 | 邏輯錯誤 | 回傳 false（不鎖定） | 否 |
| `Utils::is_chapter_locked()` | course_id 為 null（孤立章節） | nil path | 回傳 false（不鎖定） | 否 |
| `Utils::get_next_available_chapter_id()` | 所有章節已完成 | empty path | 回傳 null | 否 |
| `Utils::get_next_available_chapter_id()` | 課程無章節 | empty path | 回傳 null | 否 |
| `toggle-finish` API 鎖定檢查 | 學員嘗試完成鎖定章節 | 業務規則違反 | 403 + 錯誤訊息 + next_available_chapter_id | 是：「請先完成前面的章節」 |
| `single-pc_chapter.php` 導向 | 鎖定章節 URL 直接存取 | 業務規則違反 | 302 redirect + flash message | 是：提示訊息 |
| `get_children_posts_html_uncached` 鎖定渲染 | is_sequential 取得失敗 | nil path | 預設不鎖定 | 否 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|----------|---------|---------|------------|----------|
| 鎖定章節 URL 存取 | 管理員預覽模式應跳過鎖定 | 計劃中 | spec 未覆蓋 | 否 | 管理員繞過 |
| 取消完成連鎖鎖定 | 前端無法得知哪些章節被連鎖鎖定 | 計劃中 | spec 覆蓋 | 是（頁面重新載入） | 重新載入頁面 |
| 快取失效 | `get_children_posts_html` transient 快取了非個人化的鎖頭狀態 | 計劃中 | 需測試 | 是 | 改用 uncached 或 JS 渲染鎖頭 |
| 並行請求 | 兩個請求同時 toggle 同一章節 | 現有行為 | 否 | 是 | toggle 為冪等操作 |

## 實作步驟

### 第一階段：後端核心邏輯（Pure PHP，無 UI 依賴）

> 執行 Agent: `@wp-workflows:wordpress-master`

#### 步驟 1.1：新增鎖定計算核心方法

**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`

**行動**：新增三個靜態方法：

```php
/**
 * 取得課程的章節順序存取映射表
 * 計算每個章節是否對指定用戶鎖定
 *
 * @param int $course_id 課程 ID
 * @param int $user_id 用戶 ID
 * @return array<int, bool> key=chapter_id, value=is_locked
 */
public static function get_sequential_access_map( int $course_id, int $user_id ): array
```

邏輯：
1. 取得 `is_sequential` meta，若非 `yes` 回傳空陣列（代表全部不鎖定）
2. 呼叫 `self::get_flatten_post_ids($course_id)` 取得 DFS 前序列表
3. 若列表為空，回傳空陣列
4. 批次查詢用戶已完成的章節 IDs（使用現有 `CourseUtils::get_finished_sub_chapters`，回傳 IDs 模式）
5. 遍歷 flatten list：第一個章節永遠 `false`（不鎖定）；後續章節，若前一個章節未完成則該章節及之後全部 `true`（鎖定）
6. 回傳 `[chapter_id => is_locked]` 映射表

```php
/**
 * 檢查單一章節是否被鎖定
 *
 * @param int $chapter_id 章節 ID
 * @param int $course_id 課程 ID
 * @param int $user_id 用戶 ID
 * @return bool
 */
public static function is_chapter_locked( int $chapter_id, int $course_id, int $user_id ): bool
```

邏輯：
1. 取得 `is_sequential` meta，若非 `yes` 回傳 `false`
2. 呼叫 `get_sequential_access_map($course_id, $user_id)`
3. 回傳 `$map[$chapter_id] ?? false`

```php
/**
 * 取得目前應觀看的章節 ID（第一個未完成的章節）
 *
 * @param int $course_id 課程 ID
 * @param int $user_id 用戶 ID
 * @return int|null
 */
public static function get_next_available_chapter_id( int $course_id, int $user_id ): ?int
```

邏輯：
1. 取得 flatten_post_ids
2. 取得已完成章節 IDs
3. 遍歷找第一個未完成的章節 ID
4. 若全部完成回傳 `null`

**原因**：這是整個功能的核心演算法，必須先建立並獨立測試。`get_sequential_access_map` 一次性計算所有章節狀態，避免 N+1 查詢。

**依賴**：無

**風險**：低 — 純邏輯計算，不修改現有方法

**複雜度**：中

---

#### 步驟 1.2：修改 `get_chapter_icon_html` 支援鎖頭圖示

**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`（`get_chapter_icon_html` 方法）

**行動**：修改 `get_chapter_icon_html` 方法簽名，新增可選的 `$is_locked` 參數：

```php
public static function get_chapter_icon_html( int $chapter_id, bool $is_locked = false ): string
```

若 `$is_locked === true`：
- 回傳鎖頭圖示 HTML（使用 `Plugin::load_template('icon/lock')`）
- tooltip 文字為「請先完成前面的章節」

原有邏輯（未觀看/已開始/已完成）保持不變，僅在最前面加入鎖定判斷。

**原因**：鎖頭圖示需要與現有的影片/勾勾圖示走同一套渲染機制。

**依賴**：步驟 1.3（lock icon 模板）

**風險**：低 — 新增參數帶預設值，不影響現有呼叫

**複雜度**：低

---

#### 步驟 1.3：新增鎖頭圖示模板

**檔案**：`inc/templates/components/icon/lock.php`（新增）

**行動**：建立鎖頭 SVG 圖示模板，樣式與現有 `icon/video.php`、`icon/check.php` 一致（`class="w-full h-full"`）。使用標準的 padlock SVG。

**原因**：與現有圖示模板保持一致的架構模式。

**依賴**：無

**風險**：低

**複雜度**：低

---

#### 步驟 1.4：修改 Toggle Finish API 增加鎖定驗證

**檔案**：`inc/classes/Resources/Chapter/Core/Api.php`（`post_toggle_finish_chapters_with_id_callback` 方法）

**行動**：

1. 在 `$is_this_chapter_finished` 判斷之前，新增鎖定驗證：

```php
// 線性觀看模式下，檢查章節是否被鎖定
$is_sequential = (string) $product->get_meta('is_sequential') === 'yes';
if ($is_sequential) {
    $is_locked = ChapterUtils::is_chapter_locked($chapter_id, $course_id, $user_id);
    if ($is_locked) {
        $next_available = ChapterUtils::get_next_available_chapter_id($course_id, $user_id);
        return new \WP_REST_Response(
            [
                'code'    => '403',
                'message' => '請先完成前面的章節',
                'data'    => [
                    'status' => 403,
                    'next_available_chapter_id' => $next_available,
                ],
            ],
            403
        );
    }
}
```

2. 在「標記完成」的成功回應中增加 `next_unlocked_chapter_id`：

```php
// 在 $success 判斷後
$next_unlocked_chapter_id = null;
if ($is_sequential && $success) {
    $next_unlocked_chapter_id = ChapterUtils::get_next_available_chapter_id($course_id, $user_id);
}
```

並將 `next_unlocked_chapter_id` 加入回應的 `data` 陣列。

3. 取消完成的回應中也加入 `next_unlocked_chapter_id`（值為 `null`，因為取消完成不解鎖新章節）。

**原因**：後端必須驗證鎖定狀態，防止前端繞過。API 回應擴展讓前端能即時更新 UI。

**依賴**：步驟 1.1

**風險**：中 — 修改現有 API 回應結構，需確認前端相容性。由於只是增加新欄位，不影響現有欄位，風險可控。

**複雜度**：中

---

#### 步驟 1.5：課程 GET API 增加 `is_sequential` 欄位

**檔案**：`inc/classes/Api/Course.php`

**行動**：

1. 在 `get_courses_with_id_callback` 的回應中，找到 `is_free` 欄位附近，新增：

```php
'is_sequential' => (string) $product->get_meta('is_sequential') ?: 'no',
```

2. 在 `get_courses_callback` 的回應中同樣新增此欄位。

**原因**：管理端 React 表單需要讀取此欄位以正確顯示開關狀態。

**依賴**：無

**風險**：低 — 只增加新欄位

**複雜度**：低

---

### 第二階段：前台教室 PHP 模板修改

> 執行 Agent: `@wp-workflows:wordpress-master`

#### 步驟 2.1：教室模板增加鎖定章節導向

**檔案**：`inc/templates/single-pc_chapter.php`

**行動**：在現有的課程存取權檢查（`if (!current_user_can('manage_woocommerce'))` 區塊內），`post_status` 檢查之前，新增線性觀看鎖定檢查：

```php
// 線性觀看鎖定檢查（管理員跳過）
$is_sequential = (string) $course_product->get_meta('is_sequential') === 'yes';
if ($is_sequential) {
    $is_locked = ChapterUtils::is_chapter_locked(
        $chapter_post->ID,
        $course_product->get_id(),
        $current_user_id
    );
    if ($is_locked) {
        $next_available_id = ChapterUtils::get_next_available_chapter_id(
            $course_product->get_id(),
            $current_user_id
        );
        $redirect_url = $next_available_id
            ? \get_permalink($next_available_id)
            : \get_permalink(ChapterUtils::get_flatten_post_ids($course_product->get_id())[0] ?? 0);

        if ($redirect_url) {
            // 設置提示訊息（使用 transient 或 query param）
            \set_transient(
                "pc_sequential_notice_{$current_user_id}",
                '請先完成前面的章節，才能觀看此章節喔！',
                30
            );
            \wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
```

同時，在 `do_action('power_course_before_classroom_render')` 之後，檢查並顯示提示訊息：

```php
$sequential_notice = \get_transient("pc_sequential_notice_{$current_user_id}");
if ($sequential_notice) {
    \delete_transient("pc_sequential_notice_{$current_user_id}");
    // 傳遞給前端 JS 顯示 toast
}
```

**提示訊息顯示方式**：透過 `window.pc_data` 注入 `sequential_notice` 字串，前端 JS 收到後用現有的 dialog 機制顯示。

**原因**：防止學員透過 URL 直接存取鎖定章節，是功能的關鍵安全措施。

**依賴**：步驟 1.1

**風險**：中 — 涉及 redirect 邏輯，需確保不會造成 redirect loop

**複雜度**：中

---

#### 步驟 2.2：側邊欄章節列表渲染鎖定狀態

**檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`（`get_children_posts_html_uncached` 方法）

**行動**：

1. 修改 `get_children_posts_html_uncached` 方法簽名，新增可選參數：

```php
public static function get_children_posts_html_uncached(
    int $post_id,
    array $children_posts = null,
    $depth = 0,
    $context = 'classroom',
    array $locked_map = []  // 新增：chapter_id => is_locked
): string
```

2. 在渲染每個章節 `<li>` 時：
   - 若 `$locked_map[$child_post->ID] === true`：
     - 移除 `data-href` 屬性（或將其設為 `#`，防止跳轉）
     - 新增 `data-locked="true"` 屬性
     - 新增 `opacity-50 cursor-not-allowed` CSS class
     - 圖示改為鎖頭（呼叫 `get_chapter_icon_html($child_post->ID, true)`）

3. 遞迴呼叫子章節時也傳遞 `$locked_map`。

**注意快取問題**：`get_children_posts_html` 使用 transient 快取，但鎖定狀態是每個用戶不同的。解決方案：
- 當 `is_sequential = yes` 時，在 `chapters.php` 中直接呼叫 `_uncached` 版本並傳入 `$locked_map`
- 非線性模式下繼續使用快取版本

**原因**：教室側邊欄是學員看到鎖定狀態的主要介面。

**依賴**：步驟 1.1, 步驟 1.2

**風險**：中 — 修改核心渲染方法，需確保 `$locked_map = []` 時行為與現有完全一致

**複雜度**：中

---

#### 步驟 2.3：`chapters.php` 模板傳遞鎖定資料

**檔案**：`inc/templates/pages/classroom/chapters.php`

**行動**：

1. 取得 `is_sequential` 和 `locked_map`：

```php
$is_sequential = (string) $product->get_meta('is_sequential') === 'yes';
$locked_map = [];
if ($is_sequential) {
    $current_user_id = \get_current_user_id();
    $locked_map = ChapterUtils::get_sequential_access_map($product->get_id(), $current_user_id);
}
```

2. 條件式呼叫渲染方法：

```php
if ($is_sequential && !empty($locked_map)) {
    $chapters_html = ChapterUtils::get_children_posts_html_uncached(
        $product->get_id(), null, 0, 'classroom', $locked_map
    );
} else {
    $chapters_html = ChapterUtils::get_children_posts_html_uncached($product->get_id());
}
```

3. 將 `is_sequential` 和 `locked_map` 作為 `data-*` 屬性傳給前端 JS：

```php
<div id="pc-sider__main-chapters"
    class="pc-sider-chapters overflow-y-auto lg:ml-0 lg:mr-0"
    data-ancestor_ids="<?php echo $ancestor_ids_string; ?>"
    data-is_sequential="<?php echo $is_sequential ? 'true' : 'false'; ?>"
>
```

**原因**：模板是連接後端計算與前端渲染的橋樑。

**依賴**：步驟 1.1, 步驟 2.2

**風險**：低

**複雜度**：低

---

#### 步驟 2.4：側邊欄 JS 處理鎖定章節的點擊行為

**檔案**：`inc/templates/pages/classroom/chapters.php`（內嵌 `<script>` 區塊）

**行動**：

在現有的 `$el.on('click', 'li', ...)` 事件處理器中，增加鎖定判斷：

```javascript
const isLocked = $li.data('locked') === true;
if (isLocked) {
    // 有子章節時仍允許展開/收合
    if ($sub_ul.length > 0) {
        $li.toggleClass('expanded');
        $sub_ul.slideToggle('fast');
    }
    // 顯示友善提示
    alert('請先完成前面的章節，才能觀看此章節喔！');
    return; // 不跳轉
}
```

**提示方式**：使用現有教室的 dialog 機制（`#finish-chapter__dialog`），而非 `alert()`。或者使用 DaisyUI 的 toast。具體方式視現有教室的提示機制而定，此處以最簡實作為主。

**原因**：鎖定章節不應跳轉，但仍可展開子章節列表（方便學員預覽章節結構）。

**依賴**：步驟 2.2, 步驟 2.3

**風險**：低

**複雜度**：低

---

### 第三階段：前台 JS 即時更新

> 執行 Agent: `@wp-workflows:wordpress-master`（前台 vanilla TS，非 React）

#### 步驟 3.1：完成章節後即時解鎖下一章節

**檔案**：`inc/assets/src/events/finishChapter.ts`

**行動**：

1. 在 `complete` 回調中讀取 `next_unlocked_chapter_id`：

```typescript
const next_unlocked_chapter_id = xhr?.responseJSON?.data?.next_unlocked_chapter_id
```

2. 若 `next_unlocked_chapter_id` 存在且成功標記完成：
   - 找到對應的 `<li>` 元素
   - 移除 `data-locked` 屬性
   - 移除 `opacity-50 cursor-not-allowed` class
   - 恢復 `data-href` 為實際連結
   - 更新圖示（圖示在步驟 1.2 中已由後端計算好，透過下一次頁面載入更新即可；或者前端直接替換為影片圖示）

3. 在 store atom 中增加 `next_unlocked_chapter_id` 欄位。

**檔案**：`inc/assets/src/store.ts`

**行動**：在 `finishChapterAtom` 中增加：

```typescript
next_unlocked_chapter_id: undefined as number | undefined,
```

**原因**：完成章節後即時更新 UI，避免需要重新整理頁面。

**依賴**：步驟 1.4

**風險**：低

**複雜度**：低

---

#### 步驟 3.2：導向提示訊息顯示

**檔案**：`inc/templates/single-pc_chapter.php`（`window.pc_data` 注入區塊）

**行動**：在 `window.pc_data` 物件中增加 `sequential_notice` 欄位：

```php
"sequential_notice": "%6$s"
```

**檔案**：`inc/assets/src/events/` 中新增或在現有初始化中處理：

若 `window.pc_data.sequential_notice` 非空，頁面載入後顯示提示。

**原因**：redirect 後需要告知學員為什麼被導向。

**依賴**：步驟 2.1

**風險**：低

**複雜度**：低

---

### 第四階段：管理端 React UI

> 執行 Agent: `@wp-workflows:react-master`

#### 步驟 4.1：CourseOther 新增 `is_sequential` 開關

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`

**行動**：

在「課程資訊」Heading 區塊的 grid 中（`show_course_complete` 附近），新增一個 `FiSwitch`：

```tsx
<Heading>教室設定</Heading>
<div className="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-3 gap-6">
    <FiSwitch
        formItemProps={{
            name: ['is_sequential'],
            label: '線性觀看模式',
            tooltip:
                '開啟後，學員必須按照章節順序逐一完成才能觀看下一個章節。關閉後所有章節立即恢復自由存取。',
        }}
    />
</div>
```

位置：建議放在「課程詳情」與「銷售方案」之間，新增一個「教室設定」區塊。因為這是教室相關的設定，與課程介紹區域或銷售方案無關。

**原因**：管理員需要一個直覺的開關來控制線性觀看。FiSwitch 已處理 `yes/no` 轉換。

**依賴**：步驟 1.5（API 回傳 `is_sequential` 欄位）

**風險**：低

**複雜度**：低

---

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行，planner 只需規劃「要測什麼」。

### 整合測試（PHPUnit）

測試對象：`Utils::get_sequential_access_map()`、`Utils::is_chapter_locked()`、`Utils::get_next_available_chapter_id()`

覆蓋場景（對應 Feature Spec）：
1. 第一個章節永遠不鎖定
2. 完成前一個章節後下一個可存取
3. 未完成前一個時後續全部鎖定
4. 非連續完成紀錄的鎖定計算
5. 取消完成的連鎖鎖定
6. is_sequential = false 時全部不鎖定
7. 單層章節支援
8. 全部完成後全部不鎖定

### E2E 測試（Playwright）

1. **管理端設定**：開啟/關閉 is_sequential 開關，驗證儲存後生效
2. **前台鎖定顯示**：線性模式下側邊欄顯示鎖頭圖示
3. **前台導向**：直接 URL 存取鎖定章節被導向
4. **完成後即時解鎖**：完成章節後下一個章節的鎖頭移除
5. **API 驗證**：嘗試透過 API 完成鎖定章節回傳 403

### 測試執行指令

```bash
composer run test                    # PHPUnit
pnpm run test:e2e:frontend          # 前台 E2E
pnpm run test:e2e:admin             # 管理端 E2E
pnpm run test:e2e:integration       # 整合 E2E
```

### 關鍵邊界情況

- 空課程（無章節）開啟線性觀看
- 只有一個章節的課程
- 管理員存取鎖定章節（應跳過鎖定）
- 課程到期後的鎖定行為（應先被到期攔截，不到鎖定檢查）
- 章節排序變更後鎖定重新計算（依賴 flatten cache 清除機制）

## 風險與緩解措施

- **高：Transient 快取與個人化鎖定狀態衝突**
  - `get_children_posts_html` 使用 24 小時 transient，但鎖頭狀態因人而異
  - 緩解：線性觀看模式下使用 `_uncached` 版本，這會增加 DB 查詢但確保正確性
  - 未來優化：可考慮用 JS 在 client side 渲染鎖頭狀態，避免 PHP 端的個人化渲染

- **中：取消完成的前端即時更新**
  - 取消完成會導致多個章節被連鎖鎖定，前端需要重新計算所有受影響的章節
  - 緩解：取消完成後前端重新整理頁面（或 AJAX 重新載入側邊欄），而非逐個更新 DOM
  - 前端可在取消完成的回應後呼叫 `location.reload()` 確保一致性

- **低：管理員預覽模式**
  - 管理員在 `single-pc_chapter.php` 中已有 `current_user_can('manage_woocommerce')` 跳過存取檢查
  - 確保鎖定檢查也在這個條件區塊內即可

## 限制條件

- 不做全域預設設定（每門課程個別設定）
- 不做教室頂部的說明橫幅
- 不做「前端即時連鎖鎖定更新」（取消完成後重新整理頁面）
- 不做進度條與鎖定狀態的視覺整合
- 不修改課程商品頁（只影響教室頁面）
- 不做行動端的特殊處理（與桌面端相同邏輯）

## 預估複雜度：中

| 階段 | 步驟數 | 估計工時 | 風險 |
|------|--------|----------|------|
| 第一階段：後端核心邏輯 | 5 | 中 | 低 |
| 第二階段：前台 PHP 模板 | 4 | 中 | 中 |
| 第三階段：前台 JS 即時更新 | 2 | 低 | 低 |
| 第四階段：管理端 React UI | 1 | 低 | 低 |

## Agent 路由

| 步驟 | 執行 Agent |
|------|-----------|
| 1.1 - 1.5 | `@wp-workflows:wordpress-master` |
| 2.1 - 2.4 | `@wp-workflows:wordpress-master` |
| 3.1 - 3.2 | `@wp-workflows:wordpress-master` |
| 4.1 | `@wp-workflows:react-master` |
