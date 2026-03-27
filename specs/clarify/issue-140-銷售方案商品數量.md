# 需求澄清紀錄：Issue #140 銷售方案商品數量自訂

## 需求來源
- GitHub Issue: #140
- 標題: 銷售方案可以自由填數量
- 提出者: j7-dev

## 需求摘要
讓銷售方案中每個商品可自訂數量（包含當前課程），結帳後庫存按數量正確扣減。

## 澄清狀態: ✅ 完成（需求已充分）

### 需求分析
| 項目 | 狀態 | 內容 |
|------|------|------|
| 目標 | ✅ 明確 | 每個綑綁商品可自訂數量 |
| 驗收條件 | ✅ 明確 | 有輸入框可自由輸入數量 |
| 情境 | ✅ 明確 | 2個課程 + 3件T-shirt，庫存正確扣減 |
| 邊界條件 | ✅ 已推導 | 數量 >= 1，向後相容，多份購買時倍數扣減 |

### 設計決策（從代碼分析推導）

1. **資料儲存**: 新增 `pbp_product_quantities` meta key，以 JSON 字串儲存（單一 row）
   - 理由：與既有 `pbp_product_ids`（多 row）互補，避免改動既有結構
   - 向後相容：meta 不存在時預設所有商品數量為 1

2. **價格計算**: `getPrice()` 需乘以數量
   - 原公式: `sum(product.price)`
   - 新公式: `sum(product.price × quantity)`

3. **庫存扣減**: 訂單處理時 `qty = bundle_order_qty × per_product_qty`
   - 原邏輯: `$qty = $item->get_quantity()` 對所有綑綁商品一律
   - 新邏輯: `$qty = $item->get_quantity() * $per_product_quantity`

4. **前台顯示**: 數量 > 1 時顯示 "x{N}" 標示，數量 = 1 時不顯示

5. **當前課程**: 也支援數量設定（除非排除主課程）

## 涉及檔案清單

### PHP 後端
| 檔案 | 變更類型 | 說明 |
|------|----------|------|
| `inc/classes/BundleProduct/Helper.php` | 修改 | 新增 quantity meta 常數與存取方法 |
| `inc/classes/Api/Product.php` | 修改 | create/update API 接受 quantities |
| `inc/classes/Resources/Order.php` | 修改 | 結帳時讀取 per-product quantity |
| `inc/templates/components/card/bundle-product.php` | 修改 | 前台卡片顯示數量 |

### React/TypeScript 前端
| 檔案 | 變更類型 | 說明 |
|------|----------|------|
| `js/src/pages/admin/.../BundleForm.tsx` | 修改 | 加入 InputNumber |
| `js/src/pages/admin/.../utils/index.tsx` | 修改 | getPrice 加入數量乘法 |
| `js/src/pages/admin/.../atom.tsx` | 修改 | 狀態增加 quantities |
| `js/src/components/product/ProductTable/types/index.ts` | 修改 | 型別增加 quantities |

## 產出規格檔案
- `specs/features/bundle-quantity/銷售方案商品數量.feature`
- `specs/activities/銷售方案商品數量.activity`
- `specs/api/bundle-quantity-api.yml`
- `specs/entity/bundle-quantity-erm.dbml`
