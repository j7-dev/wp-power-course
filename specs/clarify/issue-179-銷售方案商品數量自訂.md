# Issue #179 — 銷售方案商品數量自訂：需求澄清紀錄

## 澄清決策總表

| # | 問題 | 分類 | 決策 | 說明 |
|---|------|------|------|------|
| Q1 | 數量儲存格式 | 工程 | **A** | 新增獨立 meta key `pbp_product_quantities` (JSON)，`pbp_product_ids` 不動 |
| Q2 | 原價是否乘以數量 | 情境 | **A** | 原價 = 各商品 (單價 × 數量) 加總 |
| Q3 | 數量=1 時前台顯示 | 情境 | **A** | qty > 1 才顯示 ×N，qty = 1 不顯示 |
| Q4 | 當前課程處理方式 | 工程 | **自訂** | 重構為與添加商品一致的邏輯和 UI；建立時自動添加 ×1，可移除 |
| Q5 | 數量上限 | 情境 | **B** | 上限 999 |
| Q6 | WC 訂單顯示格式 | 情境 | **B** | 名稱含 ×N，WC qty = 購買份數（如 "方案 - 講義 ×3" × 1） |
| Q7 | 多份銷售方案庫存 | 情境 | **A** | 庫存 = 購買份數 × 方案內數量（2份 × 3本 = 扣6） |

## Q4 詳細決策：當前課程重構

用戶原始回答：
> 當前課程重構為與添加商品的邏輯和 UI 一樣
> 但是創建銷售方案時，會預設添加 "當前課程" x 1
> 並且也可以移除 "當前課程"

### 影響範圍

1. **移除 `exclude_main_course` 開關** — UI 不再顯示此切換
2. **當前課程加入 `pbp_product_ids`** — 與其他商品統一管理
3. **建立時自動添加** — 課程 ID 預設出現在商品列表，qty=1
4. **可移除** — 管理員可刪除當前課程，也可重新搜尋添加
5. **搜尋不排除當前課程** — 前端搜尋 API 移除 `exclude: [courseId]` 過濾

### 遷移策略

- `exclude_main_course = 'no'` 的舊方案：將 course ID 加入 `pbp_product_ids`，qty 設為 1
- `exclude_main_course = 'yes'` 的舊方案：不加入 course ID（保持現有行為）
- 遷移在外掛升級 hook 中自動執行

## Q6 工程補充：庫存扣減機制

用戶選擇 B（名稱含 ×N，WC qty = 購買份數），因此需要自訂庫存扣減：

1. `$order->add_product()` 的 qty = 購買份數（cart qty）
2. Order item meta `_pbp_qty` 記錄方案內商品數量
3. 庫存實際扣減 = WC qty × `_pbp_qty`，透過 WooCommerce stock reduction hook 實現

## 產出規格檔案

| 檔案 | 說明 |
|------|------|
| `specs/features/bundle/銷售方案商品數量自訂.feature` | 數量 CRUD、驗證、向下相容 |
| `specs/features/bundle/銷售方案當前課程重構.feature` | 當前課程重構、遷移 |
| `specs/features/bundle/銷售方案數量原價計算.feature` | 原價/特價乘以數量 |
| `specs/features/bundle/銷售方案數量前台展示.feature` | 前台 ×N 顯示邏輯 |
| `specs/features/bundle/銷售方案數量訂單處理.feature` | 訂單品項、庫存扣減 |
| `specs/api/api.yml` (updated) | BundleProduct schema + API endpoints 新增 pbp_product_quantities |
| `specs/entity/erm.dbml` (updated) | bundle_products table 新增欄位與文件 |
