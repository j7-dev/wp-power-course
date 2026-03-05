module.exports = {
  env: {
    node: true, // 支援 Node.js 環境的全局變數 (如 process, __dirname)
    browser: true, // 支援瀏覽器環境的全局變數 (如 window, document)
    es2021: true, // 支援 ES2021 語法特性
    commonjs: true, // 支援 CommonJS 模組系統 (require/module.exports)
  },
  parser: "@typescript-eslint/parser",
  extends: [
    "plugin:@typescript-eslint/recommended", // TypeScript 推薦規則
    "plugin:@wordpress/eslint-plugin/custom", // WordPress 自定義規則
    "plugin:@wordpress/eslint-plugin/esnext", // WordPress ES6+ 規則
    "plugin:@wordpress/eslint-plugin/jsdoc", // WordPress JSDoc 規則
    "plugin:react/recommended", // React 推薦規則
    "plugin:react-hooks/recommended", // React Hooks 規則
    "plugin:jsx-a11y/recommended", // 無障礙性檢查
    "plugin:import/recommended", // 導入規則
    "plugin:import/typescript", // TypeScript 導入規則
    "eslint-config-prettier", // 關閉與 Prettier 衝突的規則
    "prettier", // Prettier 格式化規則
    "plugin:prettier/recommended", // Prettier 推薦配置
  ],
  parserOptions: {
    ecmaVersion: "latest", // 使用最新的 ECMAScript 版本
    sourceType: "module", // 使用 ES6 模組語法 (import/export)
    ecmaFeatures: {
      jsx: true, // 支援 JSX 語法
    },
    project: "./tsconfig.json", // TypeScript 配置檔案路徑
  },
  plugins: [
    "react",
    "react-hooks",
    "@typescript-eslint",
    "jsx-a11y",
    "import",
    "prettier",
  ],
  rules: {
    "quote-props": "off",
    "jsdoc/check-param-names": "off", // 暫時關閉有問題的 jsdoc 規則
    "jsdoc/require-param": "off", // 防止其他 jsdoc 相關問題
    "jsdoc/require-param-type": "off", // 關閉參數類型檢查
    "jsdoc/require-param-name": "off", // 關閉參數名稱檢查
    "jsdoc/require-param-description": "off", // 關閉參數描述檢查
    "jsdoc/valid-types": "off", // 關閉類型驗證
    "@typescript-eslint/no-explicit-any": "warn", // TypeScript 規則
    "@wordpress/no-unused-vars-before-return": "off", // WordPress 規則
    "@typescript-eslint/ban-types": "off", // TypeScript 規則
    "@typescript-eslint/interface-name-prefix": "off", // TypeScript 規則
    "@typescript-eslint/explicit-function-return-type": "off", // TypeScript 規則
    "@typescript-eslint/no-shadow": "error", // TypeScript 規則
    "@typescript-eslint/ban-ts-comment": "off", // TypeScript 規則
    "@typescript-eslint/no-unused-vars": [
      "warn",
      {
        argsIgnorePattern: "^_",
        varsIgnorePattern: "^_",
      },
    ],
    "react/prop-types": "off", // React 規則
    "react/react-in-jsx-scope": "off", // React 規則
    "react/jsx-uses-react": "off", // React 17+ 不需要
    "react/jsx-uses-vars": "error", // 檢查 JSX 中使用的變數
    "react-hooks/rules-of-hooks": "error", // Hooks 規則檢查
    "react-hooks/exhaustive-deps": "warn", // Hooks 依賴檢查
    "import/order": [
      "error",
      {
        groups: [
          "builtin", // Node.js 內建模組
          "external", // 第三方模組
          "internal", // 內部模組
          "parent", // 父目錄模組
          "sibling", // 同級目錄模組
          "index", // 索引檔案
        ],
        "newlines-between": "always", // 不同組之間空行
        alphabetize: {
          order: "asc", // 按字母順序排序
          caseInsensitive: true, // 忽略大小寫
        },
      },
    ],
    "import/no-unresolved": "off", // 關閉模組解析檢查 (Vite 處理)
    "import/extensions": [
      "error",
      "ignorePackages",
      {
        js: "never",
        jsx: "never",
        ts: "never",
        tsx: "never",
      },
    ],
    semi: ["error", "never"], // 程式碼風格規則
    quotes: ["error", "single"], // 程式碼風格規則
    "no-console": ["warn"], // 程式碼風格規則
    "no-debugger": "error", // 程式碼風格規則
    "array-callback-return": "off", // 程式碼風格規則
    "no-duplicate-imports": "error", // 程式碼風格規則
    "linebreak-style": "off", // 程式碼風格規則
    "no-unused-vars": "off", // 程式碼風格規則
    "no-shadow": "error", // 程式碼風格規則
    camelcase: "off", // 程式碼風格規則
    "prefer-const": "error", // 程式碼風格規則
    "no-var": "error", // 程式碼風格規則
    "lines-around-comment": [
      "error",
      {
        beforeBlockComment: true, // 註解規則
        afterBlockComment: false, // 註解規則
        beforeLineComment: true, // 註解規則
        afterLineComment: false, // 註解規則
        allowBlockStart: true, // 註解規則
        allowBlockEnd: true, // 註解規則
        allowObjectStart: true, // 註解規則
        allowObjectEnd: true, // 註解規則
        allowArrayStart: true, // 註解規則
        allowArrayEnd: true, // 註解規則
      },
    ],
    "jsdoc/valid-types": "off", // JSDoc 規則
    "prettier/prettier": [
      "error",
      {
        endOfLine: "auto", // Prettier 格式化規則
        useTabs: true, // Prettier 格式化規則
        tabWidth: 2, // Prettier 格式化規則
        semi: false, // 不使用分號
        singleQuote: true, // 使用單引號
        trailingComma: "es5", // 使用尾隨逗號
        "prettier-multiline-arrays-set-threshold": 1, // Prettier 格式化規則
      },
    ],
  },
  overrides: [
    {
      files: ["*.d.ts"], // TypeScript 宣告文件
      rules: {
        "no-undef": "off", // TypeScript 規則
        "no-var": "off", // TypeScript 規則
      },
    },
    {
      files: ["*.js", "*.jsx"], // JavaScript 文件
      rules: {
        "@typescript-eslint/no-var-requires": "off", // TypeScript 規則
      },
    },
    {
      files: ["*.tsx", "*.jsx"], // React 組件文件
      rules: {
        "react/prop-types": "off", // 關閉 PropTypes 檢查
        "@typescript-eslint/no-unsafe-assignment": "off",
        "@typescript-eslint/no-unsafe-return": "off",
        "@typescript-eslint/restrict-template-expressions": "off",
        "@typescript-eslint/no-unsafe-call": "off",
        "@typescript-eslint/no-unsafe-member-access": "off",
      },
    },
  ],
  globals: {
    JSX: "readonly", // JSX 類型 (只讀)
    window: "readonly", // 瀏覽器 window 物件
    React: "readonly", // React 物件
    document: "readonly", // 瀏覽器 document 物件
    wpApiSettings: "readonly", // WordPress API 設定
    process: "readonly", // Node.js process 物件
    __dirname: "readonly", // Node.js __dirname
    __filename: "readonly", // Node.js __filename
  },
  settings: {
    react: {
      version: "detect", // 自動檢測 React 版本
    },
    "import/resolver": {
      typescript: {
        alwaysTryTypes: true, // 總是嘗試解析 TypeScript
        project: "./tsconfig.json", // TypeScript 配置檔案
      },
      node: {
        extensions: [".js", ".jsx", ".ts", ".tsx"], // 支援的檔案副檔名
      },
    },
  },
};
