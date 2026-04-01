# 實作計畫：課程章節線性觀看模式 (Issue #152)

## 概述

為 Power Course 外掛新增「線性觀看模式」功能。當課程開啟 `enable_linear_mode` 後，學員必須按照章節排序（DFS 展開後 `menu_order` ASC）依序完成前面的章節，才能觀看後續章節。管理員與講師豁免此限制。此功能涉及後端鎖定邏輯、Template 層阻擋、REST API 防護、教室頁面 UI 鎖頭圖示、管理端開關設定。

## 範圍模式：HOLD SCOPE

本功能範圍已明確（Issue 澄清完成），預估影響 8-10 個檔案，適合 HOLD SCOPE 模式。專注於防彈架構與邊界情況。

## 需求重述

- 每門課程可獨立開啟/關閉線性觀看模式（`enable_linear_mode` meta, `'yes'`/`'no'`，預設 `'no'`）
- 開啟後，章節按 DFS 展開後的 `menu_order` ASC 排序，學員必須依序完成
- 第一個章節永遠可觀看
- 後端雙層阻擋：Template 層 + REST API 層
- 管理員（`manage_woocommerce`）與講師（`teacher_ids`）豁免
- 前端教室頁面：鎖定章節顯示鎖頭圖示，可點擊但彈出 Toast 警告
- 管理端「其他設定」Tab 新增 FiSwitch 開關
- 完成機制沿用現有手動切換（`toggle-finish-chapters` API）

## 架構變更

| 變更類型 | 檔案路徑 | 說明 |
|---------|---------|------|
| 修改 | `inc/classes/Resources/Chapter/Utils/Utils.php` | 新增 `is_chapter_locked()` 靜態方法；修改 `get_chapter_icon_html()` 加入鎖頭圖示 |
| 修改 | `inc/classes/Resources/Chapter/Utils/Utils.php` | 修改 `get_children_posts_html_uncached()` 傳遞鎖定狀態到 HTML |
| 修改 | `inc/templates/single-pc_chapter.php` | Template 層新增線性觀看鎖定阻擋 |
| 新增 | `inc/templates/pages/404/locked.php` | 鎖定提示頁面模板 |
| 新增 | `inc/templates/components/icon/lock.php` | 鎖頭 SVG 圖示 |
| 修改 | `inc/classes/Resources/Chapter/Core/Api.php` | `get_chapters_callback` 回傳鎖定狀態；REST API 層阻擋 |
| 修改 | `inc/classes/Api/Course.php` | `format_course_records()` 加入 `enable_linear_mode` 欄位 |
| 修改 | `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 新增 FiSwitch 開關 |
| 修改 | `inc/templates/pages/classroom/chapters.php` | jQuery 攔截鎖定章節點擊，顯示 Toast |

## 資料流分析

### 學員存取章節的鎖定判斷流程

```
REQUEST ──> IDENTIFY USER ──> CHECK COURSE MODE ──> FLATTEN CHAPTERS ──> FIND INDEX ──> CHECK PREV FINISHED ──> ALLOW/DENY
  |              |                   |                    |                  |                 |                    |
  v              v                   v                    v                  v                 v                    v
[nil?]      [not logged  [mode='no'?        [empty?          [not found?    [nil?            [Template:
 No POST]    in? ->       -> skip,           no chapters      chapter not    no finished_at   show locked
             redirect]    all unlocked]      in course]       in list]       -> locked]       page]
                                                                                             [API: 403]
```

**完整路徑說明**：
1. Happy path: 用戶已登入 -> 課程啟用線性模式 -> 章節在 DFS 序列中 -> 前面所有章節已完成 -> 允許存取
2. Nil path: 課程 ID 找不到 -> 回傳 false（不鎖定，交由其他機制處理）
3. Empty path: 課程無章節 -> 無鎖定對象，直接通過
4. Error path: `get_flatten_post_ids()` 失敗 -> 預設不鎖定（fail-open，避免誤擋已購買用戶）
5. Exemption path: 管理員/講師 -> 跳過所有鎖定判斷

### 管理端設定課程線性觀看

```
ADMIN UI (FiSwitch) ──> POST /courses/{id} ──> separator() ──> handle_save_course_meta_data() ──> update_meta_data('enable_linear_mode', 'yes'|'no') ──> save()
                                                                                                          |
                                                                                                          v
                                                                                                   [即時生效，無需額外操作]
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|------------|
| `is_chapter_locked()` | `get_course_id()` 回傳 null | Logic | 回傳 false（不鎖定） | 否（fail-open） |
| `is_chapter_locked()` | `get_flatten_post_ids()` 回傳空陣列 | Data | 回傳 false（不鎖定） | 否（fail-open） |
| `is_chapter_locked()` | `get_post_meta('enable_linear_mode')` 回傳空字串 | Data | 視為 'no'（預設不啟用） | 否 |
| Template 層阻擋 | 鎖定章節被直接 URL 存取 | Access Control | 顯示鎖定提示頁面 | 是（提示文字） |
| REST API 阻擋 | 鎖定章節被 API 請求 | Access Control | 回傳 403 + 錯誤訊息 | 是（403 + message） |
| 管理端 POST | `enable_linear_mode` 值非 `'yes'`/`'no'` | Validation | 現有 `sanitize_text_field` 清理，無效值存入後視為 `'no'` | 否 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|------------|---------|
| `is_chapter_locked()` — course_id 為 null | 章節不屬於任何課程 | 是（回傳 false） | 待新增 | 否 | 不鎖定 |
| `is_chapter_locked()` — 用戶未登入 | current_user_id = 0 | 是（回傳 false） | 待新增 | 否 | 由 Template redirect 處理 |
| Template 層 — 取消完成後 URL 存取 | 取消前面章節完成，當前章節重新鎖定 | 是（重新計算） | 待新增 | 是（鎖定頁面） | 引導回前面章節 |
| 教室頁面 JS — Toast 顯示 | jQuery 未載入 | 否 | 否 | 否 | JS console error，不影響功能 |
| REST API — 並發完成/取消 | Race condition | 否（極低機率） | 否 | 否 | 重新整理頁面 |

## 實作步驟

### 第一階段：核心鎖定邏輯（後端純邏輯，無 UI）

#### 步驟 1.1：新增 `is_chapter_locked()` 方法
- **檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
- **行動**：在 `Utils` 類別中新增 `public static function is_chapter_locked(int $chapter_id, ?int $user_id = null): bool` 方法
- **邏輯**：
  1. 取得 `user_id`（預設 `get_current_user_id()`）
  2. 若未登入，回傳 `false`（不鎖定 — 由其他機制處理未登入）
  3. 若用戶有 `manage_woocommerce` 權限，回傳 `false`
  4. 取得 `course_id` = `self::get_course_id($chapter_id)`，若為 null 回傳 `false`
  5. 檢查 `teacher_ids`：`get_post_meta($course_id, 'teacher_ids', false)`，若用戶在列表中回傳 `false`
  6. 取得 `enable_linear_mode` = `get_post_meta($course_id, 'enable_linear_mode', true)`，若非 `'yes'` 回傳 `false`
  7. 取得 DFS 扁平章節列表 `self::get_flatten_post_ids($course_id)`
  8. 找到 `$chapter_id` 在列表中的 index
  9. 若 index === 0（第一個章節），回傳 `false`
  10. 若 index 找不到，回傳 `false`
  11. 檢查 index 0 到 index-1 的所有章節是否都有 `finished_at`
  12. 若有任一未完成，回傳 `true`（鎖定）
  13. 否則回傳 `false`（解鎖）
- **原因**：核心業務邏輯，所有後續步驟都依賴此方法
- **依賴**：無
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 1.2：新增 `get_locked_chapter_ids()` 輔助方法
- **檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
- **行動**：新增 `public static function get_locked_chapter_ids(int $course_id, ?int $user_id = null): array` 方法
- **邏輯**：
  1. 取得 DFS 扁平列表
  2. 一次性查詢所有章節的 `finished_at`
  3. 回傳所有被鎖定的章節 ID 陣列
  4. 管理員/講師 -> 回傳空陣列
  5. `enable_linear_mode !== 'yes'` -> 回傳空陣列
- **原因**：教室頁面側邊欄需要一次知道所有章節的鎖定狀態，避免 N+1 查詢
- **依賴**：步驟 1.1（共用邏輯可抽取）
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

---

### 第二階段：Template 層阻擋

#### 步驟 2.1：新增鎖頭 SVG 圖示
- **檔案**：`inc/templates/components/icon/lock.php`（新增）
- **行動**：建立鎖頭 SVG 圖示模板，風格與現有 `check.php`、`video.php` 一致
- **原因**：教室頁面鎖定章節需要顯示鎖頭圖示
- **依賴**：無
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 2.2：新增鎖定提示頁面模板
- **檔案**：`inc/templates/pages/404/locked.php`（新增）
- **行動**：參照 `404/buy.php` 和 `404/expired.php` 的模式，建立鎖定提示頁面
- **內容**：
  - alert type: `warning`
  - message: `'請先完成前面的章節才能觀看此章節'`
  - buttons: 可選「前往下一個待完成章節」按鈕
- **原因**：學員直接 URL 存取被鎖定章節時的展示頁面
- **依賴**：步驟 2.1（使用鎖頭圖示）
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 2.3：Template 層加入鎖定判斷
- **檔案**：`inc/templates/single-pc_chapter.php`
- **行動**：在現有的 `!current_user_can('manage_woocommerce')` 檢查區塊內，於 `$is_expired` 檢查之後、`post_status` 檢查之前，新增線性觀看鎖定檢查
- **插入位置**：約第 53-58 行之間（`$is_expired` 檢查之後）
- **程式碼邏輯**：
  ```php
  } elseif ( ChapterUtils::is_chapter_locked( (int) $chapter_post->ID, $current_user_id ) ) {
      get_header();
      Plugin::load_template( '404/locked', null );
      get_footer();
      exit;
  }
  ```
- **原因**：第一道防線 — 阻擋學員透過 URL 直接存取被鎖定章節
- **依賴**：步驟 1.1、步驟 2.2
- **風險**：中（需確保不影響管理員/講師存取，此邏輯已在 `is_chapter_locked()` 內處理）
- **執行 Agent**：`@wp-workflows:wordpress-master`

---

### 第三階段：REST API 層阻擋

#### 步驟 3.1：修改 `get_chapters_callback` 回傳鎖定狀態
- **檔案**：`inc/classes/Resources/Chapter/Core/Api.php`
- **行動**：在 `get_chapters_callback()` 中，若請求包含 `post_parent`（代表查詢特定課程的章節），在回傳的每個章節資料中加入 `is_locked` 欄位
- **邏輯**：
  1. 在 `format_chapter_details()` 呼叫之後，追加鎖定狀態
  2. 或者在 `format_chapter_details()` 內部新增 `is_locked` 欄位（更乾淨）
- **原因**：前端教室頁面需要知道哪些章節被鎖定（用於動態渲染鎖頭圖示）
- **依賴**：步驟 1.1
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 3.2：REST API 回傳 403 阻擋被鎖定章節的內容
- **檔案**：`inc/classes/Resources/Chapter/Core/Api.php`
- **行動**：目前 `get_chapters_callback` 是列表查詢，不回傳敏感內容。主要需要在 `post_chapters_with_id_callback`（如果被用於讀取單一章節）以及教室頁面的內容載入中進行防護。由於教室頁面是 Template 渲染（非 SPA），Template 層阻擋已覆蓋此場景。
- **實際需要防護的是**：`toggle-finish-chapters` API — 學員不應該能 toggle 被鎖定章節的完成狀態（但根據 spec，取消完成不清除 `finished_at`，且 toggle 是對當前章節操作，不需要額外鎖定）
- **結論**：主要在 `format_chapter_details()` 加入 `is_locked` 標記即可。若後續有單一章節查詢 API，再追加 403 防護。
- **原因**：確保 API 層也有防護，不洩漏影片內容
- **依賴**：步驟 1.1
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

---

### 第四階段：教室頁面 UI（前台 PHP + jQuery）

#### 步驟 4.1：修改 `get_chapter_icon_html()` 支援鎖頭圖示
- **檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
- **行動**：修改 `get_chapter_icon_html()` 方法
- **邏輯**：
  1. 新增參數 `bool $is_locked = false`
  2. 若 `$is_locked === true`，回傳鎖頭圖示（取代進度圖示）
  3. tooltip: `'請先完成前面的章節才能觀看此章節'`
- **原因**：教室頁面章節列表需要顯示鎖頭圖示
- **依賴**：步驟 1.1、步驟 2.1
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 4.2：修改 `get_children_posts_html_uncached()` 傳遞鎖定狀態
- **檔案**：`inc/classes/Resources/Chapter/Utils/Utils.php`
- **行動**：
  1. 在方法起始處取得 `$locked_chapter_ids = self::get_locked_chapter_ids($course_id)` 
  2. 需要從 depth=0 呼叫時取得 course_id 並傳遞 locked_ids 到遞迴中
  3. 在每個 `$child_post` 的 `<li>` 中：
     - 若 `in_array($child_post->ID, $locked_chapter_ids)`，加入 `data-locked="true"` 屬性
     - 文字顏色降低對比度：`text-gray-400`
     - 圖示使用鎖頭：呼叫 `get_chapter_icon_html($child_post->ID, true)`
- **原因**：在教室頁面側邊欄顯示鎖定狀態
- **依賴**：步驟 1.2、步驟 4.1
- **風險**：中（遞迴結構修改需要謹慎，注意不破壞 course-product 上下文）
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 4.3：修改教室頁面 jQuery 攔截鎖定章節點擊
- **檔案**：`inc/templates/pages/classroom/chapters.php`
- **行動**：在現有 jQuery `click` handler 中，增加鎖定判斷
- **邏輯**：
  ```javascript
  // 在 li click handler 中，href 跳轉前檢查
  const isLocked = $li.data('locked');
  if (isLocked) {
      // 使用 DaisyUI toast 或自訂 toast 顯示警告
      // "請先完成前面的章節才能觀看此章節"
      // 3 秒後自動消失
      return; // 不跳轉
  }
  ```
- **原因**：前端友善互動體驗 — 鎖定章節可點擊但顯示提示
- **依賴**：步驟 4.2
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

---

### 第五階段：管理端設定（React 前端）

#### 步驟 5.1：Course API 加入 `enable_linear_mode` 欄位
- **檔案**：`inc/classes/Api/Course.php`
- **行動**：在 `format_course_records()` 方法中新增 `enable_linear_mode` 欄位
- **位置**：與 `enable_comment` 並列
- **程式碼**：
  ```php
  'enable_linear_mode' => (string) $product->get_meta( 'enable_linear_mode' ) ?: 'no',
  ```
- **原因**：管理端 React 前端需要讀取此欄位以顯示開關狀態
- **依賴**：無
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

#### 步驟 5.2：管理端 CourseOther Tab 新增 FiSwitch
- **檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`
- **行動**：在「課程詳情」Heading 區塊中，與 `enable_comment` 開關並列，新增 `enable_linear_mode` FiSwitch
- **程式碼**：
  ```tsx
  <FiSwitch
      formItemProps={{
          name: ['enable_linear_mode'],
          label: '線性觀看模式',
          tooltip: '開啟後學員必須按照章節順序依序完成，才能觀看後續章節',
      }}
  />
  ```
- **原因**：管理員需要在後台設定此功能
- **依賴**：步驟 5.1
- **風險**：低
- **執行 Agent**：`@wp-workflows:react-master`

---

### 第六階段：快取失效處理

#### 步驟 6.1：toggle-finish 後清除教室側邊欄 HTML 快取
- **檔案**：`inc/classes/Resources/Chapter/Core/Api.php`
- **行動**：在 `post_toggle_finish_chapters_with_id_callback()` 中，完成/取消完成後清除教室頁面側邊欄 HTML transient 快取
- **邏輯**：
  ```php
  // toggle 完成後
  $cache_key = ChapterUtils::get_cache_key($course_id);
  \delete_transient($cache_key);
  ```
- **原因**：`get_children_posts_html()` 使用 transient 快取，toggle 完成狀態後需更新鎖定圖示
- **依賴**：步驟 4.2
- **風險**：低（已有類似快取清除邏輯）
- **執行 Agent**：`@wp-workflows:wordpress-master`

## 測試策略

### 整合測試（PHP Integration Test）
- **線性觀看序列判斷**：
  - 第一個章節永遠解鎖
  - 未完成第一章時其餘全部鎖定
  - 依序完成後逐步解鎖
  - 取消完成後重新鎖定
  - 取消完成不清除後續 `finished_at`
- **管理員/講師豁免**：
  - 管理員查詢所有章節均為 unlocked
  - 講師查詢所有章節均為 unlocked
- **功能關閉**：
  - `enable_linear_mode = 'no'` 時所有章節均不鎖定
- **課程設定 API**：
  - 設定 `enable_linear_mode` 為 `'yes'` / `'no'`
  - 非管理員設定失敗
  - 預設值為 `'no'`

### E2E 測試（Playwright）
- **Template 層阻擋**：
  - 學員 URL 存取被鎖定章節 -> 顯示鎖定提示頁面
  - 學員 URL 存取已解鎖章節 -> 正常顯示
  - 管理員 URL 存取被鎖定章節 -> 正常顯示
- **教室頁面 UI**：
  - 鎖定章節顯示鎖頭圖示
  - 點擊鎖定章節顯示 Toast
  - 完成章節後下一章解鎖，圖示更新
- **管理端**：
  - FiSwitch 開關正確切換
  - 儲存後值正確回傳

### 測試執行指令
```bash
composer run test                    # PHPUnit
pnpm run test:e2e:frontend           # 前台 E2E
pnpm run test:e2e:admin              # 管理端 E2E
pnpm run test:e2e:integration        # 整合 E2E
```

### 關鍵邊界情況
- 課程只有 1 個章節 -> 永遠解鎖
- 課程有多層巢狀章節（父 > 子 > 孫）-> DFS 展開正確
- 中途取消完成 -> 後續章節重新鎖定但 `finished_at` 保留
- 切換 `enable_linear_mode` 後即時生效
- 管理員即使未購買課程也不受鎖定影響（已在 `current_user_can` 前置檢查）

## 風險與緩解措施

- **風險**：`get_children_posts_html_uncached()` 是遞迴方法，修改參數簽章可能影響其他呼叫端
  - 緩解措施：`$locked_chapter_ids` 以可選參數傳遞，預設為 null 時不影響既有行為。或透過方法內部自行取得（在 depth=0 時計算一次，透過遞迴參數傳遞）

- **風險**：Transient 快取導致鎖定狀態不即時更新
  - 緩解措施：toggle 完成時主動清除快取；或在教室頁面不使用快取版本（已是 `get_children_posts_html_uncached`）

- **風險**：DFS 展開順序與前端顯示順序不一致
  - 緩解措施：`get_flatten_post_ids()` 已實作 DFS 展開，與 `get_children_posts_html_uncached()` 使用相同查詢條件（`menu_order ASC`）

- **風險**：高併發下 `finished_at` 的讀寫 race condition
  - 緩解措施：鎖定狀態為即時計算，不快取中間狀態；worst case 是多允許一次存取，不會造成資料損壞

## 限制條件（此計畫不做的事）

- 不新增自動完成機制（如影片看完自動標記）-- 後續迭代
- 不修改 `toggle-finish-chapters` API 的行為 -- 沿用現有邏輯
- 不新增「前往下一個待完成章節」按鈕 -- 可選功能，後續迭代
- 不做前端 SPA 即時狀態更新（教室頁面是 PHP Template 渲染，toggle 後需要重新整理或 AJAX 更新圖示）
- 不做批量解鎖/鎖定 API

## 成功標準

- [ ] `is_chapter_locked()` 方法正確判斷所有場景（鎖定/解鎖/豁免/功能關閉）
- [ ] Template 層阻擋被鎖定章節的直接 URL 存取，顯示友善提示頁面
- [ ] REST API 章節列表回傳 `is_locked` 欄位
- [ ] 教室頁面側邊欄鎖定章節顯示鎖頭圖示 + 灰色文字
- [ ] 教室頁面點擊鎖定章節顯示 Toast 警告，不跳轉
- [ ] 管理端「其他設定」可開啟/關閉線性觀看模式
- [ ] 所有 Gherkin feature 場景通過
- [ ] `pnpm run lint:php` 通過
- [ ] `pnpm run lint:ts` 通過
- [ ] `pnpm run build` 通過
