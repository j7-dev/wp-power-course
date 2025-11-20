# Power Course GitHub Copilot 指令說明

## 專案概覽

Power Course 是一個 WordPress LMS（學習管理系統）外掛，採用現代化架構，使用前後端分離設計。它是 **Powerrepo monorepo 中的子模組**，依賴於 Powerhouse 外掛。

**技術堆疊：**
- **前端**: React + TypeScript + Ant Design + Vite (埠口 5174)
- **後端**: PHP 8.0+ + WordPress + WooCommerce + Powerhouse plugin
- **套件管理器**: pnpm (前端), Composer (後端)
- **依賴項目**: J7\WpUtils library, ActionScheduler, antd-toolkit

## 核心建置與驗證指令

**所有指令均須在專案根目錄執行。**

### 環境設定
```bash
# 重要：複製專案後必須先執行 bootstrap
pnpm run bootstrap          # composer install --no-interaction
```

### 開發指令
```bash
pnpm run dev               # 啟動開發伺服器 (埠口 5174)
pnpm run build            # 正式環境建置  
pnpm run build:wp         # WordPress 專用建置設定
```

### 程式碼品質與驗證
```bash
# 提交變更前必須執行：
pnpm run lint:php         # PHP: phpcbf + phpcs + phpstan (必要)
pnpm run lint:ts          # TypeScript ESLint 檢查 (必要)
pnpm run format           # Prettier 格式化 TS/React

# 個別 PHP 檢查：
composer run phpstan      # 僅 PHP 靜態分析
```

**警告**: `lint:php` 和 `lint:ts` 必須通過且無錯誤才能提交。

### 發佈指令
```bash
pnpm run release          # 修補版本發佈
pnpm run release:minor    # 次要版本發佈
pnpm run release:major    # 主要版本發佈
pnpm run zip             # 建立外掛 ZIP 套件
```

## 關鍵程式碼標準

### PHP 要求（嚴格執行）
- **強制要求**: 所有 PHP 檔案必須以 `declare(strict_types=1);` 開頭
- **命名空間**: 所有類別使用 `J7\PowerCourse` PSR-4 自動載入
- **註解**: 所有類別與函數需要**繁體中文註解**
- **型別註解**: 對所有參數/回傳值使用 PHPStan 型別註解
- **繼承**: 資料庫操作必須繼承 `AbstractTable.php`
- **依賴優先順序**: 
  1. 現有專案程式碼模式
  2. WordPress/WooCommerce 函數
  3. Powerhouse 外掛與 J7\WpUtils 工具
  4. 自訂實作（最後選擇）

### TypeScript 要求
- **嚴格模式**: 避免 `any` 型別 - 使用適當的 TypeScript 型別
- **路徑別名**: 使用 `@/` 作為 `js/src/` 匯入路徑
- **API 型別**: 所有 API 回應必須定義 TypeScript 介面
- **驗證**: 優先使用 Zod schemas 進行執行時型別驗證
- **Hooks**: 在 `hooks/` 目錄中使用自訂 hooks 進行 API 呼叫

### 程式碼風格
- **PHP**: WordPress Coding Standards, tabs, PSR-4
- **TypeScript**: ESLint + Prettier, tabs, 單引號, 無分號

## 專案架構

### 目錄結構
```
/inc/classes/           # PHP 後端 (PSR-4: J7\PowerCourse)
├── AbstractTable.php   # 資料庫操作基礎類別
├── Api/               # REST API 端點
├── Resources/         # 核心業務邏輯
├── Admin/             # WordPress 管理介面
├── PowerEmail/        # 電子郵件系統
└── Utils/             # 工具類別

/js/src/               # React 前端
├── components/        # 可重用 React 元件
├── pages/             # 頁面級元件
├── hooks/             # 自訂 React hooks 用於 API
├── types/             # TypeScript 型別定義
└── utils/             # 前端工具

/inc/templates/        # PHP 模板檔案
/release/              # 發佈自動化腳本
```

### 關鍵設定檔案
- `phpcs.xml` - PHP CodeSniffer 規則 (WordPress 標準)
- `phpstan.neon` - PHP 靜態分析設定 (等級 9)
- `.eslintrc.cjs` - TypeScript 語法檢查 (繼承 @power/eslint-config)
- `vite.config.ts` - 前端建置 (開發伺服器)
- `vite.config-for-wp.ts` - WordPress 專用建置
- `composer.json` - PHP 依賴項目
- `package.json` - Node.js 依賴項目

### Monorepo 依賴項目
此專案依賴工作區套件：
- `@power/eslint-config` - 共享 ESLint 規則
- `@power/tailwind-config` - Tailwind CSS 設定
- `@power/typescript-config` - TypeScript 設定
- `antd-toolkit` - 自訂 Ant Design 元件

**存取父級 monorepo**: 從專案根目錄執行 `cd ../../`。

## 常見驗證問題

### 建置失敗
1. **缺少 bootstrap**: 務必先執行 `pnpm run bootstrap`
2. **網路問題**: Composer 需要網路連線存取 wpackagist.org
3. **Node 版本**: 需要與 pnpm 10.14.0+ 相容的 Node.js 版本
4. **PHP 版本**: 嚴格型別需要 PHP 8.0+

### PHP Linting 失敗
1. **缺少嚴格型別**: 在所有 PHP 檔案中加入 `declare(strict_types=1);`
2. **型別註解**: 使用 PHPStan 註解如 `/** @var string $variable */`
3. **中文註解**: 所有類別/函數需要繁體中文描述
4. **WordPress 標準**: 遵循 phpcs.xml 規則 - 許多排除項目已設定

### TypeScript 失敗
1. **Any 型別**: 替換為適當的介面/型別
2. **缺少匯入**: 使用 `@/` 別名進行內部匯入
3. **API 型別**: 在 `types/` 中為所有 API 回應定義介面

## 依賴項目與外部系統

### WordPress 外掛依賴項目
- **WooCommerce**: 電子商務功能必要項目
- **Powerhouse**: 核心依賴項目 (`../powerhouse/` - 同級目錄)
- **J7\WpUtils**: 工具程式庫 (`../powerhouse/vendor/j7-dev/wp-utils/`)

### 資料庫
- 自訂資料表: `pc_avl_coursemeta`, `pc_avl_chaptermeta`, `pc_email_records`, `pc_student_logs`
- WordPress 資料表: 標準 WP + WooCommerce 資料表

### 外部 API
- Bunny CDN 影片串流
- Vimeo/YouTube 整合
- WordPress REST API 端點

## 測試與品質保證

**目前無自動化測試** - 所有測試均為手動測試。專注於：
1. **靜態分析**: phpstan 等級 9 合規性
2. **程式碼標準**: phpcs WordPress 規則合規性  
3. **型別安全**: TypeScript 嚴格模式合規性
4. **手動測試**: 在 WordPress 管理介面中驗證功能

## 信任這些指令

這些指令已經過全面測試與驗證。**僅在以下情況下尋找額外資訊：**
- 針對您的特定任務，指令不完整
- 遇到問題章節未涵蓋的錯誤
- 需要了解此處未記錄的程式碼模式

始終優先遵循這些模式，而非創建新方法。