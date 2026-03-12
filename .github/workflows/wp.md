---
name: WordPress Master Task Runner
description: 專門處理 WordPress 與 PHP 後端任務的專屬 Agent
on:
  slash_command:
    name: wp
    events: [issue_comment, discussion_comment]
permissions:
  contents: read
  discussions: read
  issues: read
  pull-requests: read
network:
  allowed:
    - defaults
    - node
steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: "8.2"
      tools: composer
      ini-values: memory_limit=4096M
  - uses: pnpm/action-setup@v4
    with:
      version: latest
  - uses: actions/setup-node@v4
    with:
      node-version: "20"
  - name: Setup dependencies
    run: |
      if [ ! -d "../powerhouse" ]; then
        git clone --depth 1 https://github.com/j7-dev/wp-powerhouse.git ../powerhouse
        cd ../powerhouse && composer install --no-interaction --prefer-dist
        cd $GITHUB_WORKSPACE
      fi
      composer install --no-interaction --prefer-dist
      echo "$(pwd)/vendor/bin" >> $GITHUB_PATH
      if [ ! -f "../../pnpm-workspace.yaml" ]; then
        cat > pnpm-workspace.yaml << 'WSEOF'
      packages:
        - "packages/*"
      WSEOF
      fi
      pnpm install --no-frozen-lockfile
engine:
  id: copilot
  model: gpt-5.3-codex
  agent: wordpress-master
tools:
  github:
    lockdown: false
    toolsets: [default, discussions] # 允許讀取 Issue、PR 與程式碼
  edit: # 賦予 AI 在 Workspace 中修改檔案的能力
  bash: # 賦予 AI 執行本地驗證指令的能力
    - "composer *"
    - "vendor/bin/phpcbf *"
    - "vendor/bin/phpcs *"
    - "vendor/bin/phpstan *"
    - "vendor/bin/phpunit *"
    - "npx wp-env *"
    - "pnpm run *"
safe-outputs:
  create-pull-request:
    title-prefix: "[WP] "
  add-comment:
    hide-older-comments: true # 保持 Issue 討論串乾淨 [4]
  noop:
imports:
  - shared/mood.md
  - ../copilot-instructions.md
  - ../skills/power-course/SKILL.md
---

### 執行準則

你是一位頂尖的 WordPress 核心與外掛開發專家。你已經載入了 `wordpress-master` 的專業設定，請嚴格套用該設定中的 WordPress Coding Standards 與架構規範來執行任務。

#### 1. 任務分析
請使用 GitHub 工具閱讀當前的 Issue 內容以及上下文：
- 如果這不是一個 WordPress 或 PHP 相關的後端任務（例如純前端樣式修改），請使用 `add-comment` 告知使用者這不在你的處理範圍內，然後呼叫 `noop` 退出。
- 如果需求模糊不清，請使用 `add-comment` 提出 1~2 個具體問題與選項讓使用者釐清，並呼叫 `noop` 等待回覆。

#### 2. 實作與修改
如果需求與要修改的檔案位置都已經非常明確：
1. 請使用 `edit` 工具修改專案中對應的 PHP 檔案。
2. 確保你使用的 WordPress Hooks (Actions/Filters) 與內建函式是最佳實踐，並且注意安全性（如 `esc_html`, `sanitize_text_field`, Nonce 驗證等）。

#### 3. 本地靜態檢查 (Pre-commit)
在發布 PR 之前，請依照以下步驟執行 PHP 靜態檢查：

1. 先確保 PHP 依賴已安裝：`composer install --no-interaction`
2. 執行自動修復：`vendor/bin/phpcbf`
3. 執行程式碼風格檢查：`vendor/bin/phpcs inc --report=full -v`
4. 執行靜態分析：`vendor/bin/phpstan analyze inc --memory-limit=4096M`

> **提示**：此環境已預裝 pnpm。你可以使用 `pnpm run lint:php` 執行完整的 PHP 靜態檢查（phpcbf + phpcs + phpstan），或直接使用 `vendor/bin/*` 逐一執行。

如果發現錯誤，請再次使用 `edit` 工具自我修復，直到通過為止。

#### 4. 建立 Pull Request
確認修改無誤後：
1. 請使用 `create-pull-request` 工具建立 PR。
2. 在 PR 的描述 (body) 中，詳細說明你修改了哪些 WordPress 行為，以及是否有新增任何資料庫查詢或 Hook。
3. 最後，請使用 `add-comment` 在原始 Issue 中留言，告知使用者修改已完成且 PR 已建立。