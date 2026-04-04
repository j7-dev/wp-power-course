# Issue #183 — 銷售方案可以自由填數量

> 澄清日期：2026-04-04
> 狀態：已確認

---

## 需求摘要

讓銷售方案中每個商品都可以自由設定數量，取代目前固定數量 1 的限制。同時移除「排除目前課程」開關，改為目前課程可新增/刪除的統一操作。

---

## 已確認決策

| # | 問題 | 決策 | 理由 |
|---|------|------|------|
| Q1 | 課程數量 > 1 時的行為 | **A：庫存扣 N，課程只開通 1 次**（去重機制不變） | 數量影響庫存，課程開通有去重 |
| Q2 | 「排除目前課程」開關 | **移除**：新建方案預設帶入「目前課程」×1 | 簡化 UI，統一操作邏輯 |
| Q3 | 組合原價計算 | **A：自動計算並填入，可手動覆蓋** | 維持現有彈性 |
| Q4 | 數量儲存格式 | **A：新增 `pbp_product_quantities` meta**，JSON `{"product_id": qty}` | 向下相容最好 |
| Q5 | 訂單 line item 呈現 | **A：單一 line item，qty = 購買份數 × 商品數量** | 簡潔、符合 WC 慣例 |
| Q6 | 數量上限 | **C：上限 999** | 站長自由度較高 |
| Q7 | 前端 API 傳送格式 | **A：新增 `pbp_product_quantities` 獨立欄位** | 改動最小 |
| Q8 | 目前課程是否可移除 | **B：可刪除**（像其他商品一樣刪除） | 等同用「刪除按鈕」取代舊開關 |
| Q9 | 舊方案遷移策略 | **條件遷移**：exclude=啟用 → 不變；exclude=未啟用 → 補入目前課程 ×1 | 尊重舊有設定 |

---

## 變更範圍

### 後端（PHP）

1. **`BundleProduct\Helper`**
   - 新增常數 `INCLUDE_PRODUCT_QUANTITIES_META_KEY = 'pbp_product_quantities'`
   - 新增方法 `get_product_quantities(): array` — 回傳 `["product_id" => qty]`
   - 新增方法 `set_product_quantities(array $quantities): void`
   - 新增方法 `get_product_qty(int $product_id): int` — 預設 1

2. **`Api\Product`**
   - `format_product()` 回應新增 `pbp_product_quantities` 欄位
   - `format_product()` 移除 `exclude_main_course` 欄位
   - `post_bundle_products_callback()` / `post_bundle_products_with_id_callback()` 處理新欄位
   - `handle_special_fields()` 新增 `pbp_product_quantities` 的 JSON 儲存邏輯

3. **`Resources\Order`**
   - `_handle_add_course_item_meta_by_order()` 中展開銷售方案商品時，讀取 `pbp_product_quantities` 取得各商品數量
   - `$order->add_product()` 的 qty 改為 `$item->get_quantity() * $product_qty`

4. **前台模板 `card/bundle-product.php`**
   - 讀取 quantities 資料
   - 數量 > 1 的商品旁邊顯示 `×N`

5. **資料遷移（on plugin update / lazy migration）**
   - 舊方案 `exclude_main_course` = `no` 或不存在 → `pbp_product_ids` 加入 `link_course_id`，`pbp_product_quantities` 設 `{course_id: 1}`
   - 舊方案 `exclude_main_course` = `yes` → 不動

### 前端（React / TypeScript）

1. **`BundleForm.tsx`**
   - 移除 `FiSwitch`（排除目前課程開關）
   - 目前課程改為可刪除的商品項目（與其他商品一致）
   - 每個商品項目（含目前課程）旁邊新增 `InputNumber` 數量欄位
   - 預設值 1，最小值 1，最大值 999，整數限制

2. **`atom.tsx`**
   - `selectedProductsAtom` 類型擴充，每個商品包含 `qty` 欄位

3. **`utils/index.tsx`**
   - `getPrice()` 函數計算加入數量因子：`product.price × qty`
   - 移除 `excludeMainCourse` 參數

4. **`ProductPriceFields/index.tsx`**
   - 移除 `exclude_main_course` 相關邏輯

5. **表單提交**
   - 新增 `pbp_product_quantities` hidden field，值為 JSON 字串 `{"id": qty}`

---

## 技術依賴

- 無新 library 需求（使用 Ant Design 現有的 `InputNumber` 元件）
