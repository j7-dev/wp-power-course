# 實作計劃：銷售方案商品數量自由設定（Issue #182）

## 概述

在銷售方案（Bundle Product）編輯介面中新增數量輸入功能，讓管理員可以為每個方案內商品設定數量（1–999）。同時移除「排除目前課程」開關，改為將「目前課程」視為方案中的普通商品項目。涉及後端 PHP（Helper / API / Order / Migration）與前端 React（BundleForm / utils / atom / types）共 12 個檔案。

## 範圍模式：HOLD SCOPE（Bug 修復 / 重構）

影響 12 個檔案，範圍已定。專注於防彈架構與向下相容。

## 需求

- 管理員可在銷售方案中為每個商品設定數量（正整數 1–999）
- 數量資料以 `pbp_product_quantities` postmeta 儲存（JSON: `{"productId": qty}`）
- `pbp_product_ids` 維持現有格式不變（向下相容）
- 移除 `exclude_main_course` 開關，目前課程改為預設商品（qty=1）
- 舊資料自動遷移，無需手動操作
- 前台卡片中數量 > 1 的商品顯示灰色「×N」標示
- 訂單處理時各商品數量 = 方案購買數量 × 商品設定數量

## 架構變更

| 檔案 | 變更類型 | 說明 |
|------|----------|------|
| `inc/classes/BundleProduct/Helper.php` | 修改 | 新增常數 + quantity 讀寫方法 |
| `inc/classes/Api/Product.php` | 修改 | API 回應加入 quantities；儲存時驗證；移除 exclude_main_course |
| `inc/classes/Resources/Order.php` | 修改 | 訂單 line item 數量乘以商品設定數量 |
| `inc/classes/Compatibility/Compatibility.php` | 修改 | 新增遷移呼叫點 |
| `inc/classes/Compatibility/BundleProduct.php` | 修改 | 新增 migrate_exclude_main_course() 方法 |
| `inc/templates/components/card/bundle-product.php` | 修改 | 傳遞 quantity 到子模板 |
| `inc/templates/pages/course-product/list.php` | 修改 | 支援 quantity 參數，顯示 ×N |
| `js/src/.../Edit/BundleForm.tsx` | 修改 | 移除 exclude_main_course 開關；目前課程改為普通項目；新增 InputNumber |
| `js/src/.../Edit/utils/index.tsx` | 修改 | getPrice() 乘以數量 |
| `js/src/.../Edit/atom.tsx` | 修改 | 新增 productQuantitiesAtom |
| `js/src/.../Edit/index.tsx` | 修改 | 移除 exclude_main_course 驗證；新增 pbp_product_quantities 表單欄位 |
| `js/src/.../types/index.ts` | 修改 | TProductRecord 新增 pbp_product_quantities 型別；移除 exclude_main_course |

## 資料流分析

### 1. 管理員設定數量 → 儲存到 DB

```
前端 InputNumber ──▶ atom 更新 ──▶ Form.setFieldValue ──▶ toFormData ──▶ POST /bundle_products/{id}
     │                  │                │                     │                    │
     ▼                  ▼                ▼                     ▼                    ▼
[min=1,max=999]  [quantities map]  [pbp_product_ids]   [FormData encode]  [handle_special_fields]
                                   [pbp_product_quantities]                        │
                                                                                   ▼
                                                                           validate_quantities()
                                                                                   │
                                                                           ┌───────┴───────┐
                                                                           ▼               ▼
                                                                      [通過]          [失敗: 400]
                                                                           │
                                                                           ▼
                                                                   update_post_meta()
                                                                   (JSON string)
```

### 2. 學員購買 → 訂單處理

```
WC Checkout ──▶ woocommerce_new_order ──▶ _handle_add_course_item_meta_by_order()
                                                │
                                                ▼
                                    foreach order_items as item
                                                │
                                     is_bundle_product?
                                        ┌───────┴───────┐
                                        ▼               ▼
                                     [YES]            [NO: skip]
                                        │
                                        ▼
                             get_product_ids() + get_product_quantities()
                                        │
                                        ▼
                             foreach included_product_ids
                                        │
                                        ▼
                             qty = purchase_qty × product_set_qty
                                        │
                                        ▼
                             order->add_product(product, qty, ...)
                                        │
                                        ▼
                             WC 自動扣庫存（qty 已正確）
```

### 3. 前台顯示

```
課程銷售頁 ──▶ bundle-product.php ──▶ get_product_quantities()
                                          │
                                          ▼
                                  foreach pbp_product_ids
                                          │
                                          ▼
                                  qty = quantities[id] ?? 1
                                          │
                                  ┌───────┴───────┐
                                  ▼               ▼
                             [qty > 1]       [qty = 1]
                                  │               │
                                  ▼               ▼
                          顯示 "×N" 灰色    不顯示標示
                                  │               │
                                  └───────┬───────┘
                                          ▼
                                 course-product/list.php
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|-----------|
| `validate_quantities()` | 數量 < 1 | 400 Bad Request | 回傳錯誤訊息「數量至少為 1」 | 是（前端 toast） |
| `validate_quantities()` | 數量 > 999 | 400 Bad Request | 回傳錯誤訊息「數量不可超過 999」 | 是（前端 toast） |
| `validate_quantities()` | 非正整數 | 400 Bad Request | 回傳錯誤訊息「數量必須為正整數」 | 是（前端 toast） |
| `get_product_quantities()` | meta 不存在/JSON parse 失敗 | 靜默降級 | 回傳空陣列 `[]`（預設 qty=1） | 否 |
| `migrate_exclude_main_course()` | 課程 ID 已在 pbp_product_ids | 邏輯衝突 | 跳過不重複加入 | 否 |
| `order add_product()` | 商品不存在 | WC Error | continue 跳過 | 否（已有） |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|-----------|---------|
| API save quantities | JSON encode 失敗 | 需新增 | spec 已定義 | 靜默（降級為空 {}） | 重新儲存 |
| Order qty 計算 | quantities meta 缺失 | 是（預設 1） | spec 已定義 | 否 | 自動降級 |
| Migration | 中途中斷 | 需新增 | spec 已定義 | 否 | 下次啟動重跑 |
| Frontend InputNumber | 輸入非法值 | 前端攔截 | 待新增 | 是（欄位紅框） | 自動修正 |

---

## 實作步驟

### 第一階段：後端資料層（PHP）

#### 步驟 1.1 — Helper 新增 quantity 方法

**檔案**: `inc/classes/BundleProduct/Helper.php`

**行動**:
1. 新增常數 `PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities'`（第 18 行後）
2. 新增方法 `get_product_quantities(): array` — 讀取 meta 並 json_decode，失敗回傳 `[]`
3. 新增方法 `set_product_quantities(array $quantities): void` — json_encode 後存入 postmeta
4. 新增靜態方法 `validate_quantities(array $quantities): true|\WP_Error` — 驗證每個值為 1–999 正整數

**具體變更**:
```php
// 第 18 行後新增
const PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities';

// 新增方法（約第 140 行，get_product_ids 方法之後）
/**
 * 取得銷售方案中各商品的數量設定
 * 回傳 associative array，key 為商品 ID（字串），value 為數量（整數）
 * 若 meta 不存在或 decode 失敗，回傳空陣列（預設所有商品數量為 1）
 *
 * @return array<string, int>
 */
public function get_product_quantities(): array {
    $id = $this->product->get_id();
    $raw = \get_post_meta($id, self::PRODUCT_QUANTITIES_META_KEY, true);
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    // 確保 key 為字串、value 為正整數
    $result = [];
    foreach ($decoded as $product_id => $qty) {
        $result[(string) $product_id] = max(1, (int) $qty);
    }
    return $result;
}

/**
 * 設定銷售方案中各商品的數量
 *
 * @param array<string|int, int> $quantities key 為商品 ID，value 為數量
 * @return void
 */
public function set_product_quantities(array $quantities): void {
    $sanitized = [];
    foreach ($quantities as $product_id => $qty) {
        $sanitized[(string) $product_id] = (int) $qty;
    }
    \update_post_meta(
        $this->product->get_id(),
        self::PRODUCT_QUANTITIES_META_KEY,
        wp_json_encode($sanitized)
    );
}

/**
 * 驗證數量陣列
 *
 * @param array<string|int, mixed> $quantities
 * @return true|\WP_Error
 */
public static function validate_quantities(array $quantities): true|\WP_Error {
    foreach ($quantities as $product_id => $qty) {
        if (!is_numeric($qty) || (float) $qty !== (float) (int) $qty) {
            return new \WP_Error('invalid_quantity', '數量必須為正整數', ['status' => 400]);
        }
        $qty = (int) $qty;
        if ($qty < 1) {
            return new \WP_Error('quantity_too_low', '數量至少為 1', ['status' => 400]);
        }
        if ($qty > 999) {
            return new \WP_Error('quantity_too_high', '數量不可超過 999', ['status' => 400]);
        }
    }
    return true;
}
```

**原因**: quantity 的讀寫與驗證是所有後續步驟的基礎
**依賴**: 無
**風險**: 低

---

#### 步驟 1.2 — API 回應加入 quantities + 儲存驗證

**檔案**: `inc/classes/Api/Product.php`

**行動**:

1. **API 回應** — `format_product_details()` 方法（第 580–590 行區塊）：
   - 第 581 行後新增：`Helper::PRODUCT_QUANTITIES_META_KEY => ($helper !== null ? $helper->get_product_quantities() : [])`
   - 移除第 586 行的 `'exclude_main_course'` 欄位

2. **儲存驗證** — `post_bundle_products_with_id_callback()` 方法（第 749 行開始）：
   - 在 `handle_special_fields()` 呼叫前，檢查 `$meta_data` 中是否有 `pbp_product_quantities`
   - 若有，先呼叫 `Helper::validate_quantities()` 驗證
   - 驗證失敗回傳 400 錯誤
   - 驗證成功後呼叫 `$helper->set_product_quantities($quantities)` 儲存
   - 從 `$meta_data` 中 unset 此欄位（避免被後續 update_meta_data 覆蓋為字串）

3. **新建銷售方案** — `post_bundle_products_callback()` 方法（第 650 行）：
   - 同樣處理 `pbp_product_quantities` 的驗證與儲存

4. **handle_special_fields()** 方法（第 917 行）：
   - `$unset_meta_keys` 陣列新增 `Helper::PRODUCT_QUANTITIES_META_KEY`（因為已在前面手動處理）
   - `$unset_meta_keys` 陣列新增 `'exclude_main_course'`（不再儲存此欄位）

**具體變更位置**:
```php
// format_product_details() — 第 581 行後插入
Helper::PRODUCT_QUANTITIES_META_KEY => ( $helper !== null ? $helper->get_product_quantities() : [] ),

// format_product_details() — 移除第 586 行
// 'exclude_main_course' => ... // 刪除此行

// post_bundle_products_with_id_callback() — 在 handle_special_fields 呼叫前（約第 838 行前）
if (isset($meta_data[Helper::PRODUCT_QUANTITIES_META_KEY])) {
    $quantities_raw = $meta_data[Helper::PRODUCT_QUANTITIES_META_KEY];
    // 前端以 FormData 傳送，可能是 JSON string 或 array
    $quantities = is_string($quantities_raw) ? json_decode($quantities_raw, true) : $quantities_raw;
    if (is_array($quantities)) {
        $validation = Helper::validate_quantities($quantities);
        if (\is_wp_error($validation)) {
            return new \WP_REST_Response([
                'code'    => 'invalid_quantity',
                'message' => $validation->get_error_message(),
            ], 400);
        }
        $helper = Helper::instance($product);
        $helper?->set_product_quantities($quantities);
    }
    unset($meta_data[Helper::PRODUCT_QUANTITIES_META_KEY]);
}

// handle_special_fields() — $unset_meta_keys 陣列
$unset_meta_keys = [
    'product_type',
    Helper::PRODUCT_QUANTITIES_META_KEY,
    'exclude_main_course',
];
```

**原因**: API 是前後端的橋樑，需要先確保資料可正確傳遞與驗證
**依賴**: 步驟 1.1
**風險**: 中 — 需注意 FormData 中 JSON 欄位的解碼

---

#### 步驟 1.3 — 訂單處理支援數量

**檔案**: `inc/classes/Resources/Order.php`

**行動**: 修改 `_handle_add_course_item_meta_by_order()` 方法（第 95–143 行），在加入 included product 到訂單時，將數量乘以商品設定數量。

**具體變更**（第 111–134 行）:
```php
// 現有程式碼（第 111-134 行）改為：
$helper = Helper::instance( $product );
if ( $helper?->is_bundle_product ) {
    $included_product_ids = $helper?->get_product_ids() ?: [];
    $product_quantities   = $helper?->get_product_quantities() ?: []; // 新增

    foreach ( $included_product_ids as $included_product_id ) {
        $included_product = \wc_get_product( $included_product_id );
        if ( ! $included_product ) {
            continue;
        }

        $purchase_qty = $item->get_quantity() ?: 1;
        // 商品設定數量（預設為 1，向下相容）
        $product_set_qty = (int) ( $product_quantities[ (string) $included_product_id ] ?? 1 );
        $qty = $purchase_qty * $product_set_qty; // 改為乘積

        $order->add_product(
            $included_product,
            $qty,
            [
                'name'     => $product->get_name() . ' - ' . $included_product->get_name(),
                'subtotal' => 0,
                'total'    => 0,
            ]
        );
    }
    $order->save();
}
```

**原因**: 訂單數量公式 = 方案購買數量 × 商品設定數量，是核心業務邏輯
**依賴**: 步驟 1.1
**風險**: 高 — 直接影響金流與庫存，測試必須覆蓋完整

---

### 第二階段：資料遷移（PHP）

#### 步驟 2.1 — 新增遷移方法

**檔案**: `inc/classes/Compatibility/BundleProduct.php`

**行動**: 新增 `migrate_exclude_main_course()` 靜態方法。

**具體變更**:
```php
/**
 * 遷移 exclude_main_course 欄位
 * - exclude_main_course = 'yes'：不修改 pbp_product_ids（課程本不在列表中）
 * - exclude_main_course = 'no' 或無此欄位：將 link_course_id 加入 pbp_product_ids，
 *   並在 pbp_product_quantities 中設定該課程數量為 1
 * - 遷移完成後刪除 exclude_main_course meta
 *
 * @return void
 */
public static function migrate_exclude_main_course(): void {
    $bundle_product_ids = self::get_all_bundle_products();
    foreach ($bundle_product_ids as $bundle_id) {
        $product = \wc_get_product($bundle_id);
        if (!$product) {
            continue;
        }
        $helper = Helper::instance($product);
        if (!$helper?->is_bundle_product) {
            continue;
        }

        $exclude_main_course = (string) $product->get_meta('exclude_main_course');
        $link_course_id      = $helper->link_course_id;

        if (!$link_course_id) {
            // 沒有連結課程，直接清除 meta
            \delete_post_meta($bundle_id, 'exclude_main_course');
            continue;
        }

        // 只有非排除（'no' 或空值）才需要加入課程到 pbp_product_ids
        if ($exclude_main_course !== 'yes') {
            $existing_ids = $helper->get_product_ids();
            // 若課程 ID 不在列表中才加入（避免重複）
            if (!in_array((string) $link_course_id, $existing_ids, true)) {
                $helper->add_bundled_ids($link_course_id);
                // 設定數量為 1
                $quantities = $helper->get_product_quantities();
                $quantities[(string) $link_course_id] = 1;
                $helper->set_product_quantities($quantities);
            }
        }

        // 遷移完成後刪除 exclude_main_course meta
        \delete_post_meta($bundle_id, 'exclude_main_course');
    }
}
```

**原因**: 確保舊資料與新功能相容
**依賴**: 步驟 1.1
**風險**: 中 — 需在遷移後仔細驗證資料完整性

---

#### 步驟 2.2 — 在 Compatibility 中掛載遷移

**檔案**: `inc/classes/Compatibility/Compatibility.php`

**行動**: 在 `compatibility()` 方法中（第 79 行 `BundleProduct::set_catalog_visibility_to_hidden()` 之後），新增遷移呼叫。

**具體變更**（第 79 行後）:
```php
BundleProduct::set_catalog_visibility_to_hidden();

// 1.1.0 之後移除 exclude_main_course，改用 pbp_product_quantities
if (version_compare($previous_version, '1.1.0', '<')) {
    BundleProduct::migrate_exclude_main_course();
}
```

> 注意：`$previous_version` 在第 81 行才取得，但 `BundleProduct::set_catalog_visibility_to_hidden()` 在第 79 行（`$previous_version` 之前）。需要調整：將遷移放在第 95 行區塊之後（所有 version_compare 的區塊之後），並使用 `$previous_version` 變數。

**實際插入位置**（第 95 行之後，`END 相容性代碼` 之前）:
```php
// 1.1.0 移除 exclude_main_course，遷移為 pbp_product_quantities
if (version_compare($previous_version, '1.1.0', '<')) {
    BundleProduct::migrate_exclude_main_course();
}
```

**原因**: 利用現有的版本比較機制，確保遷移只在升級時執行一次
**依賴**: 步驟 2.1
**風險**: 低

---

### 第三階段：前台模板（PHP）

#### 步驟 3.1 — bundle-product.php 傳遞 quantity

**檔案**: `inc/templates/components/card/bundle-product.php`

**行動**: 
1. 第 31 行後新增：讀取 `product_quantities`
2. 第 85–99 行的 `foreach` 迴圈中：傳遞 `quantity` 參數到子模板

**具體變更**:
```php
// 第 31 行後新增
$product_quantities = $helper?->get_product_quantities() ?? [];

// 第 85-99 行改為：
foreach ( $pbp_product_ids as $pbp_product_id ) :
    if (!is_numeric($pbp_product_id)) {
        continue;
    }
    $pbp_product = \wc_get_product( $pbp_product_id );
    $qty = (int) ( $product_quantities[ (string) $pbp_product_id ] ?? 1 );
    echo '<div>';
    Plugin::load_template(
        'course-product/list',
        [
            'product'  => $pbp_product,
            'quantity' => $qty,
        ]
    );
    echo '</div>';
    Plugin::load_template( 'divider' );
endforeach;
```

**原因**: 將數量資訊向下傳遞給子模板
**依賴**: 步驟 1.1
**風險**: 低

---

#### 步驟 3.2 — list.php 顯示 ×N

**檔案**: `inc/templates/pages/course-product/list.php`

**行動**: 
1. 在 `$default_args` 中新增 `'quantity' => 1`
2. 解構取出 `$quantity`
3. 在商品名稱 `<h6>` 後方，若 `$quantity > 1`，顯示灰色 `×N` 文字

**具體變更**:
```php
// $default_args 改為：
$default_args = [
    'product'  => $GLOBALS['course'] ?? null,
    'quantity' => 1,
];

// 解構增加 quantity：
[
    'product'  => $product,
    'quantity' => $quantity,
] = $args;

// printf 中的 %2$s（商品名稱）後方加上數量標示：
// 在 <h6> 標籤中，商品名稱後追加：
$quantity_html = ((int) $quantity > 1)
    ? sprintf(' <span class="text-gray-400 text-xs ml-1">×%d</span>', (int) $quantity)
    : '';

// printf 模板的第 43 行改為：
'<h6 class="text-sm font-semibold mb-1">%2$s%4$s</h6>'
// 並新增第 4 個參數 $quantity_html
```

**原因**: 學員需要在前台看到每個商品的數量
**依賴**: 步驟 3.1
**風險**: 低

---

### 第四階段：前端管理介面（React/TypeScript）

#### 步驟 4.1 — TypeScript 型別更新

**檔案**: `js/src/components/product/ProductTable/types/index.ts`

**行動**:
1. `TProductRecord` 中新增 `pbp_product_quantities: Record<string, number>` 欄位（第 95 行後）
2. `TProductRecord` 中移除 `exclude_main_course` 欄位（第 105 行）

**具體變更**:
```typescript
// 第 95 行後新增
pbp_product_quantities: Record<string, number>

// 第 105 行移除
// exclude_main_course: 'yes' | 'no' | '' // 刪除此行
```

**原因**: 前端型別必須與 API 回應一致
**依賴**: 無（可與後端步驟並行）
**風險**: 低 — 但需搜尋所有引用 `exclude_main_course` 的前端檔案

---

#### 步驟 4.2 — atom 新增 quantities 狀態

**檔案**: `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`

**行動**: 新增 `productQuantitiesAtom` 來管理每個商品的數量設定。

**具體變更**:
```typescript
// 新增
export const productQuantitiesAtom = atom<Record<string, number>>({})
```

**原因**: 數量是獨立於商品列表的狀態，需要獨立管理
**依賴**: 無
**風險**: 低

---

#### 步驟 4.3 — utils/getPrice 支援數量

**檔案**: `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`

**行動**: 
1. `getPrice` 函式新增 `quantities` 參數（`Record<string, number>`）
2. 價格計算改為 `sum(product.price × quantity)`
3. 移除 `excludeMainCourse` 參數（不再需要）

**具體變更**:
```typescript
export const getPrice = ({
  isFetching = false,
  type,
  products,
  course,
  returnType = 'number',
  quantities = {},     // 新增
}: {
  isFetching?: boolean
  type: 'regular_price' | 'sale_price'
  products: TBundleProductRecord[] | undefined
  course: TCourseRecord | undefined
  returnType?: 'string' | 'number'
  quantities?: Record<string, number>  // 新增
}): React.ReactNode => {
  if (isFetching) {
    return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
  }
  // 所有商品（含目前課程）都在 products 中，不再需要單獨加 coursePrice
  const total = Number(
    products?.reduce((acc, product) => {
      const price = Number(product?.[type] || product.regular_price || 0)
      const qty = quantities[String(product.id)] ?? 1
      return acc + price * qty
    }, 0)
  )

  if ('number' === returnType) return total
  return `NT$ ${total?.toLocaleString()}`
}
```

> 注意：由於移除了 `exclude_main_course`，「目前課程」現在是 `selectedProducts` 的一部分。`getPrice` 不再需要單獨處理 `coursePrice`。但初始化時需要確保 course 在 selectedProducts 中。

**原因**: 原價加總必須反映數量（單價 × 數量 之加總）
**依賴**: 步驟 4.1
**風險**: 中 — 需確認所有呼叫 `getPrice` 的地方都已更新參數

---

#### 步驟 4.4 — BundleForm 重構

**檔案**: `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`

這是最大的變更，需要：

**行動 A — 移除 exclude_main_course 相關邏輯**:
1. 移除 `watchExcludeMainCourse` 變數（第 46-47 行）
2. 移除 `FiSwitch` 元件（第 232-240 行）
3. 移除 `FiSwitch` import（第 13 行）

**行動 B — 目前課程改為可操作的商品項目**:
1. 修改第 113-115 行 `initPIdsExcludedCourseId`：不再排除 courseId
2. 修改第 242-258 行「當前課程方案」區塊：
   - 改為與其他商品一樣的項目格式
   - 增加 InputNumber（數量）與 PopconfirmDelete（刪除）
   - 加上「目前課程」Tag 標記
3. 搜尋排除 courseId：修改第 69-72 行 `exclude` filter，不再硬排除 courseId

**行動 C — 新增 InputNumber 數量輸入**:
1. import `InputNumber` from `antd`
2. import `productQuantitiesAtom` from `./atom`
3. 使用 `useAtom(productQuantitiesAtom)` 管理數量狀態
4. 在每個已選商品項目（第 325-366 行 map 區塊）中，在 Tag 與 PopconfirmDelete 之間插入 InputNumber：
   ```tsx
   <InputNumber
     min={1}
     max={999}
     value={quantities[String(id)] ?? 1}
     onChange={(val) => {
       setQuantities(prev => ({
         ...prev,
         [String(id)]: val ?? 1,
       }))
     }}
     size="small"
     className="w-16"
   />
   ```

**行動 D — 同步 quantities 到表單**:
1. 修改第 144-166 行的 `useEffect`：
   - 移除 `watchExcludeMainCourse` 相關邏輯
   - `productIds` 直接取 `selectedProducts.map(({id}) => id)`（目前課程已在內）
   - 新增 `bundleProductForm.setFieldValue(['pbp_product_quantities'], JSON.stringify(quantities))`
   - `getPrice` 呼叫改為傳入 `quantities` 而非 `excludeMainCourse`
2. 修改第 168-185 行 `bundlePrices` 的 `getPrice` 呼叫：同樣傳入 `quantities`

**行動 E — 初始化目前課程**:
1. 初始載入時：若 `record` 的 `pbp_product_ids` 包含 `courseId`，則 course 視為 selectedProducts 的一部分
2. 新建時：自動將 course 加入 selectedProducts（qty=1）
3. 修改 `initPIdsExcludedCourseId`（第 113-115 行）：改名為 `initProductIds`，不再排除 courseId
4. 初始化 quantities：從 `record.pbp_product_quantities` 讀取

**具體初始化邏輯**:
```tsx
// 初始化商品 IDs（不再排除 courseId）
const initProductIds = record?.[INCLUDED_PRODUCT_IDS_FIELD_NAME] || []

// 需要特殊處理：selectedProducts 需要包含 course
// 如果 initProductIds 包含 courseId，course 需要轉換為 TBundleProductRecord 格式
// 如果不包含（舊方案排除了），則不加入

// useEffect 初始化 quantities
useEffect(() => {
  if (record?.pbp_product_quantities) {
    setQuantities(record.pbp_product_quantities)
  }
}, [record])
```

**行動 F — 處理目前課程在 selectedProducts 中的特殊渲染**:
- 目前課程需要在商品列表中以特殊 Tag「目前課程」標記
- 但它和其他商品一樣可以刪除、修改數量

**原因**: 這是使用者互動的核心變更，直接影響管理員體驗
**依賴**: 步驟 4.1, 4.2, 4.3
**風險**: 高 — 涉及多處 UI 邏輯重構，需仔細測試互動行為

---

#### 步驟 4.5 — EditBundle（index.tsx）更新

**檔案**: `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/index.tsx`

**行動**:
1. 移除 `watchExcludeMainCourse` 變數（第 46-47 行）
2. 修改第 64-71 行驗證邏輯：不再檢查 `watchExcludeMainCourse`，改為直接檢查 `selectedProducts.length === 0`
3. 新增隱藏表單欄位 `<Form.Item name={['pbp_product_quantities']} hidden />`

**具體變更**:
```tsx
// 第 64-71 行改為：
if (!selectedProducts?.length && values?.bundle_type === 'bundle') {
    message.error('請至少選擇一個商品')
    return
}
```

**原因**: 與 BundleForm 的重構一致
**依賴**: 步驟 4.4
**風險**: 低

---

### 第五階段：額外清理

#### 步驟 5.1 — 搜尋並清理所有 exclude_main_course 引用

**行動**: 全域搜尋 `exclude_main_course` 關鍵字，確認所有引用都已移除或替換：
- PHP 檔案：`Api/Product.php`（已處理）
- TSX 檔案：`BundleForm.tsx`、`Edit/index.tsx`、`types/index.ts`（已處理）
- 可能遺漏的模板或工具檔案

**依賴**: 步驟 1.2, 4.4, 4.5
**風險**: 低

---

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行。

### PHP Integration Test（優先）

**測試檔案**: 對應 feature specs：

1. **設定銷售方案商品數量**（`specs/features/bundle/設定銷售方案商品數量.feature`）
   - 驗證數量邊界（0, -3, 1000, 2.5 → 失敗）
   - 驗證成功儲存與讀取
   - 驗證 pbp_product_ids 不受影響
   - 驗證訂閱商品支援數量
   - 驗證原價加總包含數量

2. **移除排除目前課程並遷移**（`specs/features/bundle/移除排除目前課程並遷移.feature`）
   - 遷移：exclude_main_course='yes' → 不修改
   - 遷移：exclude_main_course='no' → 加入課程
   - 遷移：無 meta → 視為 'no'
   - 遷移：課程已存在 → 不重複加入
   - 新建：自動包含目前課程

3. **銷售方案含數量訂單處理**（`specs/features/bundle/銷售方案含數量訂單處理.feature`）
   - 購買 1 份：各商品按設定數量
   - 購買 2 份：數量翻倍
   - 庫存正確扣除
   - 向下相容：無 quantities meta 時預設 1
   - 課程存取權：無論數量，僅一份

### E2E Test（Playwright）

4. **前台顯示**（`specs/features/bundle/前台顯示銷售方案商品數量.feature`）
   - 數量 > 1 顯示 ×N
   - 數量 = 1 不顯示
   - 舊方案不顯示
   - HTML 結構驗證

5. **後台管理**
   - 新建銷售方案：目前課程為預設商品
   - 編輯數量：InputNumber 可修改
   - 儲存並重新開啟：數量保持
   - 移除目前課程：可操作
   - 介面不存在 exclude_main_course 開關

### 測試執行指令
```bash
composer run test             # PHPUnit
pnpm run test:e2e:admin       # 管理端 E2E
pnpm run test:e2e:frontend    # 前台 E2E
pnpm run test:e2e:integration # 整合 E2E
```

### 關鍵邊界情況
- 舊方案（無 pbp_product_quantities meta）的向下相容
- 遷移過程中途中斷後重跑的冪等性
- 課程 ID 已在 pbp_product_ids 中時不重複加入
- InputNumber 輸入 0、空白、負數、小數的前端攔截
- 購買多份方案時數量乘積溢出（理論上不會，999 × 999 < INT_MAX）

---

## 風險與緩解措施

- **風險**: 遷移中途中斷導致部分資料不一致
  - 緩解措施: 遷移方法冪等設計（先檢查再操作），下次啟動會重跑

- **風險**: 前端 `getPrice()` 重構後，原有呼叫點未更新參數
  - 緩解措施: TypeScript strict mode 會在編譯時捕捉遺漏的參數變更

- **風險**: 訂單數量計算錯誤直接影響金流
  - 緩措施: Integration test 必須覆蓋購買 1 份和多份場景；向下相容 test 確保舊方案不受影響

- **風險**: `exclude_main_course` 移除後，舊前端快取可能送出此欄位
  - 緩解措施: 後端在 `handle_special_fields()` 中 unset 此欄位，不會寫入 DB

- **風險**: FormData 中 `pbp_product_quantities` 作為 JSON string 可能 parse 失敗
  - 緩解措施: 後端 `post_bundle_products_with_id_callback` 中加入 `json_decode` 降級處理

## 成功標準

- [ ] 後台：每個已加入商品旁有 InputNumber（min=1, max=999, 預設 1）
- [ ] 後台：目前課程以普通商品顯示（帶「目前課程」Tag），可修改數量、可刪除
- [ ] 後台：不存在「排除目前課程」開關
- [ ] 後台：儲存後重新開啟，數量正確顯示
- [ ] 後台：舊方案開啟後所有商品數量預設顯示 1
- [ ] API：回應包含 `pbp_product_quantities` 欄位
- [ ] API：不再回傳 `exclude_main_course` 欄位
- [ ] API：數量驗證失敗回傳 400 + 錯誤訊息
- [ ] 前台：數量 > 1 顯示灰色 ×N；= 1 不顯示
- [ ] 訂單：各商品數量 = 方案購買數量 × 設定數量
- [ ] 庫存：依照正確數量扣除
- [ ] 遷移：exclude_main_course='no' 的舊方案自動加入課程
- [ ] 遷移：exclude_main_course='yes' 的舊方案不修改
- [ ] 向下相容：無 pbp_product_quantities 的舊方案正常運作（預設 1）
- [ ] PHP lint + PHPStan level 9 通過
- [ ] TypeScript 編譯通過
- [ ] 所有既有 E2E 測試通過
