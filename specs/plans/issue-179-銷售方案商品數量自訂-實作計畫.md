# 實作計畫：銷售方案商品數量自訂（Issue #179）

## 概述

讓銷售方案（Bundle Product）中每個商品可自訂數量（1~999），同時將「當前課程」重構為與其他商品一致的邏輯。數量影響原價計算、前台展示、訂單明細與庫存扣減。

## 範圍模式

**HOLD SCOPE** — 此為既有功能的增強，影響範圍約 12 個檔案，無需縮減。

## 需求摘要

| 決策 | 結果 |
|------|------|
| Q1 儲存格式 | 新增 `pbp_product_quantities` JSON meta，不動 `pbp_product_ids` |
| Q2 原價計算 | 原價 = Σ(商品單價 × 數量) |
| Q3 前台顯示 | qty > 1 顯示 ×N，qty = 1 不顯示 |
| Q4 當前課程 | 重構為統一邏輯：自動添加 ×1、可移除、可搜尋 |
| Q5 數量上限 | 999 |
| Q6 訂單顯示 | 名稱含 ×N（`方案 - 講義 ×3` × 1），WC qty = 購買份數 |
| Q7 多份庫存 | 購買份數 × 方案內數量 |

## 架構變更

### 新增
- `pbp_product_quantities` post meta（JSON: `{"product_id": qty}`）
- `_pbp_qty` order item meta（記錄方案內商品數量）
- 庫存扣減 hook（`woocommerce_order_item_get_quantity` 或 `woocommerce_reduce_order_stock`）
- 資料遷移程式碼（`exclude_main_course` → 統一 `pbp_product_ids`）

### 修改
- `BundleProduct/Helper.php` — 新增 `get_product_quantities()` / `set_product_quantities()` 方法
- `Api/Product.php` — API 讀寫 `pbp_product_quantities`；移除 `exclude_main_course`
- `Resources/Order.php` — 訂單品項名稱含 ×N、記錄 `_pbp_qty`、自訂庫存扣減
- `bundle-product.php` 模板 — 前台顯示 ×N
- `course-product/list.php` 模板 — 接收並顯示數量
- `BundleForm.tsx` — 移除 `exclude_main_course` 開關，新增數量輸入框，自動添加當前課程
- `utils/index.tsx` — `getPrice()` 乘以數量
- `atom.tsx` — 新增 `productQuantitiesAtom`
- `types/index.ts` — 新增 `pbp_product_quantities` 型別
- `Compatibility.php` — 新增遷移邏輯
- `Duplicate.php` — 複製時一併複製 `pbp_product_quantities`

### 移除（deprecated）
- `exclude_main_course` 前端 UI 開關
- `exclude_main_course` API 返回值（保留讀取但不再使用）

## 資料流分析

### 銷售方案儲存流程

```
前端表單提交
  │
  ▼
POST /bundle_products/:id  (form-data)
  │
  ├─ pbp_product_ids: [100, 200, 201]
  ├─ pbp_product_quantities: {"100":1,"200":3,"201":2}
  │
  ▼
WP::separator() → meta_data
  │
  ▼
handle_special_fields()
  │
  ├─ pbp_product_ids → WcProduct::update_meta_array() (repeating meta)
  ├─ pbp_product_quantities → update_meta_data() (single JSON meta)
  │
  ▼
product->save_meta_data()
  │
  ├─ [nil?] pbp_product_quantities 不存在 → 所有商品預設 qty=1
  ├─ [empty?] JSON 為 {} → 所有商品預設 qty=1
  ├─ [error?] JSON 解析失敗 → 忽略，使用預設 qty=1
  ▼
回傳 BundleProduct schema（含 pbp_product_quantities）
```

### 訂單處理流程

```
學員結帳（購買 N 份銷售方案）
  │
  ▼
woocommerce_new_order hook
  │
  ▼
Order::_handle_add_course_item_meta_by_order()
  │
  ├─ 遍歷 order items，識別 bundle product
  │
  ▼
Helper::get_product_quantities()  ← 新方法
  │
  ├─ 取得 {"100":1, "200":3, "201":2}
  │
  ▼
foreach included_product:
  │
  ├─ $pbp_qty = quantities[$product_id] ?? 1
  ├─ $name = "{方案名稱} - {商品名稱}" + ($pbp_qty > 1 ? " ×{$pbp_qty}" : "")
  │
  ▼
$order->add_product($included_product, $cart_qty, [
    'name'     => $name,
    'subtotal' => 0,
    'total'    => 0,
])
  │
  ▼
$item->update_meta_data('_pbp_qty', $pbp_qty)
  │
  ▼
order->save()
  │
  ├─ [nil?] quantities meta 不存在 → pbp_qty=1（向下相容）
  ├─ [error?] 商品不存在 → skip, continue
  ▼
庫存扣減（woocommerce_reduce_order_stock hook）
  │
  ├─ 讀取 _pbp_qty meta
  ├─ 實際扣減 = WC item qty × _pbp_qty
  │
  ├─ [nil?] _pbp_qty 不存在 → 等同 1（向下相容）
  ▼
庫存更新完成
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `Helper::get_product_quantities()` | meta 不存在（舊方案） | nil path | 回傳空陣列，所有商品 qty=1 | 否 |
| `Helper::get_product_quantities()` | JSON 解析失敗 | error path | 回傳空陣列，fallback qty=1 | 否 |
| `handle_special_fields()` 儲存 quantities | quantities 值不在 1~999 | validation | PHP 端 clamp 到 [1, 999] | 否 |
| `Order` 加入品項 | included_product 不存在 | nil path | skip continue（現有行為） | 否 |
| 庫存扣減 hook | `_pbp_qty` meta 不存在 | nil path | fallback 1（向下相容） | 否 |
| 前端 `getPrice()` | product 價格為 null/NaN | nil path | fallback 0（現有行為） | 否 |
| 前端數量輸入 | 輸入 0/負數/小數/文字 | validation | InputNumber min=1 max=999 precision=0 | 是（自動修正） |
| 遷移 `exclude_main_course` | 課程商品不存在 | nil path | skip，不加入 pbp_product_ids | 否（靜默） |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|---------|---------|-----------|---------|
| 遷移程式碼 | 遷移中途失敗 | 是 | 待建 | 否 | 下次升級重跑（冪等） |
| 訂單庫存扣減 | _pbp_qty 遺失 | 是 | 待建 | 否 | fallback qty=1 |
| 前台模板 | quantities JSON 損壞 | 是 | 待建 | 否 | 顯示預設（不帶 ×N） |
| 前端表單 | 舊方案無 quantities | 是 | 待建 | 否 | 全部回顯 1 |

---

## 實作步驟

### 第一階段：後端資料層（Helper + API）

#### 1.1 擴充 `BundleProduct/Helper.php`
**檔案**：`inc/classes/BundleProduct/Helper.php`

**行動**：
- 新增常數 `PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities'`
- 新增方法 `get_product_quantities(): array<string, int>` — 讀取 JSON meta，解析後回傳 `{"product_id": qty}` 關聯陣列。若 meta 不存在或解析失敗，回傳空陣列
- 新增方法 `get_product_qty(int $product_id): int` — 取得單一商品的數量，預設 1
- 新增方法 `set_product_quantities(array $quantities): void` — 儲存前驗證每個值 clamp 到 [1, 999]，以 JSON 字串寫入 meta
- 現有的 `get_product_ids()` 不動，維持向下相容

**原因**：資料層是所有後續步驟的基礎，必須先完成

**依賴**：無

**風險**：低 — 純新增方法，不修改既有邏輯

---

#### 1.2 修改 API 讀取 `pbp_product_quantities`
**檔案**：`inc/classes/Api/Product.php`

**行動**：
- 在 `format_product_details()` 方法中（約 L580 附近），新增一行回傳 `pbp_product_quantities`：
  ```php
  Helper::PRODUCT_QUANTITIES_META_KEY => ($helper !== null ? $helper->get_product_quantities() : []),
  ```
  放在 `Helper::INCLUDE_PRODUCT_IDS_META_KEY` 的下一行
- `exclude_main_course` 繼續回傳但標記為 deprecated（保留向下相容，避免前端未更新時報錯）

**原因**：前端需要讀取 quantities 資料來回顯

**依賴**：步驟 1.1

**風險**：低

---

#### 1.3 修改 API 寫入 `pbp_product_quantities`
**檔案**：`inc/classes/Api/Product.php`

**行動**：
- 在 `handle_special_fields()` 方法中（約 L917），新增對 `pbp_product_quantities` 的處理邏輯：
  ```php
  // 在 $update_array_meta_keys 處理之後，新增 JSON meta 處理
  if (isset($meta_data[Helper::PRODUCT_QUANTITIES_META_KEY])) {
      $quantities = $meta_data[Helper::PRODUCT_QUANTITIES_META_KEY];
      // 如果是字串（form-data 傳來），嘗試 JSON decode
      if (is_string($quantities)) {
          $quantities = json_decode($quantities, true) ?: [];
      }
      // clamp 每個值到 [1, 999]
      $sanitized = [];
      foreach ($quantities as $pid => $qty) {
          $sanitized[(string)$pid] = max(1, min(999, (int)$qty));
      }
      $product->update_meta_data(Helper::PRODUCT_QUANTITIES_META_KEY, wp_json_encode($sanitized));
      unset($meta_data[Helper::PRODUCT_QUANTITIES_META_KEY]);
  }
  ```
- 此處理邏輯放在 `handle_special_fields()` 中的 `$update_array_meta_keys` 迴圈之後、`$unset_meta_keys` 迴圈之前

**原因**：確保 API 寫入時正確清理並持久化 quantities JSON

**依賴**：步驟 1.1

**風險**：低 — 新增的 if block 不影響既有邏輯

---

### 第二階段：當前課程重構

#### 2.1 移除 `exclude_main_course` 前端邏輯
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

**行動**：
- 移除 `FiSwitch` 組件（`exclude_main_course` 開關，約 L232-240）
- 移除 `watchExcludeMainCourse` 變數及其相關邏輯
- 移除靜態的「當前課程方案」區塊（L243-258），因為當前課程將作為普通商品出現在列表中
- 修改商品搜尋的 `filters`，移除 `exclude: [courseId]` 過濾條件（L68-72），允許搜尋到當前課程
- 但搜尋結果仍然排除已選商品（用前端 filter，非 API exclude）

**原因**：Q4 決策 — 當前課程重構為統一邏輯

**依賴**：步驟 1.2（API 需要返回 quantities）

**風險**：中 — 影響表單的核心邏輯，需要仔細測試

---

#### 2.2 自動添加當前課程
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

**行動**：
- 修改初始化邏輯：當 `record` 為新建（無 id）時，自動將當前課程添加到 `selectedProducts`，數量為 1
- 修改 `initPIdsExcludedCourseId` 邏輯：不再過濾 courseId，直接使用全部 `pbp_product_ids`
- 修改 `useEffect` 中的 productIds 同步邏輯：直接從 `selectedProducts` 取得 IDs（不再區分 exclude/include main course）

**原因**：新建方案時預設包含當前課程，與其他商品行為一致

**依賴**：步驟 2.1

**風險**：中

---

#### 2.3 後端遷移 `exclude_main_course`
**檔案**：`inc/classes/Compatibility/Compatibility.php`

**行動**：
- 在 `compatibility()` 方法中新增遷移邏輯，使用版本比較（當前版本以上才觸發）
- 新增靜態方法 `migration_bundle_exclude_main_course(): void`：
  1. 查詢所有有 `bundle_type` meta 的產品
  2. 對每個 bundle product：
     - 讀取 `exclude_main_course` meta 值
     - 讀取 `link_course_ids` meta 值（課程 ID）
     - 若 `exclude_main_course` 為 `'no'` 或空：將課程 ID 加入 `pbp_product_ids`（若尚未存在）
     - 若 `exclude_main_course` 為 `'yes'`：不做任何事
  3. 為所有 bundle 初始化 `pbp_product_quantities`（若不存在）：根據 `pbp_product_ids` 產生 `{"id": 1, ...}` JSON
  4. 此遷移必須冪等（多次執行結果相同）

**原因**：確保舊資料無縫升級

**依賴**：步驟 1.1

**風險**：中 — 批量修改資料，需要冪等設計

---

### 第三階段：前端數量輸入框

#### 3.1 新增 `productQuantitiesAtom`
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`

**行動**：
- 新增 `productQuantitiesAtom = atom<Record<string, number>>({})`
- 此 atom 儲存 `{ "product_id": qty }` 的 mapping

**原因**：數量狀態需要與商品選擇狀態分離管理

**依賴**：無

**風險**：低

---

#### 3.2 新增型別定義
**檔案**：`js/src/components/product/ProductTable/types/index.ts`

**行動**：
- 在 `TProductRecord` 中新增：`pbp_product_quantities: Record<string, number>`
- 保留 `exclude_main_course` 欄位但標記 `@deprecated`（向下相容）

**原因**：型別安全

**依賴**：無

**風險**：低

---

#### 3.3 修改 BundleForm 新增數量輸入框
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

**行動**：
- 在已選商品列表的每個商品行（約 L326-366）中，加入 `InputNumber` 組件：
  ```tsx
  <InputNumber
    min={1}
    max={999}
    precision={0}
    value={quantities[id] ?? 1}
    onChange={(val) => {
      const newQty = Math.max(1, Math.min(999, Math.floor(val ?? 1)))
      setQuantities({ ...quantities, [id]: newQty })
    }}
    className="w-20"
  />
  ```
- 在商品搜尋結果中，點擊添加商品時，同時初始化 qty=1 到 `productQuantitiesAtom`
- 在移除商品時，同步清除對應的 qty
- 新增隱藏 Form.Item 用於同步 `pbp_product_quantities` 到表單：
  ```tsx
  <Item name={['pbp_product_quantities']} initialValue={{}} hidden />
  ```
- 在 `useEffect` 中，當 `selectedProducts` 或 `quantities` 改變時，同步更新表單：
  ```tsx
  bundleProductForm.setFieldValue(['pbp_product_quantities'], JSON.stringify(quantities))
  ```

**原因**：核心 UI — 讓管理員設定每個商品的數量

**依賴**：步驟 3.1, 3.2, 2.1, 2.2

**風險**：中 — 涉及表單雙向同步

---

#### 3.4 修改初始化回顯數量
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

**行動**：
- 在 `useEffect` 初始化 `selectedProducts` 時，同步初始化 `productQuantitiesAtom`
- 從 `record.pbp_product_quantities` 讀取初始值
- 若 `record.pbp_product_quantities` 為空或不存在，所有商品預設 qty=1

**原因**：編輯既有方案時需正確回顯數量

**依賴**：步驟 3.3

**風險**：低

---

#### 3.5 修改 `getPrice()` 乘以數量
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`

**行動**：
- 修改 `getPrice()` 函數簽名，新增 `quantities` 參數：`quantities?: Record<string, number>`
- 移除 `excludeMainCourse` 參數（不再需要）
- 修改計算邏輯：
  ```tsx
  const total = products?.reduce(
    (acc, product) => {
      const qty = quantities?.[product.id] ?? 1
      return acc + Number(product?.[type] || product.regular_price) * qty
    },
    0
  ) ?? 0
  ```
- 更新所有呼叫端（BundleForm.tsx 的 `bundlePrices` 計算和 `useEffect` 中的 `setFieldValue`）

**原因**：原價計算必須反映數量（Q2 決策）

**依賴**：步驟 3.3

**風險**：低

---

### 第四階段：訂單處理與庫存扣減

#### 4.1 修改訂單品項名稱與 meta
**檔案**：`inc/classes/Resources/Order.php`

**行動**：
- 修改 `_handle_add_course_item_meta_by_order()` 方法（約 L95-143）
- 在 `$helper?->get_product_ids()` 之後，取得 quantities：
  ```php
  $quantities = $helper->get_product_quantities();
  ```
- 修改 `foreach ($included_product_ids ...)` 迴圈：
  ```php
  $pbp_qty = $quantities[(string) $included_product_id] ?? 1;
  
  // 品項名稱：qty > 1 時顯示 ×N
  $item_name = $product->get_name() . ' - ' . $included_product->get_name();
  if ($pbp_qty > 1) {
      $item_name .= ' ×' . $pbp_qty;
  }
  
  $qty = $item->get_quantity() ?: 1; // 購買份數
  
  $new_item_id = $order->add_product(
      $included_product,
      $qty, // WC qty = 購買份數（非方案內數量）
      [
          'name'     => $item_name,
          'subtotal' => 0,
          'total'    => 0,
      ]
  );
  
  // 記錄方案內數量到 order item meta
  $new_item = $order->get_item($new_item_id);
  if ($new_item instanceof \WC_Order_Item_Product) {
      $new_item->update_meta_data('_pbp_qty', $pbp_qty);
      $new_item->save_meta_data();
  }
  ```

**原因**：Q6 決策 — 名稱含 ×N，WC qty 為購買份數

**依賴**：步驟 1.1

**風險**：高 — 涉及金流相關的訂單處理

---

#### 4.2 自訂庫存扣減
**檔案**：`inc/classes/Resources/Order.php`

**行動**：
- 在 `__construct()` 中新增 hook：
  ```php
  \add_filter('woocommerce_order_item_quantity', [$this, 'adjust_bundle_item_stock_qty'], 10, 3);
  ```
- 新增方法 `adjust_bundle_item_stock_qty($quantity, $order, $item)`：
  ```php
  public function adjust_bundle_item_stock_qty($quantity, $order, $item): int {
      if (!($item instanceof \WC_Order_Item_Product)) {
          return $quantity;
      }
      $pbp_qty = (int) $item->get_meta('_pbp_qty');
      if ($pbp_qty > 1) {
          return $quantity * $pbp_qty;
      }
      return $quantity;
  }
  ```
  - `woocommerce_order_item_quantity` filter 會在 WC 扣減庫存時被調用
  - 這個 filter 修改的是 WC 用來扣減庫存的數量值
  - 效果：2 份銷售方案 × 3 本講義 = 庫存扣 6

**原因**：Q7 決策 — 庫存 = 購買份數 × 方案內數量

**依賴**：步驟 4.1

**風險**：高 — 庫存扣減直接影響營運。需仔細驗證 `woocommerce_order_item_quantity` filter 是否在正確的時機被調用（僅在庫存扣減時，不影響其他邏輯如金額計算）

**注意**：需確認 WooCommerce 中 `woocommerce_order_item_quantity` 這個 filter 的呼叫時機。如果此 filter 也被用於金額計算等場景，則需要加入條件判斷（例如檢查 backtrace 或使用不同的 hook）。替代方案是使用 `woocommerce_reduce_order_stock` action hook，在庫存扣減後手動補扣差額。

---

### 第五階段：前台展示

#### 5.1 修改 `bundle-product.php` 模板
**檔案**：`inc/templates/components/card/bundle-product.php`

**行動**：
- 在 `$pbp_product_ids` 取得之後，取得 quantities：
  ```php
  $quantities = $helper->get_product_quantities();
  ```
- 修改 `foreach ($pbp_product_ids ...)` 迴圈（約 L85-99），傳遞 qty 給子模板：
  ```php
  $pbp_qty = $quantities[(string) $pbp_product_id] ?? 1;
  Plugin::load_template(
      'course-product/list',
      [
          'product' => $pbp_product,
          'qty'     => $pbp_qty,
      ]
  );
  ```

**原因**：Q3 決策 — 前台需顯示數量

**依賴**：步驟 1.1

**風險**：低

---

#### 5.2 修改 `course-product/list.php` 模板
**檔案**：`inc/templates/pages/course-product/list.php`

**行動**：
- 在 `$default_args` 中新增 `'qty' => 1`
- 解構新增 `'qty' => $qty`
- 在商品名稱後方，若 `$qty > 1` 則顯示 ` ×{$qty}`：
  ```php
  $qty_label = ($qty > 1) ? ' ×' . $qty : '';
  // 在 printf 中將 %2$s 改為顯示 $product_name . $qty_label
  ```

**原因**：前台商品列表需標示數量

**依賴**：步驟 5.1

**風險**：低

---

### 第六階段：相容性與收尾

#### 6.1 修改 `Duplicate.php` 複製 quantities
**檔案**：`inc/classes/Utils/Duplicate.php`

**行動**：
- 確認 `process()` 方法中的 meta 複製邏輯是否自動包含 `pbp_product_quantities` meta
- 由於 `process()` 使用 `get_post_meta()` + `add_post_meta()` 複製所有 meta，新的 `pbp_product_quantities` 應該會被自動複製
- 如果遷移後 `pbp_product_ids` 中包含了原課程 ID，複製時需將其替換為新課程 ID

**原因**：課程複製功能完整性

**依賴**：步驟 2.3

**風險**：中 — 需要確認複製邏輯是否正確替換課程 ID

---

#### 6.2 修改 `EditBundle` 組件清理
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx`

**行動**：
- 移除 `watchExcludeMainCourse` 相關邏輯（約 L46-47）
- 修改 `handleOnFinish` 中的驗證邏輯：不再依賴 `watchExcludeMainCourse`（約 L65-69）

**原因**：配合 `exclude_main_course` 的移除

**依賴**：步驟 2.1

**風險**：低

---

#### 6.3 修改 `ProductPriceFields` 組件
**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/ProductPriceFields/index.tsx`

**行動**：
- 確認 `bundlePrices` prop 的計算來源已包含數量（由步驟 3.5 處理）
- 無需修改此組件本身（bundlePrices 由 BundleForm 傳入）

**原因**：確認連接正確

**依賴**：步驟 3.5

**風險**：低

---

## 測試策略

### 整合測試（PHP）

1. **Helper 單元測試**：
   - `get_product_quantities()` 回傳正確 JSON
   - `get_product_quantities()` meta 不存在時回傳空陣列
   - `set_product_quantities()` 正確 clamp 到 [1, 999]
   - `get_product_qty()` 單一商品查詢

2. **API 整合測試**：
   - POST `/bundle_products` 帶 `pbp_product_quantities` 建立成功
   - POST `/bundle_products/:id` 更新 quantities 成功
   - GET bundle product 回傳含 `pbp_product_quantities`
   - 舊方案 GET 回傳 `pbp_product_quantities` 為空（預設 1）

3. **訂單處理測試**：
   - 購買含 quantities 的 bundle，訂單品項名稱含 ×N
   - 購買含 quantities 的 bundle，`_pbp_qty` meta 正確
   - 庫存扣減：1 份 bundle × 3 本講義 → 庫存 -3
   - 庫存扣減：2 份 bundle × 3 本講義 → 庫存 -6
   - 舊方案（無 quantities）→ 庫存 -1（向下相容）

4. **遷移測試**：
   - `exclude_main_course='no'` 遷移後課程 ID 在 `pbp_product_ids` 中
   - `exclude_main_course='yes'` 遷移後課程 ID 不在 `pbp_product_ids` 中
   - 遷移冪等性（執行兩次結果相同）

### E2E 測試（Playwright）

1. **Admin E2E** — `bundle-quantity.spec.ts`：
   - 建立銷售方案時，看到數量輸入框，預設為 1
   - 修改數量為 3，儲存後重新開啟，數量回顯為 3
   - 輸入 0 或空白，自動修正為 1
   - 輸入 1000，自動修正為 999
   - 建立方案時當前課程自動出現，有刪除按鈕
   - 刪除當前課程後，搜尋可重新找到
   - 原價即時反映數量變化

2. **Frontend E2E** — `bundle-quantity-display.spec.ts`：
   - 前台銷售頁，qty > 1 的商品顯示 ×N
   - 前台銷售頁，qty = 1 的商品不顯示 ×1

3. **Integration E2E** — `bundle-quantity-order.spec.ts`：
   - 購買含自訂數量的銷售方案，訂單品項正確
   - 購買後庫存正確扣減

### 測試執行指令
```bash
composer run test                    # PHPUnit
pnpm run test:e2e:admin             # Admin E2E
pnpm run test:e2e:frontend          # Frontend E2E
pnpm run test:e2e:integration       # Integration E2E
```

### 關鍵邊界情況
- 舊方案無 `pbp_product_quantities` meta → 全部預設 1
- `pbp_product_quantities` JSON 損壞 → fallback 空陣列
- 商品已刪除但 ID 仍在 quantities 中 → 自動跳過（現有行為）
- 購買 0 份方案 → WC 自身會阻止
- 方案內商品庫存不足 → WC 加入購物車時會檢查（但注意：WC 檢查的是方案本身的庫存，不是內含商品的庫存。如果需要檢查內含商品庫存，這是一個額外的增強，本期不做）
- 課程複製後，bundle 中的課程 ID 是否正確替換

## 風險與緩解措施

- **風險**：庫存扣減邏輯錯誤（高影響）
  - 緩解措施：`woocommerce_order_item_quantity` filter 僅在 WC 核心的 `wc_reduce_stock_levels()` 中被調用，用於計算庫存扣減量。需通過 WC 原始碼確認此 filter 不會影響訂單金額計算。若有疑慮，替代方案是使用 `woocommerce_reduce_order_stock` action 在扣減完成後手動補差額
  
- **風險**：遷移損壞既有資料
  - 緩解措施：遷移設計為冪等操作；僅 append 資料，不刪除；使用 `version_compare` 確保只跑一次；WP cache flush 後驗證

- **風險**：表單同步問題（前端 atom 與 form 數據不一致）
  - 緩解措施：使用單一數據源（`productQuantitiesAtom`），表單提交時從 atom 讀取最新值並同步到隱藏的 form field

- **風險**：form-data 傳輸 JSON（`pbp_product_quantities` 是物件，但 form-data 只能傳字串）
  - 緩解措施：前端 `JSON.stringify(quantities)` 後傳輸；後端 `handle_special_fields()` 中先 `json_decode()` 再處理

## 成功標準

- [ ] 銷售方案編輯畫面中，每個商品旁有數量輸入框
- [ ] 數量預設 1，可輸入 1~999 正整數
- [ ] 當前課程與其他商品有相同的 UI（數量輸入框、刪除按鈕）
- [ ] 新建方案時自動添加當前課程 ×1
- [ ] 輸入非法值（0、空白、負數、小數）自動修正為 1
- [ ] 儲存後重新編輯，數量正確回顯
- [ ] 既有方案升級後，所有商品數量預設為 1
- [ ] 原價即時反映數量變化（Σ 單價 × 數量）
- [ ] 前台銷售頁 qty > 1 顯示 ×N，qty = 1 不顯示
- [ ] 訂單品項名稱格式：「方案 - 商品 ×N」（N > 1 時）
- [ ] 庫存扣減 = 購買份數 × 方案內數量
- [ ] 課程授權不受數量影響
- [ ] `exclude_main_course` UI 開關已移除
- [ ] 所有 E2E 測試通過
- [ ] `pnpm run lint:php` 通過
- [ ] `pnpm run lint:ts` 通過
- [ ] TypeScript `pnpm run build` 通過
