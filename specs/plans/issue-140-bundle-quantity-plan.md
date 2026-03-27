# 實作計劃：銷售方案商品數量自訂 (Issue #140)

## 概述

讓銷售方案中每個綑綁商品（包含當前課程）可自訂數量，結帳後庫存按數量正確扣減。這是一個 **HOLD SCOPE** 模式的功能擴充，影響 8 個檔案，範圍明確、架構已定。

## 需求重述

1. 後台管理：銷售方案編輯表單中，每個商品旁邊新增數量 InputNumber（最小值 1，預設 1）
2. 資料儲存：新增 `pbp_product_quantities` post meta（JSON: `{"product_id": qty}`）
3. 價格計算：建議售價改為 `sum(price * qty)`
4. 庫存扣減：結帳時 order item quantity = `bundle_order_qty * per_product_qty`
5. 前台顯示：卡片中 qty > 1 時顯示 "x{N}"，qty = 1 時不顯示
6. 向後相容：meta 不存在時所有商品預設數量 1

## 架構變更

| # | 檔案路徑 | 變更類型 | 說明 |
|---|----------|----------|------|
| 1 | `inc/classes/BundleProduct/Helper.php` | 修改 | 新增 `PRODUCT_QUANTITIES_META_KEY` 常數、`get_product_quantities()` / `set_product_quantities()` 方法 |
| 2 | `inc/classes/Api/Product.php` | 修改 | `handle_special_fields()` 處理 quantities JSON；`format_product_details()` 輸出 quantities；create/update 驗證 qty >= 1 |
| 3 | `inc/classes/Resources/Order.php` | 修改 | `_handle_add_course_item_meta_by_order()` 中 `$qty` 乘以 per_product_quantity |
| 4 | `inc/templates/components/card/bundle-product.php` | 修改 | 迴圈中讀取 quantities，qty > 1 時顯示 "x{N}" |
| 5 | `js/src/components/product/ProductTable/types/index.ts` | 修改 | `TProductRecord` 增加 `pbp_product_quantities` 欄位 |
| 6 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx` | 修改 | 新增 `productQuantitiesAtom` |
| 7 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx` | 修改 | `getPrice()` 加入數量乘法 |
| 8 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx` | 修改 | 加入 InputNumber、同步 quantities 到表單 |

## 資料流分析

### Flow 1: 站長儲存銷售方案

```
BundleForm (React)
  │
  ├─ selectedProducts + productQuantities (Jotai atoms)
  │
  ▼
handleOnFinish() ── toFormData() ──▶ POST /power-course/v1/bundle_products/{id}
                                       │
                                       ▼
                                 sanitize_text_field_deep()
                                       │
                                       ▼
                                 WP::separator() ── meta_data 包含 'pbp_product_quantities'
                                       │
                                       ▼
                                 handle_special_fields()
                                   ├─ pbp_product_ids → update_meta_array (多 row)
                                   └─ pbp_product_quantities → JSON decode → 驗證 qty >= 1 → update_post_meta (單一 row)
                                       │
                                       ▼
                                 save_meta_data()
```

Shadow paths:
- **nil**: `pbp_product_quantities` 未提供 → 不更新此 meta（向後相容）
- **empty**: `pbp_product_quantities` 為空字串或 `{}` → 跳過，保留預設行為
- **error**: JSON decode 失敗 → 回傳 400 錯誤
- **error**: qty <= 0 → 回傳 400 錯誤，訊息 "數量必須大於 0"

### Flow 2: 客戶購買銷售方案（庫存扣減）

```
WooCommerce new_order hook
  │
  ▼
_handle_add_course_item_meta_by_order()
  │
  ├─ 遍歷 order items
  │   └─ 找到 is_bundle_product
  │       │
  │       ▼
  │    helper->get_product_ids()     // 取得綑綁商品列表
  │    helper->get_product_quantities() // 取得各商品數量 (新增)
  │       │
  │       ▼
  │    foreach included_product_id:
  │       per_qty = quantities[product_id] ?? 1  // 向後相容
  │       total_qty = order_item_qty * per_qty
  │       order->add_product(product, total_qty, ...)
  │       │
  │       ▼
  │    order->save()
  │
  ▼
WooCommerce 自動依 order item quantity 扣減庫存
```

Shadow paths:
- **nil**: `pbp_product_quantities` meta 不存在（舊方案）→ 每個商品 qty 預設 1
- **nil**: included_product_id 不在 quantities map 中 → qty 預設 1
- **error**: wc_get_product 回傳 false → continue（已有處理）

### Flow 3: 前台卡片顯示

```
bundle-product.php 模板
  │
  ▼
helper->get_product_quantities()
  │
  ▼
foreach pbp_product_id:
  per_qty = quantities[product_id] ?? 1
  │
  ├─ per_qty > 1 → 顯示 "x{N}" 標示在商品名稱旁
  └─ per_qty = 1 → 不顯示數量標示（維持現行外觀）
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|----------|----------|------------|
| `handle_special_fields()` quantities JSON decode | 非法 JSON | ValueError | 回傳 400 + 錯誤訊息 | 是（API 回應） |
| `handle_special_fields()` qty 驗證 | qty <= 0 | ValidationError | 回傳 400 + "數量必須大於 0" | 是（API 回應） |
| `get_product_quantities()` meta 不存在 | 舊資料 | null | 回傳空陣列，呼叫端 fallback 1 | 否（靜默） |
| `_handle_add_course_item_meta_by_order()` | product_id 不在 quantities | KeyError | fallback qty=1 | 否（靜默） |
| BundleForm InputNumber | 使用者輸入 0 或負數 | Frontend validation | Ant Design min={1} 阻擋 + API 雙重驗證 | 是（表單阻擋） |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|----------|---------|---------|------------|----------|
| API create/update | 非法 JSON quantities | 將處理 | 將測試 | 是 | 顯示錯誤訊息 |
| API create/update | qty=0 | 將處理 | 將測試 | 是 | 顯示錯誤訊息 |
| Order item creation | meta 不存在（舊方案） | 將處理 | 將測試 | 否 | fallback qty=1 |
| 前台卡片 | quantities key 缺漏 | 將處理 | 將測試 | 否 | fallback qty=1 |
| 前端 getPrice | quantities undefined | 將處理 | 將測試 | 否 | fallback qty=1 |

## 實作步驟

### 第一階段：後端基礎設施（PHP）

> 執行 Agent: `@wp-workflows:wordpress-master`

**1.1 Helper.php -- 新增 quantities 常數與存取方法**（檔案：`inc/classes/BundleProduct/Helper.php`）

- 行動：
  1. 新增常數 `const PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities';`
  2. 新增 `get_product_quantities(): array<string, int>` 方法：
     - 讀取 `get_post_meta($id, self::PRODUCT_QUANTITIES_META_KEY, true)`
     - 若為空/不存在，回傳空陣列 `[]`
     - 若為字串，`json_decode` 並回傳
     - 若 decode 失敗，回傳空陣列
  3. 新增 `set_product_quantities(array $quantities): void` 方法：
     - 驗證所有 value >= 1
     - `update_post_meta($id, self::PRODUCT_QUANTITIES_META_KEY, wp_json_encode($quantities))`
  4. 新增 `get_product_quantity(string $product_id): int` 便利方法：
     - 回傳 `$this->get_product_quantities()[$product_id] ?? 1`
- 原因：封裝 quantities 的讀寫邏輯，所有使用處透過 Helper 存取
- 依賴：無
- 風險：低

**1.2 Api/Product.php -- handle_special_fields 處理 quantities**（檔案：`inc/classes/Api/Product.php`）

- 行動：
  1. 在 `handle_special_fields()` 方法中，於 `$update_array_meta_keys` 處理之後，新增 `pbp_product_quantities` 的專門處理邏輯：
     - 檢查 `$meta_data` 中是否包含 `'pbp_product_quantities'`
     - 若有，`json_decode` 字串值
     - 驗證每個 value 為正整數 (>= 1)，若驗證失敗丟出 `\WP_Error`（或用 WP REST 標準錯誤回傳）
     - 呼叫 `Helper::instance($product)->set_product_quantities($quantities)`
     - 從 `$meta_data` 中 unset 此 key（避免後續 loop 重複寫入）
  2. 注意：create (`post_bundle_products_callback`) 和 update (`post_bundle_products_with_id_callback`) 都經過 `handle_special_fields()`，所以兩者都會自動支援
- 原因：API 層負責驗證與轉換，確保存入 DB 的資料正確
- 依賴：步驟 1.1
- 風險：中（需確認 `sanitize_text_field_deep` 對 JSON 字串的處理，JSON 中的引號可能被 sanitize 影響）

> **風險緩解**：`pbp_product_quantities` 的值是由前端 `toFormData()` 序列化為字串後送出。`sanitize_text_field_deep` 會對字串做 `sanitize_text_field`，但 JSON 字串本身只包含數字和引號，不會被 sanitize 破壞。需在測試中驗證此行為。

**1.3 Api/Product.php -- format_product_details 輸出 quantities**（檔案：`inc/classes/Api/Product.php`）

- 行動：
  1. 在 `format_product_details()` 方法的 `$base_array` 中，`Helper::INCLUDE_PRODUCT_IDS_META_KEY` 那行之後，新增：
     ```php
     Helper::PRODUCT_QUANTITIES_META_KEY => ( $helper !== null ? $helper->get_product_quantities() : [] ),
     ```
  2. 這樣 GET API 回傳的 product 資料中會包含 `pbp_product_quantities` 欄位
- 原因：前端需要讀取既有銷售方案的 quantities 資料
- 依賴：步驟 1.1
- 風險：低

### 第二階段：訂單庫存扣減（PHP）

> 執行 Agent: `@wp-workflows:wordpress-master`

**2.1 Resources/Order.php -- 依商品數量扣減庫存**（檔案：`inc/classes/Resources/Order.php`）

- 行動：
  1. 在 `_handle_add_course_item_meta_by_order()` 方法中，修改銷售方案處理邏輯
  2. 現行程式碼（第 113-133 行）：
     ```php
     $included_product_ids = $helper?->get_product_ids() ?: [];
     foreach ( $included_product_ids as $included_product_id ) {
         // ...
         $qty = $item->get_quantity() ?: 1;
         $order->add_product( $included_product, $qty, [...] );
     }
     ```
  3. 修改為：
     ```php
     $included_product_ids = $helper?->get_product_ids() ?: [];
     $product_quantities   = $helper?->get_product_quantities() ?: [];
     foreach ( $included_product_ids as $included_product_id ) {
         // ...
         $per_product_qty = (int) ($product_quantities[ (string) $included_product_id ] ?? 1);
         $order_qty       = $item->get_quantity() ?: 1;
         $qty             = $order_qty * $per_product_qty;
         $order->add_product( $included_product, $qty, [...] );
     }
     ```
  4. 移除原有的 `// TODO: 應該也要記錄數量` 註解（第 127 行）
- 原因：實現 Gherkin 規格中的庫存扣減邏輯（購買 1 份方案，各商品扣 per_product_qty；購買 N 份，扣 N * per_product_qty）
- 依賴：步驟 1.1
- 風險：高（影響結帳金流，必須有 E2E 測試覆蓋）

> **風險緩解**：向後相容設計（`?? 1`）確保舊方案不受影響。E2E 測試需覆蓋：新方案購買、舊方案購買、多份購買三種情境。

### 第三階段：前台顯示（PHP）

> 執行 Agent: `@wp-workflows:wordpress-master`

**3.1 bundle-product.php -- 卡片顯示數量標示**（檔案：`inc/templates/components/card/bundle-product.php`）

- 行動：
  1. 在 `$pbp_product_ids` 賦值之後（第 31 行後），新增讀取 quantities：
     ```php
     $product_quantities = $helper?->get_product_quantities() ?? [];
     ```
  2. 修改 foreach 迴圈（第 85-99 行），在 `load_template('course-product/list', ...)` 呼叫中傳入 quantity：
     ```php
     foreach ( $pbp_product_ids as $pbp_product_id ) :
         if (!is_numeric($pbp_product_id)) { continue; }
         $pbp_product = \wc_get_product( $pbp_product_id );
         $per_qty = (int) ( $product_quantities[ (string) $pbp_product_id ] ?? 1 );
         echo '<div class="flex items-center gap-2">';
         echo '<div class="flex-1">';
         Plugin::load_template('course-product/list', ['product' => $pbp_product]);
         echo '</div>';
         if ( $per_qty > 1 ) {
             printf( '<span class="text-sm font-semibold text-primary whitespace-nowrap">x%d</span>', $per_qty );
         }
         echo '</div>';
         Plugin::load_template( 'divider' );
     endforeach;
     ```
  3. 使用 flex 佈局讓 "x{N}" 標示出現在商品行的右側
- 原因：讓前台客戶看到每個商品包含幾個，提升透明度
- 依賴：步驟 1.1
- 風險：低

### 第四階段：前端型別與狀態（TypeScript）

> 執行 Agent: `@wp-workflows:react-master`

**4.1 types/index.ts -- 增加 quantities 型別**（檔案：`js/src/components/product/ProductTable/types/index.ts`）

- 行動：
  1. 在 `TProductRecord` 中（`pbp_product_ids: string[]` 之後）新增：
     ```typescript
     pbp_product_quantities: Record<string, number>
     ```
- 原因：確保前端型別安全地存取 quantities
- 依賴：無
- 風險：低

**4.2 atom.tsx -- 新增 productQuantitiesAtom**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`）

- 行動：
  1. 新增 atom：
     ```typescript
     export const productQuantitiesAtom = atom<Record<string, number>>({})
     ```
  2. 此 atom 儲存 `{ [productId]: quantity }` 映射
- 原因：BundleForm 中的商品數量狀態需要在多個元件間共享（InputNumber、getPrice、表單同步）
- 依賴：無
- 風險：低

**4.3 utils/index.tsx -- getPrice 加入數量乘法**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`）

- 行動：
  1. `getPrice` 函式簽名增加 `quantities?: Record<string, number>` 參數
  2. 新增常數 `PRODUCT_QUANTITIES_FIELD_NAME = 'pbp_product_quantities'`
  3. 修改計算邏輯：
     ```typescript
     // 原本
     products?.reduce((acc, product) =>
         acc + Number(product?.[type] || product.regular_price), 0)

     // 修改為
     products?.reduce((acc, product) => {
         const qty = quantities?.[String(product.id)] ?? 1
         return acc + Number(product?.[type] || product.regular_price) * qty
     }, 0)
     ```
  4. 課程價格也要乘以數量：
     ```typescript
     const courseQty = quantities?.[String(course?.id)] ?? 1
     const coursePriceTotal = excludeMainCourse ? 0 : coursePrice * courseQty
     ```
- 原因：建議售價需即時反映數量變動
- 依賴：步驟 4.2
- 風險：低

### 第五階段：前端表單整合（TypeScript）

> 執行 Agent: `@wp-workflows:react-master`

**5.1 BundleForm.tsx -- 加入 InputNumber 與 quantities 同步**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`）

- 行動：
  1. import `InputNumber` from `antd` 和 `productQuantitiesAtom` from `./atom`
  2. import `PRODUCT_QUANTITIES_FIELD_NAME` from `./utils`
  3. 在元件內使用 `useAtom(productQuantitiesAtom)` 取得 `[quantities, setQuantities]`
  4. 新增隱藏表單欄位：
     ```tsx
     <Item name={[PRODUCT_QUANTITIES_FIELD_NAME]} hidden />
     ```
  5. **當前課程區域**（第 243-258 行的 div）：在 `<Tag color="blue">目前課程</Tag>` 旁邊增加 InputNumber：
     ```tsx
     {!watchExcludeMainCourse && (
         <InputNumber
             min={1}
             value={quantities[String(courseId)] ?? 1}
             onChange={(val) => {
                 setQuantities(prev => ({
                     ...prev,
                     [String(courseId)]: val ?? 1,
                 }))
             }}
             size="small"
             style={{ width: 60 }}
         />
     )}
     ```
  6. **已選商品列表**（第 325-366 行的 map）：在每個商品的 PopconfirmDelete 前面加入 InputNumber：
     ```tsx
     <InputNumber
         min={1}
         value={quantities[String(id)] ?? 1}
         onChange={(val) => {
             setQuantities(prev => ({
                 ...prev,
                 [String(id)]: val ?? 1,
             }))
         }}
         size="small"
         style={{ width: 60 }}
     />
     ```
  7. 修改 `useEffect` 同步邏輯（第 144-166 行）：
     - 在同步 `pbp_product_ids` 後，也同步 `pbp_product_quantities` 到表單：
       ```typescript
       const quantitiesForForm: Record<string, number> = {}
       const allProductIds = watchExcludeMainCourse
           ? selectedProducts.map(({ id }) => id)
           : [courseId, ...selectedProducts.map(({ id }) => id)]
       allProductIds.forEach(pid => {
           quantitiesForForm[String(pid)] = quantities[String(pid)] ?? 1
       })
       bundleProductForm.setFieldValue(
           [PRODUCT_QUANTITIES_FIELD_NAME],
           JSON.stringify(quantitiesForForm)
       )
       ```
     - `getPrice` 呼叫增加 `quantities` 參數
  8. 初始化 quantities：在 `useEffect(() => { if (!initIsFetching) ... }, [initIsFetching])` 中，從 `record?.pbp_product_quantities` 初始化 quantities atom：
     ```typescript
     useEffect(() => {
         if (!initIsFetching) {
             setSelectedProducts(includedProducts)
             // 初始化 quantities
             const initQuantities = record?.pbp_product_quantities ?? {}
             setQuantities(initQuantities)
         }
     }, [initIsFetching])
     ```
  9. useEffect 依賴需更新：同步 effect 除了 `selectedProducts.length` 和 `watchExcludeMainCourse` 外，還要加入 `quantities` 的變化：
     ```typescript
     useEffect(() => {
         // ...同步邏輯
     }, [selectedProducts.length, watchExcludeMainCourse, quantities])
     ```
  10. 新增商品時自動設定預設數量：在 `handleClick` 的「加入」分支中：
      ```typescript
      setSelectedProducts([...selectedProducts, product])
      setQuantities(prev => ({
          ...prev,
          [String(product.id)]: 1,
      }))
      ```
  11. 移除商品時清除數量：在 `handleClick` 的「移除」分支中：
      ```typescript
      setSelectedProducts(selectedProducts.filter(({ id }) => id !== product.id))
      setQuantities(prev => {
          const next = { ...prev }
          delete next[String(product.id)]
          return next
      })
      ```
  12. `bundlePrices` 計算也要傳入 `quantities`
- 原因：核心 UI 變更，讓站長可以為每個商品設定數量
- 依賴：步驟 4.1, 4.2, 4.3
- 風險：中（涉及多處狀態同步，需確保 quantities atom 與 selectedProducts atom 始終一致）

> **風險緩解**：在 handleClick 的加入/移除兩端同步更新 quantities，確保 atom 一致性。useEffect 同步 form value 時，只取 allProductIds 中存在的 key，避免殘留舊 key。

**5.2 Edit/index.tsx -- 傳遞 quantities 初始值**（檔案：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx`）

- 行動：
  1. 在 `handleOnFinish()` 中，確保 `pbp_product_quantities` 被包含在 form values 中
  2. 由於 `pbp_product_quantities` 已透過隱藏欄位存在 form 中，`toFormData(formattedValues)` 會自動序列化
  3. 無需額外修改，但需驗證 `toFormData` 對 JSON 字串值的處理是否正確
- 原因：確保 quantities 被正確送出到 API
- 依賴：步驟 5.1
- 風險：低

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行

### E2E 測試（Playwright）

測試執行指令：`pnpm run test:e2e:admin` / `pnpm run test:e2e:frontend` / `pnpm run test:e2e:integration`

**管理端（`tests/e2e/01-admin/`）**：

1. **bundle-quantity-create.spec.ts**
   - 建立含自訂數量的銷售方案，驗證 API 回傳正確的 `pbp_product_quantities`
   - 建立未指定數量的銷售方案，驗證預設為 1
   - 嘗試數量為 0，驗證前端 InputNumber 阻擋

2. **bundle-quantity-edit.spec.ts**
   - 編輯既有銷售方案的商品數量，驗證儲存後回傳正確值
   - 開啟舊格式銷售方案（無 quantities），驗證所有商品顯示數量 1
   - 修改數量後驗證建議售價即時更新

3. **bundle-quantity-price.spec.ts**
   - 驗證建議售價 = sum(price * qty)
   - 驗證排除主課程時不計入主課程價格
   - 驗證課程數量也影響建議售價

**前台（`tests/e2e/02-frontend/`）**：

4. **bundle-quantity-display.spec.ts**
   - 銷售方案卡片中 qty > 1 時顯示 "x{N}"
   - qty = 1 時不顯示數量標示
   - 舊方案不顯示數量標示

**整合（`tests/e2e/03-integration/`）**：

5. **bundle-quantity-stock.spec.ts**
   - 購買 1 份方案，驗證庫存按 per_product_qty 扣減
   - 購買 2 份方案，驗證庫存按 order_qty * per_product_qty 扣減
   - 購買舊方案（無 quantities），驗證每商品扣 1
   - 驗證訂單中的 order item 數量正確

### 關鍵邊界情況

- JSON 字串經過 `sanitize_text_field_deep` 後是否完整
- `toFormData()` 序列化 JSON 字串到 multipart/form-data 的行為
- 商品移除後 quantities map 中的殘留 key
- 當前課程的 quantity 在 exclude_main_course 切換時的行為
- 超大數量值（如 99999）的 UI 顯示
- 在 quantities atom 更新觸發 getPrice 重算時的效能

## 風險與緩解措施

- **高風險**：庫存扣減邏輯修改（影響結帳金流）
  - 緩解：向後相容設計（`?? 1`），E2E 整合測試覆蓋新舊方案
  - 緩解：修改範圍僅限 `_handle_add_course_item_meta_by_order()` 的一個迴圈內

- **中風險**：`sanitize_text_field_deep` 可能破壞 JSON 字串
  - 緩解：JSON 字串只包含數字和引號，`sanitize_text_field` 不會移除這些字元
  - 緩解：在 `handle_special_fields` 中做 `json_decode` 失敗檢查

- **中風險**：前端 quantities atom 與 selectedProducts atom 不同步
  - 緩解：在 handleClick 的加入/移除兩端同步更新，useEffect 中只取有效 product_ids 的 key

- **低風險**：前台 PHP 模板的 HTML 結構變更可能影響現有 CSS
  - 緩解：使用 flex 佈局包裹，不改動 `course-product/list.php` 模板本身

## 限制條件

- 此計劃**不**修改 WooCommerce 核心的庫存扣減邏輯（依賴 WC 自動依 order item qty 扣庫存）
- 此計劃**不**修改 `course-product/list.php` 模板（數量標示在外層包裹）
- 此計劃**不**新增資料庫 migration（使用 post meta，無需 DDL 變更）
- 此計劃**不**處理 "庫存不足" 的前端提示（WooCommerce 已有內建機制）
- 此計劃**不**處理 variable product 或 grouped product 的數量（僅支援 simple 和 subscription）

## 預估複雜度：中

影響 8 個檔案，核心邏輯變更集中在 3 個方法（`handle_special_fields`、`_handle_add_course_item_meta_by_order`、`getPrice`），其餘為資料傳遞與 UI 呈現。最大風險點在庫存扣減，但修改範圍明確且向後相容。

## 成功標準

- [ ] 後台：銷售方案編輯表單中，每個商品（含當前課程）旁有 InputNumber，預設 1
- [ ] 後台：建議售價即時反映 price * qty 的計算
- [ ] API：create/update 正確儲存 `pbp_product_quantities` meta
- [ ] API：qty <= 0 時回傳 400 錯誤
- [ ] 前台：卡片中 qty > 1 顯示 "x{N}"，qty = 1 不顯示
- [ ] 結帳：庫存按 order_qty * per_product_qty 正確扣減
- [ ] 向後相容：舊銷售方案（無 quantities meta）正常運作，所有商品預設 qty = 1
- [ ] lint:php 通過（PHPCS + PHPStan level 9）
- [ ] lint:ts 通過（ESLint + TypeScript strict mode）
- [ ] build 通過
