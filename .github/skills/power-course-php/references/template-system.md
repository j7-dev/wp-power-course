# 模板系統詳細參考

## 目錄結構

```
inc/templates/
  components/        # 可重用 UI 元件（badge, tabs, alert, button, card, icon...）
  pages/             # 頁面模板（course-product, classroom, my-account, 404）
  single-pc_chapter.php  # 章節詳情頁
  course-product-entry.php  # 課程商品入口
```

## 模板覆寫

主題可透過在 `{theme}/power-course/` 放置同名檔案覆寫任何模板。  
由 `Templates\Templates.php` 統一管理覆寫邏輯。

## CSS 規範

- DaisyUI utility class 前綴：`pc-`（如 `pc-btn`, `pc-modal`, `pc-badge`, `pc-collapse`）
- Tailwind class 前綴：`tw-`（如 `tw-flex`, `tw-mt-4`）
- Power Course **無自有 CSS**，所有樣式定義在 Powerhouse 外掛

## Ajax 模板

```php
// Templates\Ajax.php 處理前端 AJAX 請求對應的模板渲染
// hook: wp_ajax_{action}
```

## 常用 Component 清單

| Component | 功能 |
|-----------|------|
| badge | 標籤/狀態徽章 |
| tabs | 頁籤切換 |
| alert | 提示訊息 |
| button | 通用按鈕 |
| card | 卡片容器 |
| icon | 圖示 |
| video | 影片播放器 |
| rate | 星級評分 |
| countdown | 倒數計時 |
| stock | 庫存顯示 |
| price | 價格顯示 |
| progress | 進度條 |
| review | 評論 |

## 頁面模板

| 頁面 | 路徑 |
|------|------|
| 課程商品頁 | pages/course-product.php |
| 教室（classroom）頁 | pages/classroom.php |
| 我的帳戶頁 | pages/my-account.php |
| 404 頁 | pages/404.php |
