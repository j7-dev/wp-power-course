# Clarify Session 2026-03-27 — 銷售方案商品數量

## Issue

#134 銷售方案可以自由填數量

## Idea

讓銷售方案的組合裡面的每個商品都可以自由選擇數量，包含當前課程。
用戶可以在後台課程編輯創建一個銷售方案，內容包含 2 個課程 + 3 件 T-shirt，
結帳後課程庫存減 2，T-shirt 庫存減 3。

## Q&A

- Q1: 數量的資料模型 — (A) 新增 `pbp_product_quantities` JSON meta (B) 改為新結構 `pbp_product_items` (C) 允許重複 ID (D) 最佳實踐 → A: 選 D（最佳實踐），但必須兼容舊資料。決策：新增 `pbp_product_quantities` meta key（JSON 格式 `{"product_id": qty}`），舊資料 fallback 數量 1，零遷移。
- Q2: 數量輸入的 UI 交互 — (A) 卡片右側 InputNumber (B) 卡片下方獨立一行 (C) 最佳實踐；目前課程是否也加數量？ → A: 選 A（卡片右側），目前課程也要加數量 input。
- Q3: 數量輸入限制 — (A) min 1, max 99 (B) min 1, 無上限 (C) 依庫存動態 (D) 最佳實踐 → A: 選 B（min 1，無上限，步進 1）。
- Q4: 價格自動計算是否乘上數量 — (A) 乘上數量 `Σ(price × qty)` (B) 不乘，管理員手動考量 (C) 最佳實踐 → A: 選 A（自動乘上數量）。
- Q5: 前台銷售方案卡片的數量顯示 — (A) 所有商品都顯示 `×N` (B) 只有 >1 才顯示 (C) 前台不顯示 (D) 最佳實踐 → A: 選 A（所有商品都顯示 `×N`，包含 ×1）。
- Q6: 訂單處理的庫存扣除邏輯 — (A) `order qty × item qty` (B) 不論數量都扣 1 (C) 最佳實踐 → A: 選 A（`order qty × item qty`，買 2 份方案含課程 ×2 + T-shirt ×3 → 課程扣 4，T-shirt 扣 6）。
- Q7: 課程開通與數量的關係 — (A) 庫存扣數量，但開通只一次 (B) 課程期限延長 N 倍 (C) 課程不應設 >1 (D) 最佳實踐 → A: 暫時選 A（庫存扣數量，開通只一次）。未來另開 issue 處理團體報名功能（一人購買多份授權再分配）。

## 決策摘要

| # | 主題 | 決策 |
|---|------|------|
| Q1 | 資料模型 | 新增 `pbp_product_quantities` JSON meta，`pbp_product_ids` 保留不動，舊資料 fallback 為 1 |
| Q2 | UI 交互 | 每個已選商品卡片右側加 `InputNumber`，包含目前課程 |
| Q3 | 數量限制 | min=1, max=無上限, step=1 |
| Q4 | 價格計算 | `regular_price = Σ(商品原價 × 數量)` |
| Q5 | 前台顯示 | 所有商品名稱後顯示 `×N` |
| Q6 | 庫存扣除 | `order_qty × item_qty` |
| Q7 | 課程開通 | 庫存按數量扣，但課程開通每位學員只一次 |

## 影響範圍

### PHP 後端
- `inc/classes/BundleProduct/Helper.php` — 新增 `get_product_quantities()` / `set_product_quantities()` 方法
- `inc/classes/Api/Product.php` — `handle_special_fields()` 處理 `pbp_product_quantities` 儲存；`format_product_details()` 回傳數量
- `inc/classes/Resources/Order.php` — `_handle_add_course_item_meta_by_order()` 使用 `item_qty × bundle_qty` 作為 `add_product()` 的數量
- `inc/templates/components/card/bundle-product.php` — 讀取數量並在商品名稱後顯示 `×N`

### React 前端
- `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx` — 在已選商品卡片和課程卡片加 `InputNumber`
- `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx` — `getPrice()` 計算時乘上數量
- `js/src/pages/admin/Courses/Edit/tabs/CourseBundles/Edit/atom.tsx` — 可能需要新增 quantities 的 atom 或擴展 selectedProducts 結構
- `js/src/components/product/ProductTable/types/index.ts` — `TBundleProductRecord` 新增 `pbp_product_quantities` 型別

## 技術依賴

無新增外部套件。所有變更使用現有技術棧（Ant Design `InputNumber`、WordPress `post_meta`）。
