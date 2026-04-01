# 實作計劃：課程章節線性觀看 -- 測試覆蓋 (Issue #152)

## 概述

Issue #152 的功能實作已全部完成（PHP 後端鎖定邏輯、Template 阻擋、API is_locked 欄位、管理端 FiSwitch 開關、教室頁面 JS Toast 警告）。本計劃專注於為已完成的功能補充完整的測試覆蓋，包含 PHP Integration Test 與 Playwright E2E Test，並移除 feature files 上的 `@ignore` tag。

## 範圍模式：HOLD SCOPE

功能已實作完成，範圍明確限定於測試覆蓋。預估新增 3 個測試檔案，不涉及功能變更。

## 需求重述

- 為三份 Gherkin feature spec 補齊對應的自動化測試：
  1. `線性觀看章節鎖定.feature` -- 核心 DFS 鎖定/解鎖邏輯
  2. `存取被鎖定章節.feature` -- Template 層 + REST API 層阻擋
  3. `設定課程線性觀看模式.feature` -- 管理端 CRUD + 即時生效
- 移除 feature files 上的 `@ignore` tag
- 確保所有測試可獨立執行、可重複執行

## 架構變更

| 變更類型 | 檔案路徑 | 說明 |
|---------|---------|------|
| 新增 | `tests/Integration/Chapter/LinearModeChapterLockTest.php` | PHP 整合測試：章節鎖定邏輯 |
| 新增 | `tests/e2e/02-frontend/015-linear-mode-access-denied.spec.ts` | E2E 測試：Template 層阻擋鎖定章節存取 |
| 新增 | `tests/e2e/01-admin/linear-mode-setting.spec.ts` | E2E 測試：管理端線性觀看開關設定 |
| 修改 | `specs/features/chapter/線性觀看章節鎖定.feature` | 移除 `@ignore` tag |
| 修改 | `specs/features/chapter/存取被鎖定章節.feature` | 移除 `@ignore` tag |
| 修改 | `specs/features/course/設定課程線性觀看模式.feature` | 移除 `@ignore` tag |

## 資料流分析

### 測試覆蓋流程（PHP Integration Test）

```
SETUP DATA ──> CALL is_chapter_locked() ──> ASSERT RESULT
    |                    |                        |
    v                    v                        v
[create_course]    [各種組合]              [locked / unlocked]
[create_chapters]  - 未完成前置章節        [管理員豁免]
[enroll_user]      - 完成前置章節          [講師豁免]
[set_finished]     - 管理員/講師           [功能關閉]
                   - 功能關閉
                   - 取消完成
```

### 測試覆蓋流程（E2E -- Template 阻擋）

```
API SETUP ──> LOGIN AS ──> NAVIGATE TO CHAPTER URL ──> ASSERT PAGE
    |              |                  |                       |
    v              v                  v                       v
[create course]  [student]     [locked chapter]          [顯示 alert]
[create chapters][admin]       [unlocked chapter]        [顯示內容]
[grant access]   [teacher]     [first chapter]           [正常顯示]
[set linear mode]
```

### 測試覆蓋流程（E2E -- 管理端設定）

```
LOGIN ADMIN ──> NAVIGATE TO COURSE EDIT ──> TOGGLE FiSwitch ──> SAVE ──> RELOAD ──> ASSERT
                                                  |                                    |
                                                  v                                    v
                                            [enable_linear_mode]                [值正確回傳]
                                            ['yes' / 'no']                      [API 確認]
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|---------|---------|------------|
| PHP Test -- `is_chapter_locked()` | 自訂表未建立 | Setup | `ensure_tables_exist()` 已在 TestCase 基類中 | 否 |
| PHP Test -- `get_flatten_post_ids()` | wp_cache 干擾 | Cache | 每個測試 `tear_down()` 清除快取 | 否 |
| E2E -- Template 阻擋 | 測試課程不存在 | Data | `beforeAll` 透過 API 建立課程 | 否 |
| E2E -- 管理端 | SPA 載入超時 | Timeout | 使用 `waitForSelector` + 合理 timeout | 否 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|--------|--------|------------|---------|
| `is_chapter_locked()` -- course_id 為 null | 章節不屬於任何課程 | 是（回傳 false） | **本計劃新增** | 否 | 不鎖定 |
| `is_chapter_locked()` -- 未登入 | current_user_id = 0 | 是（回傳 false） | **本計劃新增** | 否 | 不鎖定 |
| `is_chapter_locked()` -- 章節不在 DFS 列表 | 章節 ID 無效 | 是（回傳 false） | **本計劃新增** | 否 | 不鎖定 |
| `get_locked_chapter_ids()` -- 管理員 | 管理員查詢 | 是（回傳空陣列） | **本計劃新增** | 否 | 全部解鎖 |
| Template 層 -- 取消完成後重新鎖定 | URL 直接存取 | 是（顯示鎖定頁面） | **本計劃新增** | 是 | 引導前面章節 |

## 實作步驟

### 第一階段：PHP Integration Test -- 章節鎖定邏輯

#### 步驟 1.1：建立 `LinearModeChapterLockTest.php`
- **檔案**：`tests/Integration/Chapter/LinearModeChapterLockTest.php`
- **行動**：建立新測試類別，繼承 `Tests\Integration\TestCase`，覆蓋 `線性觀看章節鎖定.feature` 的所有場景
- **依賴**：無
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

**測試場景清單**（對應 feature file 的每個 Example）：

**Rule: 線性觀看序列依照 DFS 展開排序**

| # | 測試方法名稱 | 對應 Feature Example | 行為說明 |
|---|-------------|---------------------|---------|
| 1 | `test_第一個章節永遠可觀看` | 第一個章節（第一章）永遠可觀看 | 呼叫 `is_chapter_locked(200, alice_id)` 應回傳 false |
| 2 | `test_未完成第一個章節時其餘章節全部鎖定` | 未完成第一個章節時，其餘章節全部鎖定 | 201/202/203/204 全部 locked |
| 3 | `test_完成第一章後下一個子章解鎖` | 完成第一章後，1-1 解鎖 | 200 完成 -> 201 unlocked, 202 locked |
| 4 | `test_依序完成到子章後下一個頂層章解鎖` | 依序完成到 1-2 後，第二章解鎖 | 200+201+202 完成 -> 203 unlocked, 204 locked |

**Rule: 取消完成後重新鎖定**

| # | 測試方法名稱 | 對應 Feature Example | 行為說明 |
|---|-------------|---------------------|---------|
| 5 | `test_取消完成後後續章節重新鎖定` | 取消 1-1 完成後重新鎖定 | 刪除 201 的 finished_at -> 202/203 locked |
| 6 | `test_取消完成不清除後續章節的finished_at` | 取消完成不清除後續 finished_at | 刪除 200 的 finished_at -> 201/202 的 finished_at 仍存在 |

**Rule: 管理員/講師豁免**

| # | 測試方法名稱 | 對應 Feature Example | 行為說明 |
|---|-------------|---------------------|---------|
| 7 | `test_管理員可自由存取所有章節` | 管理員可自由存取所有章節 | admin 呼叫 is_chapter_locked 全部 false |
| 8 | `test_講師可自由存取所有章節` | 講師可自由存取所有章節 | teacher 呼叫 is_chapter_locked 全部 false |

**Rule: 功能關閉時**

| # | 測試方法名稱 | 對應 Feature Example | 行為說明 |
|---|-------------|---------------------|---------|
| 9 | `test_未開啟線性觀看時所有章節自由存取` | 未開啟線性觀看時所有章節自由存取 | enable_linear_mode='no' -> 全部 unlocked |

**額外邊界情況**

| # | 測試方法名稱 | 行為說明 |
|---|-------------|---------|
| 10 | `test_get_locked_chapter_ids_批量取得鎖定清單` | 驗證 `get_locked_chapter_ids()` 回傳正確的鎖定 ID 陣列 |
| 11 | `test_get_locked_chapter_ids_管理員回傳空陣列` | 管理員呼叫回傳 `[]` |
| 12 | `test_get_locked_chapter_ids_功能關閉回傳空陣列` | enable_linear_mode='no' 回傳 `[]` |
| 13 | `test_課程只有一個章節時永遠解鎖` | 只有 1 個章節 -> `is_chapter_locked()` 回傳 false |
| 14 | `test_get_flatten_post_ids_DFS展開順序正確` | 驗證 DFS 展開順序：第一章 -> 1-1 -> 1-2 -> 第二章 -> 2-1 |

**Background 資料建立**：

```php
// 在 set_up() 中建立 feature file 定義的完整資料結構：
// - Admin (administrator), Alice (subscriber), Teacher (subscriber)
// - Course 100 with enable_linear_mode = 'yes', teacher_ids = [Teacher]
// - 章節結構：
//   200 第一章 (parent=100, menu_order=10)
//     201 1-1 (parent=200, menu_order=10)
//     202 1-2 (parent=200, menu_order=20)
//   203 第二章 (parent=100, menu_order=20)
//     204 2-1 (parent=203, menu_order=10)
// - Alice 已加入課程 100
```

**關鍵實作細節**：

1. 建立子章節時，`post_parent` 設為父章節 ID（非課程 ID），頂層章節的 `parent_course_id` meta 設為課程 ID
2. 每個測試的 `tear_down()` 需清除 `wp_cache`（`flatten_post_ids_{course_id}` 和 `prev_next` group）
3. 使用 `$this->set_chapter_finished()` 設定完成狀態
4. 使用 `$this->get_chapter_meta()` 驗證 finished_at 是否保留

---

### 第二階段：E2E Test -- Template 層 + 教室頁面 UI

#### 步驟 2.1：建立 `015-linear-mode-access-denied.spec.ts`
- **檔案**：`tests/e2e/02-frontend/015-linear-mode-access-denied.spec.ts`
- **行動**：建立 E2E 測試，覆蓋 `存取被鎖定章節.feature` 的場景 + 教室頁面 UI 鎖頭圖示與 Toast
- **依賴**：步驟 1.1（確認核心邏輯正確後再跑 E2E）
- **風險**：中（需要完整的 WordPress 環境 + WooCommerce + 課程授權）
- **執行 Agent**：`@wp-workflows:wordpress-master`

**測試場景清單**：

| # | 測試名稱 | 對應 Feature | 行為說明 |
|---|---------|-------------|---------|
| 1 | 學員 URL 存取被鎖定章節 -- 顯示提示頁面 | Template 層 | 導航到鎖定章節 URL -> 頁面顯示 `pc-alert` warning + 文字「請先完成前面的章節」|
| 2 | 學員 URL 存取已解鎖章節 -- 正常顯示 | Template 層 | 完成第一章後 -> 第二章 URL -> 顯示教室頁面 |
| 3 | 第一個章節不受鎖定影響 | 第一章永遠可存取 | 導航到第一章 -> 正常顯示 |
| 4 | 線性觀看關閉 -- 學員可自由存取 | 功能關閉 | enable_linear_mode='no' -> 第二章 URL -> 正常顯示 |
| 5 | 教室側邊欄鎖定章節顯示鎖頭圖示 | UI 鎖頭 | 教室頁面 -> 側邊欄 -> 鎖定章節的 `li` 有 `data-locked="true"` 且包含鎖頭 SVG |
| 6 | 點擊鎖定章節顯示 Toast 警告 | UI Toast | 點擊有 `data-locked="true"` 的 `li` -> 頁面出現 `.pc-locked-toast` 元素 |

**測試資料準備**（`beforeAll`）：

```typescript
// 透過 API 建立：
// 1. 建立課程（含 3 個章節，slug 明確指定）
// 2. 更新課程 enable_linear_mode = 'yes'
// 3. 建立/取得 subscriber 帳號
// 4. 授權 subscriber 存取課程
// 所有操作使用 setupApiFromBrowser() + ApiClient
```

**Cleanup**（`afterAll`）：
- 移除課程存取權（或保留，由 global-teardown 清理）
- 重設 enable_linear_mode = 'no'

**關鍵 Selector**：
- 鎖定提示：`.pc-alert` + 文字包含「請先完成前面的章節」
- 教室正常渲染：`#pc-classroom-body` 存在
- 鎖頭圖示：`li[data-locked="true"]` 內的 SVG
- Toast：`.pc-locked-toast`

---

### 第三階段：E2E Test -- 管理端線性觀看設定

#### 步驟 3.1：建立 `linear-mode-setting.spec.ts`
- **檔案**：`tests/e2e/01-admin/linear-mode-setting.spec.ts`
- **行動**：建立 E2E 測試，覆蓋 `設定課程線性觀看模式.feature` 的場景
- **依賴**：無（獨立於步驟 1、2）
- **風險**：低
- **執行 Agent**：`@wp-workflows:react-master`

**測試場景清單**：

| # | 測試名稱 | 對應 Feature | 行為說明 |
|---|---------|-------------|---------|
| 1 | 課程預設 enable_linear_mode 為 no | 預設值 | API GET 課程 -> enable_linear_mode = 'no' |
| 2 | 開啟線性觀看模式 | 開啟 | POST 更新 enable_linear_mode='yes' -> GET 確認 = 'yes' |
| 3 | 關閉線性觀看模式 | 關閉 | POST 更新 enable_linear_mode='no' -> GET 確認 = 'no' |
| 4 | 管理端 FiSwitch 開關正確顯示 | UI | 導航到課程編輯頁 -> 「其他設定」tab -> FiSwitch 存在且可切換 |

**測試資料準備**：
- 透過 `ApiClient.createCourse()` 建立測試課程
- 使用 admin storageState 自動登入

**關鍵 Selector**：
- 課程編輯頁面：`/wp-admin/admin.php?page=power-course#/courses/edit/{id}`
- 「其他設定」Tab：Tab 標題包含「其他」
- FiSwitch：`[name="enable_linear_mode"]` 或包含文字「線性觀看模式」的 Switch 元件

---

### 第四階段：移除 Feature Files 的 @ignore Tag

#### 步驟 4.1：更新 Feature Files
- **檔案**：
  - `specs/features/chapter/線性觀看章節鎖定.feature`
  - `specs/features/chapter/存取被鎖定章節.feature`
  - `specs/features/course/設定課程線性觀看模式.feature`
- **行動**：移除每個檔案第一行的 `@ignore` tag（保留 `@query` 或 `@command` tag）
- **依賴**：步驟 1.1、2.1、3.1（測試通過後再移除 ignore）
- **風險**：低
- **執行 Agent**：`@wp-workflows:wordpress-master`

## 測試策略

### PHP Integration Test

- **測試檔案**：`tests/Integration/Chapter/LinearModeChapterLockTest.php`
- **測試群組**：`@group chapter`, `@group linear-mode`
- **測試數量**：14 個測試方法
- **覆蓋的核心方法**：
  - `ChapterUtils::is_chapter_locked(int $chapter_id, ?int $user_id): bool`
  - `ChapterUtils::get_locked_chapter_ids(int $course_id, ?int $user_id): array`
  - `ChapterUtils::get_flatten_post_ids(int $course_id): array`
- **測試執行**：`composer run test -- --filter=LinearModeChapterLockTest`

### Playwright E2E Test

- **前台測試檔案**：`tests/e2e/02-frontend/015-linear-mode-access-denied.spec.ts`
  - 測試數量：6 個測試
  - 覆蓋：Template 阻擋、教室 UI 鎖頭圖示、Toast 警告
- **管理端測試檔案**：`tests/e2e/01-admin/linear-mode-setting.spec.ts`
  - 測試數量：4 個測試
  - 覆蓋：API CRUD、FiSwitch UI
- **測試執行**：
  ```bash
  pnpm run test:e2e:frontend -- --grep "線性觀看"
  pnpm run test:e2e:admin -- --grep "linear"
  ```

### 關鍵邊界情況

| 邊界情況 | 測試類型 | 覆蓋方式 |
|---------|---------|---------|
| 課程只有 1 個章節 | PHP IT | `test_課程只有一個章節時永遠解鎖` |
| 多層巢狀章節（父 > 子） | PHP IT | Background 資料即為巢狀結構 |
| 取消完成後 finished_at 保留 | PHP IT | `test_取消完成不清除後續章節的finished_at` |
| 管理員豁免（未購買也不鎖） | PHP IT | `test_管理員可自由存取所有章節` |
| 功能關閉即時生效 | PHP IT | `test_未開啟線性觀看時所有章節自由存取` |
| 鎖定章節 URL 直接存取 | E2E | `學員 URL 存取被鎖定章節` |
| Toast 3 秒自動消失 | E2E | 點擊鎖定章節 -> `.pc-locked-toast` 出現 |

## 風險與緩解措施

- **風險**（中）：E2E 測試中 `toggle-finish-chapters` API 需要正確的 nonce 和 user context，學員帳號可能無法直接呼叫
  - 緩解措施：改用 admin API client 呼叫 `set_chapter_finished`（透過 AVLChapterMeta::update 或 admin REST API），然後以學員身份瀏覽頁面驗證 UI

- **風險**（低）：PHP Integration Test 中 `get_flatten_post_ids()` 使用 wp_cache，可能在同一測試 suite 中產生殘留快取
  - 緩解措施：在 `tear_down()` 中呼叫 `wp_cache_delete('flatten_post_ids_' . $this->course_id, 'prev_next')` 清除快取

- **風險**（低）：E2E 測試課程 slug 與現有測試資料衝突
  - 緩解措施：使用獨立的 slug（如 `e2e-linear-mode-test-course`），並在 `afterAll` 中清理

- **風險**（低）：子章節的 `post_parent` 設定在 Integration Test 中可能不被 `get_flatten_post_ids()` 正確識別
  - 緩解措施：確保頂層章節設定 `parent_course_id` meta，子章節用 `post_parent` 指向父章節，與生產環境一致

## 限制條件

- **不修改任何功能程式碼** -- 本計劃僅新增測試與移除 `@ignore` tag
- **不新增前端 unit test** -- 專案慣例為 E2E + PHP IT，無前端 unit test 基礎設施
- **不測試 Race Condition** -- 並發 toggle 的 race condition 極低機率且不影響資料完整性
- **不測試 jQuery 未載入** -- JS runtime 環境假設 jQuery 已載入（WordPress 標準行為）

## 成功標準

- [ ] `LinearModeChapterLockTest` 14 個測試全部通過（`composer run test -- --filter=LinearModeChapterLockTest`）
- [ ] `015-linear-mode-access-denied.spec.ts` 6 個 E2E 測試全部通過
- [ ] `linear-mode-setting.spec.ts` 4 個 E2E 測試全部通過
- [ ] Feature files 的 `@ignore` tag 已移除
- [ ] `pnpm run lint:php` 通過（測試檔案符合 PHPCS 規範）
- [ ] 現有測試不受影響（`composer run test` 全部通過）
