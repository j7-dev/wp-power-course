# Event Storming — 課程線性觀看功能

## Bounded Context: Classroom / Chapter Progress

---

## Domain Events（橘色）

| # | Event | 觸發條件 |
|---|-------|---------|
| E1 | `LinearModeEnabled` | 管理員對課程開啟線性觀看模式 |
| E2 | `LinearModeDisabled` | 管理員對課程關閉線性觀看模式 |
| E3 | `ChapterFinished` | 學員標記章節完成（既有事件） |
| E4 | `ChapterUnlocked` | 前一章節完成後，下一章節解鎖（前端 JS 局部解鎖） |
| E5 | `LockedChapterAccessAttempted` | 學員嘗試存取鎖定的章節（含直接 URL） |
| E6 | `LockedChapterAccessDenied` | 系統攔截學員對鎖定章節的存取（模板層或 API 層） |

---

## Commands（藍色）

| # | Command | Actor | Predecessors | Description |
|---|---------|-------|-------------|-------------|
| C1 | `EnableLinearMode` | 管理員 | — | 在課程編輯頁面開啟「線性觀看」開關，儲存 `enable_linear_mode` meta |
| C2 | `DisableLinearMode` | 管理員 | — | 關閉「線性觀看」開關，刪除或設為 `no` |
| C3 | `FinishChapter` | 學員 | 章節已解鎖 | 標記當前章節為已完成（既有 toggle-finish，線性模式下禁止取消完成） |
| C4 | `AccessChapter` | 學員 | 課程已授權 | 學員嘗試存取某個章節（模板載入或 API 請求） |

---

## Read Models（綠色）

| # | Read Model | 資料來源 | 消費者 |
|---|-----------|---------|--------|
| R1 | `CourseLinearModeSetting` | `postmeta.enable_linear_mode` | 管理員編輯頁面、教室模板、API |
| R2 | `ChapterLockStatus` | 計算：`enable_linear_mode` + `flatten_post_ids` + `finished_at` | 教室 sidebar、章節頁面、REST API |
| R3 | `NextUnlockedChapter` | 計算：第一個未完成章節 | 學員鎖定提示訊息（「請先完成 XX 章節」） |

---

## Aggregates（黃色）

| Aggregate | 包含的 Entity |
|-----------|--------------|
| `Course` (WC_Product) | 課程設定（`enable_linear_mode`） |
| `ChapterProgress` | 章節完成狀態（`pc_avl_chaptermeta.finished_at`） |

---

## Policies（紫色）

| # | Policy | When | Then |
|---|--------|------|------|
| P1 | 線性鎖定判斷 | 學員存取章節時 | 檢查：(a) 課程是否啟用線性模式 (b) 該章節在 flatten 順序中，前一個章節是否已完成（第一章節永遠解鎖） |
| P2 | 禁止取消完成 | 線性模式下學員嘗試 toggle-finish | 如果章節已完成 → 回傳錯誤，禁止取消 |
| P3 | 已完成章節保持可存取 | 中途開啟線性模式 | 已經有 `finished_at` 的章節，無論前面章節狀態如何，都可存取 |
| P4 | 管理員繞過 | 管理員或課程作者存取章節 | `current_user_can('manage_woocommerce')` → 跳過鎖定檢查 |
| P5 | JS 局部解鎖 | 學員完成章節後 | 前端 JS 收到成功回應後，局部更新 sidebar 中下一章節的鎖定圖示與可點擊狀態 |

---

## External Systems

| System | 互動方式 |
|--------|---------|
| WooCommerce Product Meta | 儲存 `enable_linear_mode` 設定 |
| `pc_avl_chaptermeta` 表 | 讀取 `finished_at` 判斷完成狀態 |

---

## 決策紀錄

| # | 問題 | 決策 | 理由 |
|---|------|------|------|
| D1 | 取消完成行為 | 線性模式下禁止取消完成（不允許 toggle 回未完成） | 避免連鎖鎖定的複雜性，保持用戶體驗簡單 |
| D2 | 父章節是否參與排序 | 所有章節都參與線性順序，包含父章節和無內容章節 | 用戶明確要求，邏輯一致 |
| D3 | 管理員是否受限 | 管理員與講師繞過鎖定限制 | 管理員需自由瀏覽所有章節進行審核與排查 |
| D4 | 完成後導航行為 | 顯示「已完成！下一章節已解鎖」提示，不自動跳轉 | 不強制跳轉，尊重學員自主選擇 |
| D5 | meta_key 命名 | `enable_linear_mode` | 用戶指定 |
| D6 | 伺服器端攔截方式 | 模板層 + REST API 雙重攔截 | 最安全，防止前端與 API 兩個入口都被繞過 |
| D7 | 免費預覽豁免 | 第一期不做豁免機制 | 保持簡單，未來可擴充 |
| D8 | 前端更新方式 | JS 局部解鎖（不重新載入頁面） | 用戶明確要求，提升體驗流暢度 |
