# Admin 擴充與 Compatibility 層詳細參考

## Admin 擴充

```php
// Admin\Entry        — 後台入口點，掛載 admin_menu 等 hooks
// Admin\Product      — WC 商品後台擴充（自定義欄位、批量操作）
// Admin\ProductQuery — WC 商品列表頁查詢過濾
```

### Admin\Product 功能

- 新增課程相關欄位到 WC 商品後台（is_course, limit_type 等）
- 批量操作：批量設定課程到期日、課程狀態
- 自定義 WC 商品後台欄位的 save 與 display

### Admin\ProductQuery 功能

- 過濾 WC 商品列表：支援篩選「是否為課程」
- 擴充 WC 後台商品搜尋，支援按課程 meta 篩選

---

## Compatibility 層

```php
// Compatibility\Course        — 處理課程 WooCommerce 相容性問題
// Compatibility\Chapter       — 處理章節相容性
// Compatibility\BundleProduct — 處理 Bundle Product 相容性
// Compatibility.php           — 主入口，統一初始化各 compatibility 模組
```

### 主要相容性處理

- **Course Compatibility**：確保課程 WC Product 的特殊行為（如購物車、結帳頁顯示）
- **Chapter Compatibility**：確保章節 CPT 的 permalink 及前台顯示正確
- **BundleProduct Compatibility**：Bundle 購買流程與 WC 的相容處理

---

## BundleProduct Helper

```php
// BundleProduct\Helper 提供 Bundle Product 輔助方法
// INCLUDE_PRODUCT_IDS_META_KEY = 'pbp_product_ids'
// LINK_COURSE_IDS_META_KEY = 'link_course_ids'

BundleProduct\Helper::get_bundle_product_ids( $course_id ); // 取得關聯 Bundle
BundleProduct\Helper::is_bundle_product( $product );         // 是否為 Bundle
```
