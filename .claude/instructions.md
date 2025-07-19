### PHP 程式碼品質
- 優先參考專案內其他寫過的程式碼風格以及使用的類及函數，避免重複造輪子
- 再來考慮使用 wordpress, woocommerce 的特性以及函數，避免重複造輪子
- 再來考慮使用 Powerhouse 外掛, J7\WpUtils 的特性以及函數，避免重複造輪子
- 需要符合 phpcs 以及 phpstan 的規則，盡可能不要 ignore 規則
- 所有 php 檔案都必須宣告 declare(strict_types=1); 強制嚴格型別檢查
- 函數都必須要有繁體中文註解，一句話講清楚用途，並且函數的輸入、輸出變數要都有 phpstan 的型別註釋以及強型別定義
- 如果有定時任務優先使用 ActionScheduler 來實作，而非 wp-cron
- 提交前必須通過 `pnpm run lint:php` 檢查，有錯誤請自動修正
- PHP 8.0+ 語法，使用 PSR-4 autoloading
- 所有 API 端點需繼承適當的基礎類別
- 資料庫操作使用抽象表格類別，避免直接 SQL
- 遵循 WordPress Coding Standards
- class 類必須要有繁體中文註解，一句話講清楚用途
- class 類的屬性定義，盡可能使用單行中文註解，如下
```
/** @var string 文章名稱 */
public string $name;
```

### 前端 程式碼品質
- 優先參考其他寫過的程式碼風格以及使用的類及函數，避免重複造輪子
- 再來考慮使用 antd, @ant-design/pro-components, @ant-design/pro-editor, antd-toolkit 這個 library 的組件還有 custom hooks 來實作功能，避免重複造輪子
- typescript 盡量避免使用 any
- 如果可以，使用 zod/v4 來做 runtime 的型別驗證，然後由 zod 的 schema 來定義 typescript 的型別
- 前端: ESLint + Prettier，使用 tabs、單引號、無分號
- 路徑別名: `@/` 映射到 `js/src/`
- 使用 Ant Design 組件庫，遵循既有設計規範
- API 呼叫統一使用 custom hooks，位於 `hooks/` 目錄
- TypeScript 嚴格模式，所有 API 回應需定義類型
- Power Course 沒有自己的 CSS，所有 CSS (scss & tailwind) 都寫在 Powerhouse 內
- 提交前必須通過 `pnpm run lint:ts` 檢查，有錯誤請自動修正

### 錯誤處理與測試標準
- 目前專案沒有任何的測試，都是手動測試，所以暫時不要求測試，等到有自動化測試再補充

### 任務執行
- 任何修改前，先確認是否在 master 分支，如果是，提醒我是否確認創建新的分支
- 任務計畫都會寫在 .claude/tasks/ 目錄下，請先閱讀任務目標跟計畫
- 所有計畫必須先讓我審核，確認無誤後才能動工，並要求每個步驟都需經過 Review
- 過程中，如果有發現代碼混亂，複雜度過高或者效能低下，或可讀性差，也可以簡單先提出來，之後再作改善
