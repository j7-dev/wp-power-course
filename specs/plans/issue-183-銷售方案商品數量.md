# 實作計劃：銷售方案商品可自由填數量 (Issue #183)

## 概述

讓銷售方案中每個商品都可以自由設定數量（1~999 正整數），同時移除「排除目前課程」開關，改為目前課程預設帶入且可刪除的統一操作。影響範圍包含後端 PHP（Helper、API、Order 處理、前台模板、資料遷移）與前端 React（BundleForm、atom、utils、types）。

**範圍模式：HOLD SCOPE** — 功能需求已明確定義，專注於防彈架構與邊界情況。

## 需求

- 銷售方案編輯介面中，每個已選商品旁邊有「數量」輸入框（1~999 正整數，預設 1）
- 移除「排除目前課程」開關；新建方案預設帶入「目前課程」×1，管理員可像其他商品一樣刪除
- 修改數量後，組合原價自動重新計算（商品單價 × 數量的總和），可手動覆蓋
- 新增 `pbp_product_quantities` JSON meta 儲存各商品數量，向下相容（無此 meta 時預設 1）
- 訂單展開時 line item qty = 購買份數 × 商品數量
- 前台銷售頁數量 > 1 的商品顯示「×N」標示
- 舊資料遷移：exclude=yes 不變；exclude=no 或不存在 → 補入目前課程 ×1

## 架構變更

| # | 檔案 | 變更類型 | 說明 |
|---|------|----------|------|
| 1 | `inc/classes/BundleProduct/Helper.php` | 修改 | 新增 quantities 相關常數與方法 |
| 2 | `inc/classes/Api/Product.php` | 修改 | API 回應新增 quantities、移除 exclude_main_course、handle_special_fields 處理新欄位 |
| 3 | `inc/classes/Resources/Order.php` | 修改 | 展開 bundle 商品時讀取 quantities 設定 qty |
| 4 | `inc/templates/components/card/bundle-product.php` | 修改 | 前台顯示 ×N 標示 |
| 5 | `inc/templates/pages/course-product/list.php` | 修改 | 支援接收並顯示 quantity 參數 |
| 6 | `inc/classes/Compatibility/Compatibility.php` | 修改 | 新增遷移方法呼叫 |
| 7 | `inc/classes/Compatibility/BundleProduct.php` | 修改 | 新增 migrate_exclude_main_course 遷移方法 |
| 8 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx` | 修改 | 移除 FiSwitch、目前課程改為可刪除、新增 InputNumber |
| 9 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx` | 修改 | selectedProductsAtom 類型擴充 qty |
| 10 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx` | 修改 | getPrice 加入數量因子、新增常數 |
| 11 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/ProductPriceFields/index.tsx` | 修改 | 移除 exclude_main_course 相關邏輯 |
| 12 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx` | 修改 | 移除 exclude_main_course 驗證邏輯、新增 quantities 表單欄位 |
| 13 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/index.tsx` | 修改 | 新建方案時預設帶入 pbp_product_ids 含 courseId |
| 14 | `js/src/components/product/ProductTable/types/index.ts` | 修改 | 新增 pbp_product_quantities 類型、移除 exclude_main_course |

## 資料流分析

### 管理員儲存銷售方案（含數量）

```
前端 BundleForm ──▶ toFormData() ──▶ POST /bundle_products/{id} ──▶ handle_special_fields() ──▶ wp_postmeta
      │                  │                     │                          │                         │
      ▼                  ▼                     ▼                          ▼                         ▼
[selectedProducts  [pbp_product_ids[]   [body_params 解析     [pbp_product_quantities   [JSON 字串儲存
 含 qty 欄位]       + quantities JSON]    sanitize]             JSON decode → validate    至 postmeta]
                                                                → clamp 1~999]
```

**Shadow paths：**
- nil: `pbp_product_quantities` 未傳送 → 不更新，保留舊值（或無值 = 全部預設 1）
- empty: `pbp_product_quantities` 為 `{}` → 所有商品數量預設 1
- error: JSON decode 失敗 → 忽略，保留舊值，記錄 warning log

### 訂單展開銷售方案商品

```
WC 訂單建立 ──▶ _handle_add_course_item_meta_by_order() ──▶ 遍歷 items ──▶ 判斷 bundle ──▶ 讀取 quantities ──▶ add_product(qty)
      │                    │                                      │              │                │                    │
      ▼                    ▼                                      ▼              ▼                ▼                    ▼
[order items]       [foreach item]                          [get product]  [Helper::instance]  [get_product_qty()]  [qty = 購買份數
                                                             [nil → skip]  [not bundle → skip] [default 1]           × 商品數量]
```

**Shadow paths：**
- nil: product 不存在 → continue（已有）
- nil: quantities meta 不存在 → 預設 qty = 1（向下相容）
- error: add_product 失敗 → WC 內部處理，不影響其他 items

### 前台顯示銷售方案卡片

```
bundle-product.php ──▶ Helper::get_product_quantities() ──▶ foreach pbp_product_ids ──▶ 渲染 ×N
       │                         │                                   │                       │
       ▼                         ▼                                   ▼                       ▼
[get_product_ids()]    [JSON decode / default {}]           [get_product_qty($id)]    [qty > 1 ? "×{qty}" : ""]
```

**Shadow paths：**
- nil: quantities meta 不存在 → 空陣列 → 所有 qty = 1 → 不顯示 ×N
- nil: 某 product_id 不在 quantities 中 → 預設 1

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `Helper::get_product_quantities()` | meta 為非 JSON 字串 | 資料損壞 | json_decode 失敗回傳空陣列，預設 1 | 靜默 |
| `Helper::set_product_quantities()` | JSON encode 失敗 | 系統錯誤 | 不更新 meta，記錄 error log | 靜默 |
| `handle_special_fields()` 解析 quantities | 前端傳入非法 JSON | 輸入驗證 | json_decode 失敗忽略該欄位 | 靜默 |
| `Order::_handle_add_course_item_meta_by_order()` qty 計算 | 數量為 0 或負數 | 資料異常 | clamp 到 min=1 | 靜默 |
| 前端 InputNumber 輸入 | 使用者輸入非數字/小數/負數 | 輸入驗證 | Ant Design InputNumber 自動限制 | 即時提示 |
| 遷移 `migrate_exclude_main_course()` | product 已刪除 | 資料不完整 | wc_get_product 回傳 false → skip | 靜默 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|-----------|---------|
| Helper::get_product_quantities() | JSON 損壞 | ✅ 回傳 [] | 待新增 | 否 | 預設所有商品 qty=1 |
| Order 展開 bundle | quantities 缺失 | ✅ 預設 1 | 待新增 | 否 | 等同舊行為 |
| 前台模板渲染 | quantities 缺失 | ✅ 不顯示 ×N | 待新增 | 否 | 等同舊行為 |
| 遷移 exclude=yes | 不動 pbp_product_ids | ✅ 保留原樣 | 待新增 | 否 | 舊行為不變 |
| 遷移 exclude=no | 補入 link_course_id | ✅ 加入 qty=1 | 待新增 | 否 | 管理員可編輯 |

## 實作步驟

### 第一階段：後端核心 — Helper 與 API（無前端依賴）

#### 1. **擴充 BundleProduct\Helper**（檔案：`inc/classes/BundleProduct/Helper.php`）
- **行動**：
  1. 新增常數 `INCLUDE_PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities'`
  2. 新增方法 `get_product_quantities(): array` — 讀取 meta，JSON decode，回傳 `[product_id => qty]`（失敗回傳 `[]`）
  3. 新增方法 `set_product_quantities(array $quantities): void` — JSON encode 後寫入 meta
  4. 新增方法 `get_product_qty(int $product_id): int` — 從 quantities 中取出指定商品數量，預設 1，clamp 到 1~999
- **原因**：集中管理 quantities 資料的讀寫邏輯，其他模組統一透過 Helper 存取
- **依賴**：無
- **風險**：低

#### 2. **修改 API 回應格式**（檔案：`inc/classes/Api/Product.php`，`format_product()` 方法，約第 580-590 行）
- **行動**：
  1. 在 `format_product()` 回傳陣列中新增：`Helper::INCLUDE_PRODUCT_QUANTITIES_META_KEY => ($helper !== null ? $helper->get_product_quantities() : [])` — 使用新的 `INCLUDE_PRODUCT_QUANTITIES_META_KEY` 常數作為 key
  2. 移除 `'exclude_main_course'` 欄位（約第 586 行）
- **原因**：前端需要讀取 quantities 資料；exclude_main_course 功能已移除
- **依賴**：步驟 1
- **風險**：低（向下相容，舊前端收到新欄位不影響）

#### 3. **修改 handle_special_fields 處理 quantities**（檔案：`inc/classes/Api/Product.php`，`handle_special_fields()` 方法，約第 917-968 行）
- **行動**：
  1. 在 `handle_special_fields()` 中新增 `pbp_product_quantities` 的處理邏輯：
     - 從 `$meta_data` 取出 `pbp_product_quantities`
     - JSON decode（如為字串）
     - 驗證：確保每個值為 1~999 的正整數（`max(1, min(999, intval($qty)))`）
     - 透過 `Helper::set_product_quantities()` 儲存
     - 從 `$meta_data` 中 unset 避免重複寫入
  2. 同時移除 `exclude_main_course` 的任何殘留處理（如有）
- **原因**：quantities 是 JSON 格式，需要特殊處理而非直接 update_meta_data
- **依賴**：步驟 1
- **風險**：低

#### 4. **修改新建 bundle product 預設帶入目前課程**（檔案：`inc/classes/Api/Product.php`，`post_bundle_products_callback()` 約第 650 行）
- **行動**：
  1. 在 `post_bundle_products_callback()` 的 `handle_special_fields()` 之後，檢查 `link_course_ids` 值
  2. 如果前端未傳入 `pbp_product_ids` 或 `pbp_product_ids` 不包含 `link_course_id`，自動將 `link_course_id` 加入 `pbp_product_ids`
  3. 同時為該課程設定 `pbp_product_quantities` 中的數量為 1（如 quantities 不存在）
- **原因**：新建方案時預設帶入目前課程（Q2 決策）
- **依賴**：步驟 1, 3
- **風險**：低

### 第二階段：後端 — 訂單展開與前台模板

#### 5. **修改訂單展開邏輯**（檔案：`inc/classes/Resources/Order.php`，`_handle_add_course_item_meta_by_order()` 約第 95-143 行）
- **行動**：
  1. 在 bundle 商品展開迴圈中（約第 115-135 行），讀取 `$helper->get_product_qty((int) $included_product_id)`
  2. 修改 `$qty` 計算：`$qty = ($item->get_quantity() ?: 1) * $product_qty`（原本只有 `$item->get_quantity()`）
  3. 此修改使 `$order->add_product()` 的 qty 參數正確反映「購買份數 × 商品數量」
- **原因**：核心業務邏輯，確保庫存正確扣除
- **依賴**：步驟 1
- **風險**：中（影響訂單處理，需充分測試）

```
// 修改前 (Order.php ~line 122)
$qty = $item->get_quantity() ?: 1;

// 修改後
$bundle_qty = $item->get_quantity() ?: 1;
$product_qty = $helper->get_product_qty( (int) $included_product_id );
$qty = $bundle_qty * $product_qty;
```

#### 6. **修改前台 bundle-product 模板**（檔案：`inc/templates/components/card/bundle-product.php`）
- **行動**：
  1. 在 `$pbp_product_ids` 下方讀取 quantities：`$quantities = $helper->get_product_quantities();`
  2. 在 foreach 迴圈中（約第 85-99 行），傳遞 quantity 給 `course-product/list` 模板：
     ```php
     $qty = $quantities[ (string) $pbp_product_id ] ?? 1;
     Plugin::load_template('course-product/list', [
         'product'  => $pbp_product,
         'quantity' => (int) $qty,
     ]);
     ```
- **原因**：前台銷售頁需要顯示數量標示
- **依賴**：步驟 1
- **風險**：低

#### 7. **修改 course-product/list 模板支援 quantity 顯示**（檔案：`inc/templates/pages/course-product/list.php`）
- **行動**：
  1. 在 `$default_args` 中新增 `'quantity' => 1`
  2. 在 product name 顯示後，如果 `$quantity > 1`，附加 `<span class="text-primary font-bold">×{$quantity}</span>`
- **原因**：銷售方案卡片中的商品列表需顯示「×N」
- **依賴**：步驟 6
- **風險**：低

### 第三階段：資料遷移

#### 8. **新增遷移方法**（檔案：`inc/classes/Compatibility/BundleProduct.php`）
- **行動**：
  1. 新增靜態方法 `migrate_exclude_main_course(): void`
  2. 邏輯：
     - 取得所有 bundle products（使用現有 `get_all_bundle_products()`）
     - 對每個 bundle：
       a. 讀取 `exclude_main_course` meta
       b. 讀取 `link_course_ids` meta（即 link_course_id）
       c. 如果 `exclude_main_course` !== 'yes'（即 'no' 或不存在）：
          - 讀取現有 `pbp_product_ids`
          - 如果 `link_course_id` 不在 `pbp_product_ids` 中，將其加入（prepend）
          - 讀取或初始化 `pbp_product_quantities`，為 `link_course_id` 設定 qty = 1（如不存在）
       d. 刪除 `exclude_main_course` meta（cleanup）
  3. 遷移完成後記錄 log
- **原因**：確保舊有用戶的資料平滑遷移，維持向下相容
- **依賴**：步驟 1
- **風險**：中（影響既有資料，需 idempotent）

#### 9. **註冊遷移**（檔案：`inc/classes/Compatibility/Compatibility.php`，`compatibility()` 方法約第 56-104 行）
- **行動**：
  1. 在 `compatibility()` 方法中加入版本判斷與遷移呼叫：
     ```php
     // 1.1.0 之後移除 exclude_main_course，遷移銷售方案商品數量
     if (version_compare($previous_version, '1.1.0', '<=')) {
         BundleProduct::migrate_exclude_main_course();
     }
     ```
  2. 放在現有 `0.11.0` 遷移之後
- **原因**：利用既有的版本遷移機制，確保只執行一次
- **依賴**：步驟 8
- **風險**：低

### 第四階段：前端 — Types 與 Atom

#### 10. **更新 TypeScript 類型**（檔案：`js/src/components/product/ProductTable/types/index.ts`）
- **行動**：
  1. 在 `TProductRecord` 中新增：`pbp_product_quantities: Record<string, number>`
  2. 移除：`exclude_main_course: 'yes' | 'no' | ''`
- **原因**：TypeScript 類型需要與 API 回應對齊
- **依賴**：步驟 2
- **風險**：低

#### 11. **擴充 atom 類型**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`）
- **行動**：
  1. 定義新類型 `TSelectedProduct`：`TBundleProductRecord & { qty: number }`
  2. 修改 `selectedProductsAtom` 為 `atom<TSelectedProduct[]>([])`
- **原因**：每個選中的商品需要攜帶 qty 資訊
- **依賴**：步驟 10
- **風險**：低

### 第五階段：前端 — 核心 UI 改動

#### 12. **重構 BundleForm 移除 exclude_main_course 並新增數量**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`）
- **行動**：
  1. **移除**：
     - 移除 `FiSwitch` import 與使用（約第 13, 232-240 行）
     - 移除 `watchExcludeMainCourse` 變數（約第 46-47 行）
     - 移除 `exclude_main_course` 相關的 CSS class 判斷（約第 245, 261 行）
  2. **目前課程改為可刪除項目**：
     - 目前課程不再特殊顯示在頂部固定位置
     - 改為與其他選中商品一樣出現在 `selectedProducts` 列表中
     - 附帶 `<Tag color="blue">目前課程</Tag>` 標籤以區分
     - 有 `PopconfirmDelete` 刪除按鈕
  3. **新增數量 InputNumber**：
     - 在每個已選商品項目中（約第 326-366 行的 map 迴圈），新增 `<InputNumber>` 元件
     - props：`min={1}`, `max={999}`, `precision={0}`, `defaultValue={1}`, `size="small"`, `style={{ width: 60 }}`
     - onChange 時更新 `selectedProducts` 中對應商品的 `qty`
     - 目前課程項目同樣有數量輸入框
  4. **同步更新表單欄位**：
     - `useEffect` 中（約第 144-166 行），同步 `pbp_product_quantities`：
       ```ts
       const quantities: Record<string, number> = {}
       selectedProducts.forEach(({ id, qty }) => { quantities[id] = qty || 1 })
       bundleProductForm.setFieldValue(['pbp_product_quantities'], JSON.stringify(quantities))
       ```
     - 修改 `pbp_product_ids` 同步邏輯：不再根據 `watchExcludeMainCourse` 判斷，直接使用 `selectedProducts.map(({ id }) => id)`
  5. **新增 hidden form field**：
     - `<Item name={['pbp_product_quantities']} hidden />`
  6. **初始化邏輯**：
     - `initPIdsExcludedCourseId` 改為不排除 courseId（移除 `.filter((id) => id !== courseId)`），而是過濾掉 courseId 後 fetch 其他商品資料
     - 初始化時，如果 record 的 `pbp_product_ids` 包含 courseId，則 course 也要加入 selectedProducts（帶上 qty）
     - 從 record 的 `pbp_product_quantities` 讀取各商品 qty，設定到 selectedProducts 中
- **原因**：核心 UI 改動，實現數量輸入與移除 exclude 功能
- **依賴**：步驟 10, 11
- **風險**：高（最大的前端改動，需仔細測試）

#### 13. **修改 getPrice 加入數量因子**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`）
- **行動**：
  1. 新增常數：`export const PRODUCT_QUANTITIES_FIELD_NAME = 'pbp_product_quantities'`
  2. 修改 `getPrice` 函數簽名：移除 `excludeMainCourse` 參數
  3. 修改計算邏輯：
     ```ts
     // 修改前
     const total = Number(products?.reduce((acc, product) =>
       acc + Number(product?.[type] || product.regular_price), 0
     )) + (excludeMainCourse ? 0 : coursePrice)

     // 修改後（products 已包含 course，每個都有 qty）
     const total = Number(products?.reduce((acc, product) => {
       const price = Number(product?.[type] || product.regular_price || 0)
       const qty = (product as TSelectedProduct).qty || 1
       return acc + price * qty
     }, 0))
     ```
  4. 移除 `course` 參數（不再需要單獨計算課程價格）
- **原因**：組合原價需要乘以各商品數量
- **依賴**：步驟 11
- **風險**：中

#### 14. **修改 EditBundle 組件**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx`）
- **行動**：
  1. 移除 `watchExcludeMainCourse` 相關邏輯（約第 46-47 行）
  2. 修改 `handleOnFinish` 中的驗證：移除 `watchExcludeMainCourse` 條件（約第 65-71 行）
  3. 確保 `pbp_product_quantities` 被包含在 formData 中
- **原因**：配合 BundleForm 變更，移除 exclude 邏輯
- **依賴**：步驟 12
- **風險**：低

#### 15. **修改 ProductPriceFields**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/ProductPriceFields/index.tsx`）
- **行動**：
  1. 確認無 `exclude_main_course` 相關邏輯（目前此檔案未直接使用，但需確認 bundlePrices 的計算是否正確傳入）
  2. 此檔案改動最小，主要是確保 props 型別正確
- **原因**：確保價格顯示正確
- **依賴**：步驟 13
- **風險**：低

#### 16. **修改 CourseBundles 新建方案邏輯**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/index.tsx`，約第 95-111 行）
- **行動**：
  1. 在 `handleCreate` 中新增 `pbp_product_ids: [courseId]`，確保新建方案自動包含目前課程
  2. 新增 `pbp_product_quantities: JSON.stringify({ [courseId]: 1 })`
- **原因**：前端新建方案時也預設帶入目前課程（與後端行為一致的雙重保障）
- **依賴**：無
- **風險**：低

### 第六階段：清理與整合

#### 17. **全域清理 exclude_main_course 殘留引用**
- **行動**：
  1. 全域搜尋 `exclude_main_course` 關鍵字，清除所有殘留引用
  2. 預計需清理的位置：
     - `BundleForm.tsx`（步驟 12 已處理）
     - `types/index.ts`（步驟 10 已處理）
     - `Api/Product.php` format_product（步驟 2 已處理）
     - 若有其他位置，一併清理
- **原因**：移除廢棄功能的所有痕跡
- **依賴**：步驟 2, 10, 12
- **風險**：低

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行。

### E2E 測試（Playwright）

**管理端 E2E：**
1. **新建含數量的銷售方案**：
   - 進入課程編輯 → 銷售方案 tab → 新增銷售方案
   - 確認目前課程已自動帶入（數量 1）
   - 搜尋並加入 T-shirt 商品
   - 修改 T-shirt 數量為 3
   - 確認組合原價自動計算（課程原價×1 + T-shirt原價×3）
   - 儲存方案
   - 重新開啟編輯，確認數量正確回填

2. **編輯既有銷售方案的數量**：
   - 開啟既有銷售方案
   - 修改商品數量
   - 儲存並確認回填正確

3. **移除目前課程**：
   - 開啟銷售方案
   - 刪除目前課程
   - 儲存成功
   - 重新開啟確認目前課程已不在列表中

4. **數量邊界值**：
   - 輸入 0 → 自動修正為 1
   - 輸入 1000 → 自動修正為 999
   - 輸入小數 2.7 → 自動取整為 2
   - 確認 InputNumber 不接受負數

**前台 E2E：**
5. **銷售方案卡片顯示數量**：
   - 瀏覽課程銷售頁
   - 確認數量 > 1 的商品旁邊顯示 ×N
   - 確認數量 = 1 的商品不顯示 ×N

**整合 E2E：**
6. **購買銷售方案後庫存扣除**：
   - 設定銷售方案（課程×2、T-shirt×3）
   - 記錄 T-shirt 初始庫存
   - 模擬購買 1 份
   - 確認 T-shirt 庫存扣除 3

### 測試執行指令

```bash
pnpm run test:e2e:admin       # 管理端 E2E
pnpm run test:e2e:frontend    # 前台 E2E
pnpm run test:e2e:integration # 整合 E2E
pnpm run lint:php             # PHP 品質（PHPCS + PHPStan level 9）
pnpm run lint:ts              # ESLint
pnpm run build                # TypeScript 編譯驗證
```

### 關鍵邊界情況

- 舊版銷售方案（無 `pbp_product_quantities` meta）→ 所有商品數量預設 1，行為與現行一致
- 舊版 `exclude_main_course=yes` 方案 → 遷移後 pbp_product_ids 不變
- 購買多份銷售方案（qty=2）→ line item qty = 2 × 商品數量
- 課程數量 > 1 → 庫存扣 N，但課程只開通 1 次（去重機制）
- `pbp_product_quantities` JSON 損壞 → 靜默降級為預設值 1

## 風險與緩解措施

- **風險**：訂單展開邏輯修改影響既有訂單處理
  - 緩解措施：向下相容設計（無 quantities meta 時 qty=1），不影響舊訂單行為。充分測試 happy path + 舊資料 path

- **風險**：前端 BundleForm 重構幅度較大，可能引入 UI 回歸
  - 緩解措施：分步驟重構（先移除 exclude → 再加 quantity），每步獨立驗證。E2E 覆蓋主要操作流程

- **風險**：遷移腳本可能在大量 bundle products 時效能不佳
  - 緩解措施：使用 Action Scheduler 異步執行（已有機制），逐個處理不批量

- **風險**：前後端 quantities 資料格式不一致（前端 JSON 字串 vs 後端 JSON 物件）
  - 緩解措施：後端統一使用 `json_decode`/`json_encode` 處理，前端統一使用 `JSON.stringify`/`JSON.parse`

## 成功標準

- [ ] 用戶可以在銷售方案編輯畫面中，看到每個已選商品旁邊有「數量」InputNumber
- [ ] 數量欄位預設值為 1，最小值 1，最大值 999，僅接受正整數
- [ ] 「排除目前課程」開關已移除，目前課程顯示為可刪除的商品項目
- [ ] 新建銷售方案時，自動帶入目前課程 ×1
- [ ] 修改數量後，組合原價自動重新計算（商品單價 × 數量的總和）
- [ ] 儲存銷售方案後，數量資料正確保存（pbp_product_quantities meta）
- [ ] 重新開啟編輯時，數量正確回填
- [ ] 前台課程銷售頁的銷售方案卡片中，數量 > 1 的商品旁邊顯示「×N」
- [ ] 學員購買銷售方案後，各商品 line item qty = 購買份數 × 商品數量
- [ ] 庫存依 line item qty 正確扣除
- [ ] 舊版銷售方案（無 quantities meta）行為不變，視為數量 1
- [ ] 舊版 exclude_main_course=yes 方案遷移後 pbp_product_ids 不變
- [ ] 舊版 exclude_main_course=no 方案遷移後自動補入目前課程 ×1
- [ ] PHP 品質：通過 PHPCS + PHPStan level 9
- [ ] TS 品質：通過 ESLint + TypeScript 編譯
