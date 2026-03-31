# 實作計劃：課程章節線性觀看（Issue #145）

## 概述

為 Power Course 新增「線性觀看」功能：管理員可針對每門課程設定是否啟用線性觀看模式（product meta: `enable_linear_viewing`）。啟用後，學員必須按照扁平化章節順序（menu_order ASC）依序完成才能解鎖下一章節。父章節（分類標題）也納入線性序列，已完成章節永遠解鎖（不連鎖清除 finished_at）。

## 範圍模式：HOLD SCOPE

這是一個已明確定義的功能，有完整的 Feature specs、API 變更說明、ERM 更新和需求澄清。影響約 10 個檔案，不需要縮減。

## 需求重述

1. **管理員設定**：課程設定頁面新增 `enable_linear_viewing` 開關（yes/no，預設 no）
2. **解鎖邏輯**：
   - 第一個章節永遠解鎖
   - 已完成的章節（有 finished_at）永遠解鎖
   - 前一個章節（扁平化序列）已完成 → 當前章節解鎖
   - 管理員不受限
3. **API 變更**：GET /chapters 回傳 `is_locked`；toggle-finish 增加鎖定前置檢查 + 回傳 `next_unlocked_chapter_id`
4. **前台 URL 攔截**：鎖定章節 302 重導向到下一個應完成章節，帶 `?pc_locked=1`
5. **前台 UI**：側邊欄鎖定章節灰色 + 鎖頭圖示 + 不可點擊 + tooltip；Header「前往下一章節」按鈕在鎖定時停用
6. **取消完成**：不連鎖清除後續 finished_at，但未完成的後續章節重新鎖定

## 架構變更

| 檔案路徑 | 變更類型 | 說明 |
|---------|---------|------|
| `inc/classes/Resources/Chapter/Utils/LinearViewing.php` | **新增** | 線性觀看核心邏輯類別 |
| `inc/classes/Resources/Chapter/Core/Api.php` | 修改 | GET chapters + toggle-finish 增加線性觀看邏輯 |
| `inc/classes/Api/Course.php` | 修改 | format_course_records 回傳 enable_linear_viewing |
| `inc/templates/single-pc_chapter.php` | 修改 | 章節頁面載入前檢查鎖定並重導向 |
| `inc/templates/pages/classroom/chapters.php` | 修改 | 側邊欄章節列表加入鎖定狀態 HTML |
| `inc/templates/pages/classroom/header.php` | 修改 | 前往下一章節按鈕鎖定時停用 |
| `inc/assets/src/events/finishChapter.ts` | 修改 | 完成後更新側邊欄鎖定狀態 |
| `inc/assets/src/store.ts` | 修改 | atom 新增 next_unlocked_chapter_id |
| `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | 修改 | 新增 enable_linear_viewing 開關 |
| `tests/e2e/02-frontend/015-linear-viewing.spec.ts` | **新增** | E2E 測試 |

## 資料流分析

### 管理員設定 enable_linear_viewing

```
Admin React UI ──▶ POST /courses/{id} ──▶ handle_save_course_meta_data ──▶ wp_postmeta
     │                     │                        │                          │
     ▼                     ▼                        ▼                          ▼
  [FiSwitch]         [body_params]            [update_meta_data]         [meta_value]
  [yes/no]           [nil? → 忽略]            [exception? → WP_Error]   [stored]
```

### 學員進入章節頁面（鎖定檢查 + 重導向）

```
URL Request ──▶ single-pc_chapter.php ──▶ LinearViewing::is_chapter_locked() ──▶ 302 / 正常載入
     │                  │                            │                              │
     ▼                  ▼                            ▼                              ▼
  [chapter_id]    [is_avl check]              [get_flatten_post_ids]          [redirect target]
  [nil? → 404]    [not_avl? → buy page]       [empty? → false]               [pc_locked=1 toast]
                  [admin? → bypass]            [error? → false (fail open)]
```

### 切換章節完成（toggle-finish）

```
POST toggle ──▶ 鎖定前置檢查 ──▶ AVLChapterMeta add/delete ──▶ 回應 + next_unlocked
     │                │                    │                          │
     ▼                ▼                    ▼                          ▼
  [chapter_id]  [is_locked? → 403]   [add finished_at]         [next_unlocked_chapter_id]
  [course_id]   [not_linear? → skip] [delete finished_at]      [null if all done / un-finish]
                                     [exception? → 400]
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `LinearViewing::is_chapter_locked()` | course_id 無效 | RuntimeException | 回傳 false（fail open） | 否 |
| `LinearViewing::is_chapter_locked()` | chapter 不在 flat_ids 中 | 邏輯錯誤 | 回傳 false（fail open） | 否 |
| `toggle-finish` 鎖定前置檢查 | 章節鎖定時嘗試完成 | 403 Forbidden | 回傳錯誤訊息 | 是：「章節尚未解鎖，請先完成前面的章節」 |
| `single-pc_chapter.php` 重導向 | 找不到下一個應完成章節 | 邏輯邊界 | 重導向到第一個章節 | 是：toast 提示 |
| `POST /courses/{id}` | enable_linear_viewing 值無效 | 驗證錯誤 | 忽略無效值（現有 meta_data 儲存邏輯） | 否（靜默忽略） |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|-----------|---------|
| `is_chapter_locked()` + 空課程 | 無章節的課程 | 是（空陣列 → false） | 待新增 | 否 | N/A |
| `is_chapter_locked()` + 快取過期 | flatten_post_ids 快取失效 | 是（重新查詢） | 既有快取邏輯 | 否 | 自動恢復 |
| toggle-finish + 並發操作 | 兩人同時完成同一章節 | 是（AVLChapterMeta 處理） | 既有 | 否 | N/A |
| 重導向迴圈 | 第一個章節也鎖定（不應發生） | 是（第一個永遠解鎖） | 待新增 | 否 | N/A |
| Admin 儲存 + 並發 | 兩管理員同時修改 | 是（最後寫入者勝出） | 低優先 | 否 | 手動修正 |

---

## 實作步驟

### 第一階段：後端核心 — 線性觀看邏輯引擎

> 執行 Agent: `@wp-workflows:wordpress-master`

#### 步驟 1.1 — 新增 LinearViewing 工具類別

**檔案：`inc/classes/Resources/Chapter/Utils/LinearViewing.php`（新增）**

- 行動：建立 `J7\PowerCourse\Resources\Chapter\Utils\LinearViewing` 抽象類別，包含：
  - `is_chapter_locked(int $chapter_id, int $course_id, int $user_id): bool` — 核心鎖定判斷
  - `get_first_locked_chapter_id(int $course_id, int $user_id): ?int` — 取得第一個應完成但未完成的章節（用於重導向）
  - `get_next_unlocked_chapter_id(int $chapter_id, int $course_id, int $user_id): ?int` — 完成後新解鎖的章節 ID
  - `get_chapters_lock_map(int $course_id, int $user_id): array<int, bool>` — 一次取得所有章節鎖定狀態（批量優化，避免 N+1）
- 原因：將線性觀看邏輯封裝為獨立類別，遵循 Single Responsibility。`get_chapters_lock_map` 可在章節列表渲染時一次查詢所有狀態，避免逐一呼叫 `is_chapter_locked`
- 依賴：無
- 風險：低
- 解鎖演算法虛擬碼：
  ```
  function is_chapter_locked(chapter_id, course_id, user_id):
    if get_post_meta(course_id, 'enable_linear_viewing', true) !== 'yes': return false
    if user_can(user_id, 'manage_woocommerce'): return false
    flat_ids = ChapterUtils::get_flatten_post_ids(course_id)
    if empty(flat_ids): return false
    if chapter_id === flat_ids[0]: return false
    if has_finished_at(chapter_id, user_id): return false
    index = array_search(chapter_id, flat_ids)
    if index === false: return false  // 不在序列中，fail open
    prev_id = flat_ids[index - 1]
    if has_finished_at(prev_id, user_id): return false
    return true
  ```

#### 步驟 1.2 — 修改 toggle-finish API 增加鎖定前置檢查

**檔案：`inc/classes/Resources/Chapter/Core/Api.php`**

- 行動：在 `post_toggle_finish_chapters_with_id_callback()` 方法中，在 `$is_this_chapter_finished` 判斷之前，新增：
  1. 取得課程的 `enable_linear_viewing` meta
  2. 若為 `'yes'` 且非管理員，呼叫 `LinearViewing::is_chapter_locked()` 檢查
  3. 若鎖定則回傳 403：`{ code: 'rest_forbidden', message: '章節尚未解鎖，請先完成前面的章節', data: { status: 403 } }`
  4. 在成功完成/取消完成的回應 `data` 中，新增 `next_unlocked_chapter_id` 欄位
- 原因：防止前端繞過 UI 直接呼叫 API 完成鎖定章節；提供前端即時更新所需資訊
- 依賴：步驟 1.1
- 風險：中（修改既有 API 回應結構，需確保向後相容）

#### 步驟 1.3 — GET chapters API 回傳 is_locked

**檔案：`inc/classes/Resources/Chapter/Core/Api.php`**

- 行動：在 `get_chapters_callback()` 中，若請求帶有 `post_parent`（課程 ID），且課程啟用線性觀看，則為每個章節附加 `is_locked` 布林欄位。使用 `LinearViewing::get_chapters_lock_map()` 批量查詢
- 原因：前台側邊欄需要知道每個章節的鎖定狀態
- 依賴：步驟 1.1
- 風險：低（新增欄位，不影響既有欄位）
- 注意：需判斷當前用戶身分。GET chapters 在管理端呼叫時 `is_locked` 應全為 false（管理員不受限）

#### 步驟 1.4 — 課程 API 回傳 enable_linear_viewing

**檔案：`inc/classes/Api/Course.php`**

- 行動：在 `format_course_records()` 方法的 `$extra_array` 中新增：
  ```php
  'enable_linear_viewing' => (string) $product->get_meta('enable_linear_viewing') ?: 'no',
  ```
- 原因：管理後台 React 需要讀取此欄位以顯示開關狀態
- 依賴：無
- 風險：低

### 第二階段：前台 PHP 模板 — 重導向與鎖定 UI

> 執行 Agent: `@wp-workflows:wordpress-master`

#### 步驟 2.1 — 章節頁面鎖定重導向

**檔案：`inc/templates/single-pc_chapter.php`**

- 行動：在 `!current_user_can('manage_woocommerce')` 區塊內，在 `is_avl` 檢查之後、`post_status` 檢查之前，新增線性觀看鎖定檢查：
  ```php
  // 線性觀看鎖定檢查
  $course_id = $course_product ? $course_product->get_id() : 0;
  if ($course_id && LinearViewing::is_chapter_locked($chapter_post->ID, $course_id, $current_user_id)) {
      $redirect_chapter_id = LinearViewing::get_first_locked_chapter_id($course_id, $current_user_id);
      $redirect_url = $redirect_chapter_id
          ? add_query_arg('pc_locked', '1', get_permalink($redirect_chapter_id))
          : add_query_arg('pc_locked', '1', get_permalink($chapter_ids[0] ?? 0));
      wp_safe_redirect($redirect_url);
      exit;
  }
  ```
- 原因：防止學員透過 URL 直接存取鎖定章節
- 依賴：步驟 1.1
- 風險：中（修改核心模板，需充分測試重導向目標是否正確）
- 邊界情況：
  - 課程無章節 → 不觸發鎖定（flat_ids 為空 → is_chapter_locked 回傳 false）
  - redirect_chapter_id 為 null → 重導向到第一個章節

#### 步驟 2.2 — 側邊欄章節列表鎖定 UI

**檔案：`inc/classes/Resources/Chapter/Utils/Utils.php`**

- 行動：修改 `get_children_posts_html_uncached()` 方法：
  1. 新增可選參數 `$lock_map = []`（型別 `array<int, bool>`）
  2. 在渲染每個 `<li>` 時，檢查 `$lock_map[$child_post->ID] ?? false`
  3. 鎖定章節：
     - 移除 `data-href` 屬性（不可點擊）
     - 新增 CSS class: `opacity-50 cursor-not-allowed pointer-events-none`
     - 替換 icon 為鎖頭圖示（使用 `Plugin::load_template('icon/lock')` 或內聯 SVG）
     - 新增 tooltip: `data-tip="請先完成前面的章節"`
  4. 遞迴傳遞 `$lock_map` 到子章節

**檔案：`inc/templates/pages/classroom/chapters.php`**

- 行動：在呼叫 `get_children_posts_html_uncached()` 之前，計算 lock_map：
  ```php
  $user_id = get_current_user_id();
  $lock_map = [];
  $enable_linear = (string) $product->get_meta('enable_linear_viewing');
  if ($enable_linear === 'yes' && $user_id && !current_user_can('manage_woocommerce')) {
      $lock_map = LinearViewing::get_chapters_lock_map($product->get_id(), $user_id);
  }
  $chapters_html = ChapterUtils::get_children_posts_html_uncached($product->get_id(), null, 0, 'classroom', $lock_map);
  ```
- 原因：側邊欄需要根據鎖定狀態顯示不同 UI
- 依賴：步驟 1.1
- 風險：中（修改核心 HTML 渲染函式簽名，需確保既有呼叫點不受影響）
- 注意：`get_children_posts_html_uncached` 有多個呼叫點（classroom + course-product），course-product 不需要鎖定 UI，所以 `$lock_map` 預設為空陣列是正確的。但需確認 `get_children_posts_html()` 的快取機制：啟用線性觀看時，快取 key 需包含 user_id（因為不同用戶鎖定狀態不同）。建議：啟用線性觀看時直接呼叫 uncached 版本，或修改 cache key。

#### 步驟 2.3 — Header 前往下一章節按鈕鎖定

**檔案：`inc/templates/pages/classroom/header.php`**

- 行動：在計算 `$next_chapter_button_html` 時，增加鎖定判斷：
  1. 取得 `enable_linear_viewing` meta
  2. 若啟用且下一章節鎖定（`LinearViewing::is_chapter_locked($next_chapter_id, $product_id, $user_id)`），則渲染為 disabled 按鈕，tooltip 顯示「請先完成當前章節」
- 原因：鎖定時「前往下一章節」按鈕不應可點擊
- 依賴：步驟 1.1
- 風險：低

#### 步驟 2.4 — 重導向後 toast 提示

**檔案：`inc/templates/single-pc_chapter.php` 或 `inc/templates/pages/classroom/body.php`**

- 行動：在教室頁面渲染時，檢查 `$_GET['pc_locked']`，若為 `'1'` 則注入 JavaScript toast 提示：「請先完成前面的章節，才能觀看該章節」
- 原因：重導向後用戶需要知道為什麼被導到這個頁面
- 依賴：步驟 2.1
- 風險：低

### 第三階段：前台 JS — 完成後即時更新鎖定 UI

> 執行 Agent: `@wp-workflows:wordpress-master`（前台 JS 是 vanilla TS，非 React）

#### 步驟 3.1 — 更新 finishChapterAtom

**檔案：`inc/assets/src/store.ts`**

- 行動：在 `finishChapterAtom` 中新增 `next_unlocked_chapter_id: undefined as number | null | undefined`
- 原因：完成章節後需要知道下一個解鎖的章節 ID 以更新 UI
- 依賴：無
- 風險：低

#### 步驟 3.2 — 完成後更新側邊欄鎖定狀態

**檔案：`inc/assets/src/events/finishChapter.ts`**

- 行動：
  1. 在 AJAX complete callback 中，讀取 `xhr?.responseJSON?.data?.next_unlocked_chapter_id`
  2. 在 atom subscriber 中，當 `isSuccess && isFinished === true && next_unlocked_chapter_id` 時：
     - 找到 `li[data-post-id="${next_unlocked_chapter_id}"]`
     - 移除鎖定 CSS class（`opacity-50 cursor-not-allowed pointer-events-none`）
     - 恢復 `data-href` 屬性
     - 將鎖頭圖示替換為影片圖示（使用 API 回傳的 icon_html 或預設影片 SVG）
  3. 當 `isFinished === false`（取消完成）時，需重新計算鎖定狀態。可選方案：
     - A. 重新載入頁面（簡單但體驗差）
     - B. 呼叫 GET /chapters API 取得新的 lock_map（推薦）
     - C. 在 toggle API 回傳完整的 lock_map
  4. **建議方案 C**：在 toggle-finish API 回應中，當課程啟用線性觀看時，額外回傳 `lock_map: { [chapter_id]: boolean }` ，前端根據此 map 更新所有章節的鎖定狀態
- 原因：完成章節後側邊欄需即時反映鎖定狀態變化
- 依賴：步驟 1.2, 步驟 3.1
- 風險：中（涉及 DOM 操作，需確保選擇器正確匹配鎖定章節的 HTML 結構）

### 第四階段：管理後台 React — 設定開關

> 執行 Agent: `@wp-workflows:react-master`

#### 步驟 4.1 — 課程設定頁面新增 enable_linear_viewing 開關

**檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`**

- 行動：在「課程詳情」Section 中，新增一個 `FiSwitch` 元件：
  ```tsx
  <FiSwitch
    formItemProps={{
      name: ['enable_linear_viewing'],
      label: '啟用線性觀看',
      tooltip: '啟用後，學員必須按照章節順序完成才能解鎖下一個章節',
    }}
  />
  ```
- 原因：管理員需要能夠開啟/關閉此功能
- 依賴：步驟 1.4（API 需回傳此欄位）
- 風險：低
- 位置建議：放在「課程詳情」Heading 下方的第一個位置，因為這是課程級別的重要設定

### 第五階段：測試

> 測試策略由 tdd-coordinator 交給 test-creator 執行

#### PHPUnit 整合測試

**檔案：`tests/phpunit/LinearViewingTest.php`（新增）**

測試 `LinearViewing` 類別的核心邏輯：

1. **is_chapter_locked — 基本解鎖邏輯**
   - 未啟用線性觀看 → 所有章節不鎖定
   - 第一個章節永遠解鎖
   - 完成前一個章節後下一個解鎖
   - 已完成的章節永遠解鎖
   - 管理員不受限

2. **is_chapter_locked — 邊界情況**
   - 單章節課程 → 不鎖定
   - chapter 不在 flat_ids 中 → 不鎖定（fail open）
   - 空課程（無章節）→ 不鎖定
   - 取消完成後的連鎖狀態

3. **get_first_locked_chapter_id**
   - 新學員 → 回傳第一個章節
   - 部分完成 → 回傳下一個應完成的章節

4. **get_next_unlocked_chapter_id**
   - 完成當前章節後 → 回傳下一個章節 ID
   - 完成最後一個章節 → 回傳 null

#### Playwright E2E 測試

**檔案：`tests/e2e/02-frontend/015-linear-viewing.spec.ts`（新增）**

1. **管理員設定線性觀看**
   - 在課程編輯頁面開啟 enable_linear_viewing
   - 驗證儲存後重新載入仍為開啟狀態

2. **學員看到鎖定章節 UI**
   - 進入啟用線性觀看的課程教室
   - 驗證第一個章節可點擊，其餘章節顯示鎖頭
   - 驗證鎖定章節有灰色樣式

3. **學員完成章節後下一章節解鎖**
   - 點擊「標示為已完成」
   - 驗證下一章節從鎖頭變為影片圖示
   - 驗證下一章節可點擊

4. **URL 直接存取鎖定章節被重導向**
   - 直接存取鎖定章節 URL
   - 驗證 302 重導向到正確章節
   - 驗證頁面顯示 toast 提示

5. **前往下一章節按鈕在鎖定時停用**
   - 在未完成當前章節時驗證按鈕 disabled

**檔案：`tests/e2e/01-admin/linear-viewing-admin.spec.ts`（新增）**

1. **管理員設定 API 測試**
   - 透過 API 設定 enable_linear_viewing
   - 驗證 meta 正確儲存

## 風險與緩解措施

- **風險（中）**：`get_children_posts_html_uncached()` 函式簽名變更可能影響其他呼叫點
  - 緩解：`$lock_map` 參數預設為空陣列 `[]`，確保向後相容。所有既有呼叫點不需修改。

- **風險（中）**：快取與線性觀看的交互。`get_children_posts_html()` 使用 transient 快取，但鎖定狀態是 per-user 的
  - 緩解：在 `chapters.php` 模板中，啟用線性觀看時直接呼叫 `_uncached` 版本（跳過快取）。效能影響可接受，因為章節數量通常不大（< 100）。

- **風險（低）**：toggle-finish API 回應新增欄位
  - 緩解：新增欄位不破壞既有客戶端（向後相容），前端舊版本會忽略未知欄位。

- **風險（低）**：並發操作下的 race condition（兩個 tab 同時操作）
  - 緩解：鎖定狀態基於 DB 實時查詢，不依賴 session state。最壞情況是 UI 暫時不同步，重新載入即恢復。

## 限制條件

- 此計劃**不包含**批量設定線性觀看（僅支援單一課程設定）
- 此計劃**不包含**自動播放到下一章節的功能
- 此計劃**不包含**鎖定章節的預覽模式（學員只能看到標題，不能看到任何內容）
- 已完成章節的 finished_at 不會因為前面章節取消完成而被清除（此為需求決策 Q2）

## 成功標準

- [ ] 管理員可在課程設定頁面開啟/關閉線性觀看
- [ ] 啟用後，新學員只有第一個章節解鎖，其餘章節顯示鎖定
- [ ] 完成章節後，下一章節即時解鎖（側邊欄 UI 即時更新）
- [ ] URL 直接存取鎖定章節被 302 重導向，頁面顯示 toast 提示
- [ ] toggle-finish API 在鎖定章節時回傳 403
- [ ] 管理員不受線性觀看限制
- [ ] 已完成章節永遠解鎖（取消完成前面章節不影響）
- [ ] PHPUnit 測試覆蓋核心解鎖邏輯
- [ ] E2E 測試覆蓋完整使用者旅程
- [ ] PHPStan level 9 通過
- [ ] 無 TypeScript `any` 型別洩漏

## 預估複雜度：中

影響 ~10 個檔案，核心邏輯明確（解鎖演算法已有虛擬碼），主要風險在模板修改和前台 JS DOM 操作。
