# 實作計劃：銷售方案商品數量自訂 + 當前課程統一管理

## 概述

本次實作包含兩個緊密關聯的 Feature：
1. **銷售方案商品數量自訂**：管理員可為銷售方案中的每個商品設定 1~999 的數量，影響原價計算、前台顯示、訂單項目與庫存扣減
2. **當前課程統一管理**：將「當前課程」重構為與其他商品一致的邏輯和 UI，移除 `exclude_main_course` 開關

**範圍模式**：HOLD SCOPE — 這是對現有銷售方案功能的增強重構，需求明確，影響約 10 個檔案。

## 需求

- 銷售方案編輯畫面中，每個商品旁有數量輸入框（預設 1，範圍 1~999）
- 當前課程不再有特殊 UI 處理，改為統一列在商品列表中
- 建立新銷售方案時自動加入當前課程（qty=1），可修改數量或移除
- 移除 `exclude_main_course` 開關
- 原價 = Σ(商品單價 × 數量)
- 前台顯示：qty > 1 顯示「×N」，qty = 1 不顯示
- 訂單項目名稱含數量標記（如「超值學習包 - Python 講義 ×3」），WC qty = 1
- 庫存扣減 = 購買份數 × 方案內商品數量
- 向下相容：既有方案無 `pbp_product_quantities` 時，所有商品預設 qty=1

## 架構變更

| # | 檔案 | 變更類型 | 說明 |
|---|------|---------|------|
| 1 | `inc/classes/BundleProduct/Helper.php` | 修改 | 新增 `PRODUCT_QUANTITIES_META_KEY` 常數、`get_product_quantities()` / `set_product_quantities()` / `get_product_qty()` 方法 |
| 2 | `inc/classes/Api/Product.php` | 修改 | API 回應新增 `pbp_product_quantities`；`handle_special_fields()` 處理 quantities 存儲；移除 `exclude_main_course` 回應欄位；新建方案時自動加入當前課程 |
| 3 | `inc/classes/Resources/Order.php` | 修改 | `_handle_add_course_item_meta_by_order()` 讀取 quantities，計算庫存扣減數量、訂單項目名稱加數量標記 |
| 4 | `inc/templates/components/card/bundle-product.php` | 修改 | 前台銷售卡片：qty > 1 時顯示「×N」 |
| 5 | `inc/templates/pages/course-product/list.php` | 修改 | 接受 `qty` 參數，渲染數量標記 |
| 6 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx` | **重構** | 移除 exclude_main_course 開關和特殊課程 UI；當前課程統一為商品列表項；每個商品新增 InputNumber 數量輸入框 |
| 7 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx` | 修改 | `getPrice()` 新增 `quantities` 參數，原價 = Σ(單價 × 數量) |
| 8 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx` | 修改 | 新增 `productQuantitiesAtom` 管理 `{[productId]: qty}` 狀態 |
| 9 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx` | 修改 | 表單提交時序列化 `pbp_product_quantities` |
| 10 | `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/index.tsx` | 修改 | 新建銷售方案時自動帶入 `pbp_product_ids: [courseId]` |
| 11 | `js/src/components/product/ProductTable/types/index.ts` | 修改 | 新增 `pbp_product_quantities` 型別、棄用 `exclude_main_course` |

## 資料流分析

### 管理員編輯銷售方案（寫入流程）

```
FORM INPUT ──▶ VALIDATION ──▶ SERIALIZE ──▶ API CALL ──▶ PERSIST
   │               │              │             │            │
   ▼               ▼              ▼             ▼            ▼
[qty空白?]     [<1或>999?]   [toFormData]  [POST /api]  [update_meta]
→修正為1      →clamp(1,999)  [JSON化qty]  [400錯誤?]   [save失敗?]
```

**Happy Path**: 管理員輸入 qty=3 → 前端 clamp 為 3 → toFormData 序列化 → POST /bundle_products/:id → handle_special_fields 儲存 JSON → 回應 200

**Nil Path**: `pbp_product_quantities` 不存在（舊資料） → `get_product_qty()` 回傳 1（預設值）

**Empty Path**: `pbp_product_ids` 為空 → `pbp_product_quantities` 為 `{}`

**Error Path**: API 回應 400/500 → Refine mutation.onError → Ant Design message.error

### 學員購買銷售方案（庫存扣減流程）

```
ORDER CREATED ──▶ DETECT BUNDLE ──▶ LOAD QUANTITIES ──▶ ADD ITEMS ──▶ STOCK REDUCE
      │                 │                  │                │              │
      ▼                 ▼                  ▼                ▼              ▼
 [無商品?]        [非bundle?]         [無qty meta?]    [商品下架?]    [庫存不足?]
 →skip item       →skip              →預設 qty=1      →skip item    →WC處理
```

**Happy Path**: 訂單含 bundle → 讀取 `pbp_product_quantities` → 每個商品以 `purchase_qty × bundle_qty` 扣庫存

**Nil Path**: 舊方案無 `pbp_product_quantities` → 所有商品 qty=1，行為不變

**Error Path**: 庫存不足 → WooCommerce 原生庫存管理處理（非本次範圍）

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|------------|
| `Helper::get_product_quantities()` | meta 不存在 | nil | 回傳 `[]`，全部預設 qty=1 | 否（靜默） |
| `Helper::set_product_quantities()` | JSON encode 失敗 | runtime | PHP 會拋出 JsonException | 是（500） |
| `BundleForm` qty InputNumber | 輸入非數字/空白/0 | validation | 前端 clamp 為 1 | 是（自動修正） |
| `Order::_handle_add_course_item_meta_by_order()` | 銷售方案商品被刪除 | state | `wc_get_product()` 回傳 false → continue | 否（靜默 skip） |
| `Api/Product::post_bundle_products_callback()` | 缺少必要參數 | validation | WP::include_required_params → 400 | 是（400 error） |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|------------|---------|
| 前端 qty 輸入 | 輸入 0、負數、小數、空白 | ✅ clamp | E2E | 自動修正為 1 | 無需恢復 |
| 前端 qty 輸入 | 輸入 > 999 | ✅ clamp | E2E | 自動修正為 999 | 無需恢復 |
| API 儲存 quantities | JSON 格式錯誤 | ✅ 後端驗證 | IT | 400 錯誤 | 重新送出 |
| 舊方案無 quantities | meta 不存在 | ✅ 預設 1 | IT | 透明相容 | 無需恢復 |
| 訂單處理 qty 乘算 | 整數溢位（999 × 999） | ✅ PHP int 足夠 | - | 否 | 無需恢復 |
| 前台顯示 qty | qty=0 somehow | ✅ max(1, qty) | - | 否 | 無需恢復 |

## 實作步驟

### 第一階段：後端核心（PHP — Helper + API）

#### 1.1 `Helper.php` — 新增數量管理方法

**檔案**：`inc/classes/BundleProduct/Helper.php`

**行動**：
- 新增常數 `PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities'`（約在 line 18）
- 新增 `get_product_quantities(): array<string, int>` 方法
  - 讀取 `get_post_meta($id, self::PRODUCT_QUANTITIES_META_KEY, true)`
  - 如果結果是 JSON string → `json_decode()` 為 associative array
  - 如果結果為空或不存在 → 回傳 `[]`（呼叫端使用 `$qty ?: 1` 取得預設值）
- 新增 `get_product_qty(int $product_id): int` 方法
  - 從 `get_product_quantities()` 取得指定商品的數量
  - 回傳 `max(1, min(999, $qty))`（clamped 到 1~999）
  - 若不存在 → 回傳 `1`
- 新增 `set_product_quantities(array $quantities): void` 方法
  - 驗證每個值為 1~999 整數，不合法的 clamp 到範圍內
  - `update_post_meta($id, self::PRODUCT_QUANTITIES_META_KEY, wp_json_encode($quantities))`

**原因**：所有數量邏輯集中在 Helper 中，其他模組（API、Order、Template）都透過 Helper 存取，確保一致性
**依賴**：無
**風險**：低 — 新增方法，不修改既有方法

#### 1.2 `Api/Product.php` — API 回應與儲存

**檔案**：`inc/classes/Api/Product.php`

**行動**：

**(a) `format_product_details()` — 新增 quantities 回應（約 line 581~590）**
- 在 `Helper::INCLUDE_PRODUCT_IDS_META_KEY` 下方新增：
  ```php
  'pbp_product_quantities' => ($helper !== null ? $helper->get_product_quantities() : []),
  ```
- 移除 `'exclude_main_course'` 欄位（line 586），改為回傳固定值 `'no'` 或直接移除
  - **注意**：為向下相容，暫時保留但標記 `@deprecated`，前端不再讀取即可

**(b) `handle_special_fields()` — 處理 quantities 儲存（約 line 917~968）**
- 在方法開頭新增對 `pbp_product_quantities` 的處理：
  ```php
  if (isset($meta_data['pbp_product_quantities'])) {
      $quantities = $meta_data['pbp_product_quantities'];
      // 如果是 JSON string（從 FormData 來），先 decode
      if (is_string($quantities)) {
          $quantities = json_decode($quantities, true) ?: [];
      }
      $helper = Helper::instance($product);
      $helper?->set_product_quantities($quantities);
      unset($meta_data['pbp_product_quantities']);
  }
  ```

**(c) `post_bundle_products_callback()` — 新建方案自動加入當前課程（約 line 650~690）**
- 在 `$product->save()` 之後、處理 meta_data 之前：
  - 讀取 `link_course_ids` 從 `$meta_data`
  - 如果 `pbp_product_ids` 不包含 course_id，自動加入
  - 設定 `pbp_product_quantities` 中 course_id 的 qty 為 1

**原因**：API 是前後端的唯一橋樑，必須正確序列化/反序列化 quantities
**依賴**：步驟 1.1
**風險**：中 — 修改 API 回應格式，需確保前端同步更新

#### 1.3 `Order.php` — 訂單項目名稱與庫存扣減

**檔案**：`inc/classes/Resources/Order.php`

**行動**：

修改 `_handle_add_course_item_meta_by_order()` 方法（lines 95~136）：

```
原本邏輯（line 115~133）：
foreach ($included_product_ids as $included_product_id) {
    $qty = $item->get_quantity() ?: 1;
    $order->add_product($included_product, $qty, [
        'name' => $product->get_name() . ' - ' . $included_product->get_name(),
        'subtotal' => 0, 'total' => 0,
    ]);
}

修改為：
$quantities = $helper->get_product_quantities();
foreach ($included_product_ids as $included_product_id) {
    $bundle_qty = (int)($quantities[(string)$included_product_id] ?? 1);
    $bundle_qty = max(1, min(999, $bundle_qty));
    $purchase_qty = $item->get_quantity() ?: 1;
    $total_qty = $purchase_qty * $bundle_qty;

    // 訂單項目名稱含數量標記
    $item_name = $product->get_name() . ' - ' . $included_product->get_name();
    if ($bundle_qty > 1) {
        $item_name .= ' x' . $bundle_qty;
    }

    $order->add_product($included_product, $total_qty, [
        'name' => $item_name,
        'subtotal' => 0, 'total' => 0,
    ]);
}
```

**重點變更**：
- 讀取 `$helper->get_product_quantities()` 取得每個商品的數量
- `$total_qty = $purchase_qty * $bundle_qty`（購買份數 × 方案內數量）
- 訂單項目名稱：qty > 1 時加入 `x{qty}` 標記（如「超值學習包 - Python 講義 x3」）
- WooCommerce `add_product()` 的 qty 參數設為 `$total_qty`，WC 會自動處理庫存扣減

**原因**：這是庫存正確扣減的核心邏輯
**依賴**：步驟 1.1
**風險**：高 — 直接影響訂單金額與庫存。需要整合測試覆蓋

### 第二階段：前端核心（React — BundleForm 重構）

#### 2.1 型別更新

**檔案**：`js/src/components/product/ProductTable/types/index.ts`

**行動**：
- 在 `TProductRecord` 中新增 `pbp_product_quantities: Record<string, number>`（約 line 95 之後）
- 將 `exclude_main_course` 標記為 `@deprecated`（保留型別避免 TS 報錯，但不再使用）

**依賴**：無
**風險**：低

#### 2.2 Atom 更新

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`

**行動**：
- 新增 `productQuantitiesAtom = atom<Record<string, number>>({})`
  - key 為商品 ID（string），value 為數量（number）

**依賴**：無
**風險**：低

#### 2.3 工具函式更新

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`

**行動**：

修改 `getPrice()` 函式：

```typescript
// 修改前（約 line 22~52）：
const total = products?.reduce((acc, product) =>
    acc + Number(product?.[type] || product.regular_price), 0
) + (excludeMainCourse ? 0 : coursePrice)

// 修改後：
export const getPrice = ({
    isFetching = false,
    type,
    products,
    course,
    returnType = 'number',
    quantities = {},  // 新增參數
}: {
    // ... 原有參數 ...
    quantities?: Record<string, number>
}): React.ReactNode => {
    const total = products?.reduce((acc, product) => {
        const qty = quantities[product.id] ?? 1
        return acc + Number(product?.[type] || product.regular_price) * qty
    }, 0) ?? 0

    // 注意：不再需要 excludeMainCourse 參數
    // 因為當前課程已統一在 products 列表中（如果存在的話）
    // ...
}
```

- 移除 `excludeMainCourse` 參數
- 新增 `quantities` 參數，每個商品價格 × 對應數量

**依賴**：步驟 2.1
**風險**：中 — 影響價格計算顯示

#### 2.4 BundleForm 核心重構

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

**行動**：

**(a) 移除 exclude_main_course 相關程式碼**
- 刪除 line 46~47 的 `watchExcludeMainCourse`
- 刪除 line 232~240 的 `FiSwitch` 元件（「排除目前課程」開關）
- 刪除 line 242~264 的特殊課程 UI 區塊（帶有 opacity-20 saturate-0 的 div）

**(b) 將「當前課程」統一為商品列表項目**
- 修改 `selectedProductsAtom` 初始化邏輯（約 line 113~141）：
  - 不再從 `initPIdsExcludedCourseId` 中排除 courseId
  - 初始化時如果 `pbp_product_ids` 包含 courseId，也載入到 `selectedProducts` 中
  - 當前課程使用 course 資料構造一個 `TBundleProductRecord` 物件（取 id, name, images, price_html, regular_price, type 等欄位）
- 在搜尋框下方的選中商品列表中，當前課程和其他商品一樣顯示（有刪除按鈕、有數量輸入框）
- 當前課程可能有特殊標記 `<Tag color="blue">目前課程</Tag>`（保持可識別性，但操作方式一致）

**(c) 每個商品新增數量 InputNumber**
- 在選中商品列表（約 line 325~366）每個商品項目中新增 `InputNumber`：
  ```tsx
  <InputNumber
      min={1}
      max={999}
      precision={0}
      value={quantities[id] ?? 1}
      onChange={(val) => {
          const qty = Math.max(1, Math.min(999, Math.floor(val ?? 1)))
          setQuantities(prev => ({ ...prev, [id]: qty }))
      }}
      className="w-20"
  />
  ```
- 同樣在搜尋結果選中時初始化 qty=1

**(d) 更新 `useEffect` — 同步 quantities 到表單**
- 修改 line 144~166 的 useEffect：
  ```tsx
  useEffect(() => {
      // 選擇商品改變時，同步更新到表單上
      const productIds = selectedProducts.map(({ id }) => id)
      bundleProductForm.setFieldValue([INCLUDED_PRODUCT_IDS_FIELD_NAME], productIds)

      // 同步 quantities
      bundleProductForm.setFieldValue(['pbp_product_quantities'], JSON.stringify(quantities))

      // 更新原價（含數量）
      bundleProductForm.setFieldValue(
          ['regular_price'],
          getPrice({ type: 'regular_price', products: selectedProducts, quantities })
      )
  }, [selectedProducts.length, quantities])
  ```

**(e) 更新 bundlePrices 計算**
- 修改 line 168~185，傳入 `quantities` 參數

**原因**：這是 UI 層最大的變動，需要統一當前課程和一般商品的處理邏輯
**依賴**：步驟 2.1、2.2、2.3
**風險**：高 — 核心 UI 重構，需仔細處理初始化順序與邊界情況

#### 2.5 EditBundle 表單提交更新

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx`

**行動**：
- 修改 `handleOnFinish()`（line 59~84）：
  - 移除 `watchExcludeMainCourse` 相關驗證邏輯
  - 確保 `pbp_product_quantities` 在 `toFormData()` 之前已序列化為 JSON string
  - 新增驗證：bundle_type === 'bundle' 時至少要有一個商品
- 移除 line 46~47 的 `watchExcludeMainCourse`
- 初始化 atoms 時（line 49~56），也初始化 `productQuantitiesAtom`：
  ```tsx
  useEffect(() => {
      form.setFieldsValue(record)
      setBundleProduct(record)
      // 初始化 quantities
      setQuantities(record.pbp_product_quantities ?? {})
  }, [record])
  ```

**依賴**：步驟 2.4
**風險**：中

#### 2.6 CourseBundles 列表 — 新建方案自動加入當前課程

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseBundles/index.tsx`

**行動**：
- 修改 `handleCreate()`（line 95~111）：
  ```tsx
  const handleCreate = () => {
      const values = {
          status: 'publish',
          bundle_type: 'bundle',
          name: '銷售方案',
          link_course_ids: [courseId],
          pbp_product_ids: [courseId],  // 新增：自動加入當前課程
          pbp_product_quantities: JSON.stringify({ [courseId as string]: 1 }),  // 新增：預設 qty=1
      }
      // ...
  }
  ```

**原因**：新建方案時自動包含當前課程，符合 spec 定義
**依賴**：步驟 2.4
**風險**：低

### 第三階段：前台模板（PHP Template）

#### 3.1 銷售方案卡片 — 顯示商品數量

**檔案**：`inc/templates/components/card/bundle-product.php`

**行動**：
- 在 line 31 下方讀取 quantities：
  ```php
  $quantities = $helper?->get_product_quantities() ?? [];
  ```
- 修改 line 85~99 的 foreach 迴圈：
  ```php
  foreach ($pbp_product_ids as $pbp_product_id) :
      if (!is_numeric($pbp_product_id)) { continue; }
      $pbp_product = \wc_get_product($pbp_product_id);
      $qty = max(1, (int)($quantities[(string)$pbp_product_id] ?? 1));

      echo '<div>';
      Plugin::load_template(
          'course-product/list',
          [
              'product' => $pbp_product,
              'qty'     => $qty,  // 新增 qty 參數
          ]
      );
      echo '</div>';
      Plugin::load_template('divider');
  endforeach;
  ```

**依賴**：步驟 1.1
**風險**：低

#### 3.2 商品列表項 — 渲染數量標記

**檔案**：`inc/templates/pages/course-product/list.php`

**行動**：
- 新增 `qty` 參數（預設 1）：
  ```php
  $default_args = [
      'product' => $GLOBALS['course'] ?? null,
      'qty'     => 1,
  ];
  ```
- 在商品名稱後方加入數量標記（qty > 1 時）：
  ```php
  $qty_display = $qty > 1 ? ' <span class="text-primary font-semibold">×' . $qty . '</span>' : '';
  // 在 printf 中將 %2$s 改為 %2$s%4$s，新增 $qty_display 參數
  ```

**依賴**：步驟 3.1
**風險**：低

### 第四階段：向下相容遷移（PHP — Activation Hook）

#### 4.1 遷移 exclude_main_course 到 pbp_product_ids

**檔案**：新增 `inc/classes/BundleProduct/Migration.php`

**行動**：
- 建立 `Migration` 類別，包含 `migrate_exclude_main_course()` 靜態方法
- 在外掛啟動/升級時執行一次性遷移（使用 version check 機制）：
  1. 查詢所有 bundle products（`get_all_bundle_products()`）
  2. 對每個 bundle product：
     - 讀取 `exclude_main_course` meta
     - 讀取 `link_course_ids` meta（即當前課程 ID）
     - 讀取 `pbp_product_ids`
     - 如果 `exclude_main_course !== 'yes'` 且 courseId 不在 `pbp_product_ids` 中：
       - 將 courseId 加入 `pbp_product_ids`
     - 如果不存在 `pbp_product_quantities`：
       - 建立 `{"courseId": 1, ...其他商品ID: 1}`
  3. 使用 `update_option('pc_bundle_qty_migrated', '1')` 標記已遷移

**原因**：確保升級後舊資料正確轉換，避免功能中斷
**依賴**：步驟 1.1
**風險**：中 — 需要在生產環境安全執行。建議使用 batch 處理避免 timeout

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行，planner 只需規劃「要測什麼」。

### PHP Integration Test

1. **Helper 單元測試**
   - `get_product_quantities()` — 無 meta 時回傳空陣列
   - `get_product_quantities()` — 有 meta 時正確 decode JSON
   - `get_product_qty($product_id)` — 存在的商品回傳正確數量
   - `get_product_qty($product_id)` — 不存在的商品回傳 1
   - `set_product_quantities()` — 正確儲存 JSON
   - `set_product_quantities()` — 不合法值被 clamp（0→1, 1000→999, -1→1）

2. **API 整合測試**
   - POST `/bundle_products` — 新建方案，自動包含 courseId 在 `pbp_product_ids`
   - POST `/bundle_products/:id` — 更新方案，正確儲存 `pbp_product_quantities`
   - GET `/bundle_products` — 回應中包含 `pbp_product_quantities` 欄位
   - 向下相容：舊方案無 `pbp_product_quantities`，API 回傳 `{}`

3. **訂單處理測試**
   - 購買 1 份方案（含 Python 講義 ×3），庫存減 3
   - 購買 2 份方案（含 Python 講義 ×3），庫存減 6
   - 舊方案無 quantities，庫存減 1（向下相容）
   - 訂單項目名稱正確含數量標記

### E2E 測試

1. **管理端 E2E**（`tests/e2e/01-admin/bundle-product-qty.spec.ts`）
   - 新建銷售方案 → 確認當前課程自動出現在商品列表中
   - 搜尋商品加入 → 確認數量輸入框預設為 1
   - 修改數量為 3 → 儲存 → 重新開啟 → 確認數量回顯為 3
   - 輸入 0 → 確認自動修正為 1
   - 確認「排除目前課程」開關已移除
   - 移除當前課程 → 確認可以成功儲存

2. **前台 E2E**（`tests/e2e/02-frontend/bundle-qty-display.spec.ts`）
   - 銷售方案含 qty=3 的商品 → 確認前台顯示「×3」
   - 銷售方案含 qty=1 的商品 → 確認前台不顯示「×1」

3. **整合 E2E**（`tests/e2e/03-integration/bundle-qty-stock.spec.ts`）
   - 建立含 qty=3 的銷售方案 → 購買 → 確認庫存正確扣減

### 測試執行指令

```bash
composer run test              # PHPUnit
pnpm run test:e2e:admin        # 管理端 E2E
pnpm run test:e2e:frontend     # 前台 E2E
pnpm run test:e2e:integration  # 整合 E2E
```

### 關鍵邊界情況

- 數量為 0、空白、負數、小數、非數字 → 自動修正為 1
- 數量超過 999 → 自動修正為 999
- 舊銷售方案無 `pbp_product_quantities` meta → 所有商品預設 1
- 舊銷售方案 `exclude_main_course=yes` → 遷移後 courseId 不在 `pbp_product_ids` 中
- 舊銷售方案 `exclude_main_course=no` → 遷移後 courseId 加入 `pbp_product_ids`
- 購買 N 份方案，每個商品庫存扣 N × qty

## 風險與緩解措施

- **風險**：向下相容遷移失敗可能導致既有銷售方案行為改變
  - 緩解：遷移使用 `update_option` 標記，可重複執行；舊欄位暫不刪除
- **風險**：前端重構 BundleForm 可能遺漏邊界情況
  - 緩解：E2E 測試覆蓋主要流程（新建、編輯、儲存回顯）
- **風險**：訂單庫存計算錯誤可能影響營收
  - 緩解：整合 E2E 測試驗證購買 → 庫存扣減正確性
- **風險**：前端 `quantities` atom 與 `selectedProducts` atom 同步問題
  - 緩解：商品移除時同步清理 quantities 對應 key

## 成功標準

- [ ] 銷售方案編輯畫面中，每個商品旁有數量輸入框（含當前課程）
- [ ] 數量輸入框預設值為 1，可輸入 1~999 的正整數
- [ ] 輸入 0 或空白時，自動修正為 1
- [ ] 儲存後重新開啟，數量正確回顯
- [ ] 既有銷售方案升級後，所有商品數量預設顯示為 1
- [ ] `exclude_main_course` 開關已從 UI 移除
- [ ] 新建方案時自動包含當前課程（qty=1）
- [ ] 前台銷售頁：qty > 1 顯示「×N」，qty = 1 不顯示
- [ ] 訂單項目名稱正確含數量標記
- [ ] 庫存按 購買份數 × 方案內數量 正確扣減
- [ ] 所有 PHP 程式碼通過 `pnpm run lint:php`（PHPCS + PHPStan level 9）
- [ ] 所有 TS 程式碼通過 `pnpm run lint:ts`（ESLint + TypeScript strict）
- [ ] E2E 測試全部通過
