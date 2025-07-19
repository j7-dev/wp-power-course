# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概覽

Power Course 是一個 WordPress 課程外掛，採用前後端分離架構。前端在 refine.dev 這個大框架內使用 React + TypeScript + Ant Design，後端使用 PHP + WordPress + Woocommerce 外掛 + Powerhouse 外掛 + composer。

Power Course 位於 Powerrepo 是這個 monorepo 底下的 submodule
Power Course 依賴的 Powerhouse 也是 Powerrepo 這個 monorepo 底下的 submodule
如果需要查看 Powerrepo 的文件內容，可以 cd ../../ 進入 Powerrepo 的根目錄

## 常用指令

### 開發環境
```bash
pnpm run dev          # 啟動開發伺服器 (port 5174)
pnpm run build        # 建置生產版本
pnpm run build:wp     # 使用 WordPress 專用配置建置
```

### 代碼品質檢查
```bash
pnpm run lint:php     # 後端 php 代碼品質檢查執行 phpcbf & phpcs & phpstan
pnpm run lint:ts      # 前端 typescript eslint 代碼品質檢查
pnpm run format       # 格式化 TypeScript/React 代碼
composer run phpstan # 執行 PHP 靜態分析
```

### 發佈流程
```bash
pnpm run release         # 發佈 patch 版本
pnpm run release:minor   # 發佈 minor 版本
pnpm run release:major   # 發佈 major 版本
pnpm run zip            # 打包外掛檔案
```

## 核心架構

### 前端架構 (js/src/)
- **components/**: React 組件庫，按功能模組分類
- **pages/**: 頁面級組件，對應各管理頁面
- **hooks/**: 自定義 React Hooks，處理 API 呼叫和狀態管理
- **types/**: TypeScript 類型定義，包含 API 回應格式
- **utils/**: 工具函數和共用邏輯

### 後端架構 (inc/)
- **classes/Api/**: REST API 端點實作，遵循 PSR-4 命名空間 `J7\PowerCourse`
- **classes/Tables/**: 資料庫抽象層，繼承 `AbstractTable.php`
- **classes/**: 核心功能類別，如課程管理、學生系統等

### 關鍵設計模式
- **抽象表格類別**: 所有資料庫操作繼承 `AbstractTable.php`
- **API 端點**: 使用 WordPress REST API 架構，統一錯誤處理
- **React Query**: 前端使用 TanStack Query 處理 API 狀態管理
- **Ant Design Pro**: 使用 ProTable、ProForm 等高階組件

## 主要功能模組

### 課程系統
- **Courses**: 課程主體管理，包含章節、學生、商品關聯
- **Chapters**: 章節內容管理，支援視頻、文件、測驗
- **Students**: 學生註冊、進度追蹤、證書生成

### 媒體播放
- **視頻播放器**: 支援 Bunny、Vimeo、YouTube、自製 VidStack
- **HLS 串流**: 使用 hls.js 處理串流媒體
- **浮水印**: PDF 和視頻動態浮水印功能

### 商業邏輯
- **WooCommerce 整合**: 商品、訂單、付款狀態同步
- **權限控制**: 課程存取權限、過期日期管理
- **分析報表**: 學習進度、完成率統計

## 開發注意事項
請遵照 .claude/instructions.md 文檔的規範進行開發

### Monorepo 依賴
此專案使用 workspace 依賴，相關配置位於：
- `@power/eslint-config`: ESLint 共用配置
- `@power/tailwind-config`: Tailwind CSS 配置
- `@power/typescript-config`: TypeScript 配置


### Wordpress 依賴
- 依賴 Woocommerce Plugin
- 依賴 Powerhouse Plugin
- Powerhouse 內有個 lib J7\WpUtils 非常常使用

### 參考文件
- Powerhouse php 文件參考路徑 (與 Power Course同一個 monorepo 內): ../powerhouse/inc/classes/Domains/
- J7\WpUtils php 文件參考路徑 (Powerhouse 的 php 依賴): ../powerhouse/vendor/j7-dev/wp-utils/src
- antd-toolkit 前端組件參考路徑 (與 Power Course同一個 monorepo 內): ../../packages/antd-toolkit/lib
- refine.dev 文件: https://refine.dev/docs/
- WordPress 文件: https://developer.wordpress.org/reference/
- Woocommerce 文件: https://woocommerce.github.io/code-reference/
