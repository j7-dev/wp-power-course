---
name: Auto Ready Copilot PRs
description: Automatically marks Copilot draft PRs as ready and assigns Copilot reviewer
on:
  pull_request:
    types: [opened, reopened]
permissions:
  contents: read
  pull-requests: write
engine: copilot
safe-outputs:
  mark_pull_request_as_ready_for_review:
    max: 1
  add-reviewer:
    max: 1
    reviewers: [copilot]
---
### 任務
1. 請檢查觸發這個 Pull Request 的作者是不是 Copilot 機器人 (例如 `copilot` 或 `copilot-swe-agent`)。
2. 如果是 Copilot 建立的 PR，請呼叫 `mark_pull_request_as_ready_for_review` 將其轉為正式 PR。
3. 接著，請呼叫 `add-reviewer` 將這個 PR 指派給 `copilot` 進行程式碼審查。
4. 如果這不是 Copilot 建立的，請直接呼叫 `noop` 忽略。
