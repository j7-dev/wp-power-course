# Event Storming: Power Course

> WordPress LMS 外掛 — 整合 WooCommerce 銷售課程、管理章節、追蹤學員進度、自動化 Email 通知
>
> **版本:** 0.11.25 | **文件日期:** 2026-03-10

---

## Actors

- **管理員** [人]: WordPress 後台操作者，負責建立課程、章節、管理學員、設定外掛
- **學員** [人]: 購買或被授權課程的用戶，在前台觀看章節、標記完成進度
- **講師** [人]: 被指派到課程的教學者，具有讀取課程資料的權限
- **WooCommerce 訂單系統** [系統]: 訂單狀態變更後（預設 `completed`）自動觸發課程開通流程
- **Action Scheduler** [系統]: 執行背景任務，包含定時課程開課通知、Email 排程發送

---

## Aggregates

### Course（課程）
> WooCommerce 商品（product），附加 `_is_course = 'yes'` meta，是平台核心實體

| 屬性 | 說明 |
|------|------|
| `id` | WooCommerce 商品 ID |
| `name` | 課程名稱 |
| `status` | `publish` / `draft` |
| `limit_type` | 存取期限類型：`unlimited` / `fixed` / `assigned` / `follow_subscription` |
| `limit_value` | 期限數值（天/月/年/timestamp） |
| `limit_unit` | 期限單位：`day` / `month` / `year` / `timestamp` |
| `course_schedule` | 開課時間 timestamp（0 = 立即開課） |
| `teacher_ids` | 多筆 user meta rows 存放講師 ID |
| `feature_video` | 特色影片 `{type, id, meta}` |
| `trial_video` | 試看影片 `{type, id, meta}` |
| `is_popular` | `yes` / `no` |
| `is_featured` | `yes` / `no` |
| `is_free` | `yes` / `no` |
| `bind_courses_data` | 此課程被哪些商品授權（BindCourseData 陣列） |
| `editor` | `power-editor` / `elementor` |

### Chapter（章節）
> 自訂文章類型 `pc_chapter`，階層式結構（課程 → 頂層章節 → 子章節）

| 屬性 | 說明 |
|------|------|
| `id` | 文章 ID |
| `post_parent` | 父章節 ID 或課程商品 ID（頂層） |
| `parent_course_id` | 根課程 ID（任意巢狀深度皆可用） |
| `menu_order` | 排序順序（ASC） |
| `chapter_video` | 影片資料 `{type, id, meta}`，type：`bunny` / `youtube` / `vimeo` / `code` / `none` |
| `chapter_length` | 影片長度（秒） |
| `enable_comment` | `yes` / `no` |

### StudentEnrollment（學員選課）
> 自訂資料表 `pc_avl_coursemeta`，記錄學員對每個課程的存取權與進度

| 屬性 | 說明 |
|------|------|
| `user_id` | 學員 ID |
| `course_id` | 課程 ID（post_id） |
| `expire_date` | 到期時間 timestamp（0 = 永久）或 `subscription_{id}` |
| `course_granted_at` | 課程開通時間 `Y-m-d H:i:s` |
| `finished_at` | 課程完成時間 `Y-m-d H:i:s`（進度 100% 時設定） |
| `last_visit_info` | `{chapter_id, last_visit_at}`，最後造訪記錄 |

### ChapterProgress（章節進度）
> 自訂資料表 `pc_avl_chaptermeta`，記錄學員各章節完成狀態

| 屬性 | 說明 |
|------|------|
| `user_id` | 學員 ID |
| `chapter_id` | 章節 ID（post_id） |
| `first_visit_at` | 首次進入章節時間 `Y-m-d H:i:s` |
| `finished_at` | 章節完成時間 `Y-m-d H:i:s` |

### Settings（設定）
> WordPress option `power_course_settings`，全站外掛設定

| 屬性 | 說明 |
|------|------|
| `course_access_trigger` | 觸發課程開通的訂單狀態（預設 `completed`） |
| `course_permalink_structure` | 課程固定連結結構（預設空字串） |
| `hide_myaccount_courses` | 是否隱藏 My Account 課程頁籤（`yes` / `no`，預設 `no`） |
| `fix_video_and_tabs_mobile` | 手機端影片/Tab 固定（`yes` / `no`，預設 `no`） |
| `pc_header_offset` | 固定頂欄高度偏移（px，預設 `0`） |
| `hide_courses_in_main_query` | 隱藏課程於部落格列表（`yes` / `no`，預設 `no`） |
| `hide_courses_in_search_result` | 隱藏課程於搜尋結果（`yes` / `no`，預設 `no`） |
| `pc_watermark_qty` | 影片浮水印數量（0 = 關閉，預設 0） |
| `pc_watermark_text` | 浮水印文字模板（支援 `{display_name}`, `{post_title}`, `{ip}`, `{email}`） |
| `pc_pdf_watermark_qty` | PDF 浮水印數量（0 = 關閉，預設 0） |
| `pc_pdf_watermark_text` | PDF 浮水印文字模板 |

### BundleProduct（銷售方案）
> WooCommerce 商品（product），附加 `bundle_type` meta，連結到單一課程，提供多元定價方案

| 屬性 | 說明 |
|------|------|
| `id` | 商品 ID |
| `bundle_type` | 非空字串代表此商品為銷售方案 |
| `link_course_ids` | 連結的課程 ID |
| `pbp_product_ids` | 包含的商品 IDs（多筆 meta rows） |

---

## Commands

### CreateCourse
- **Actor**: 管理員
- **Aggregate**: Course
- **Predecessors**: 無
- **參數**: `name`（課程名稱）, `status`（`publish`/`draft`）, `description`, `short_description`, `price`（售價）, `regular_price`（原價）, `limit_type`（`unlimited`/`fixed`/`assigned`/`follow_subscription`）, `limit_value`（期限數值）, `limit_unit`（`day`/`month`/`year`/`timestamp`）, `course_schedule`（開課時間 timestamp，0=立即）, `teacher_ids`（講師 ID 陣列）, `is_popular`（`yes`/`no`）, `is_featured`（`yes`/`no`）, `is_free`（`yes`/`no`）, `feature_video`（特色影片）, `trial_video`（試看影片）
- **Description**:
  - What: 建立一個新的 WooCommerce 課程商品，設定名稱、售價、存取期限、開課時間、講師等基本資訊
  - Why: 管理員需要在平台上架新課程，供學員購買或被授權
  - When: 管理員在後台課程管理頁面點擊「建立課程」按鈕

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `name` 不可為空
  - `limit_type` 為 `fixed` 時，`limit_value` 與 `limit_unit` 不可為空
  - `limit_type` 為 `assigned` 時，`limit_value` 必須是有效的 10 位 unix timestamp
  - `price` 若有設定須為非負數
- 後置（狀態）:
  - 建立一個 WooCommerce product，`_is_course` meta 設為 `'yes'`
  - 若 `teacher_ids` 有值，以多筆 meta rows 分別儲存（不使用 update_post_meta 傳入陣列）
  - 回傳新建課程的完整資料（含 ID）

---

### UpdateCourse
- **Actor**: 管理員
- **Aggregate**: Course
- **Predecessors**: CreateCourse
- **參數**: `id`（課程 ID）, 其餘同 CreateCourse 參數（皆為可選，僅更新有傳入的欄位）
- **Description**:
  - What: 更新指定課程的基本資訊、定價、存取設定、媒體等
  - Why: 課程內容或定價需要異動時，管理員可隨時修改
  - When: 管理員在課程編輯頁面修改欄位並儲存

#### Rules
- 前置（狀態）:
  - 指定 `id` 對應的課程必須存在且 `_is_course = 'yes'`
- 前置（參數）:
  - `id` 不可為空且必須為正整數
  - 同 CreateCourse 的參數驗證規則
- 後置（狀態）:
  - 觸發 `power_course_before_update_product_meta` action（`$product`, `$meta_data`）
  - 更新 WC_Product 後持久化，`teacher_ids` 先 `delete_meta_data` 再 `add_meta_data` loop

---

### DeleteCourse
- **Actor**: 管理員
- **Aggregate**: Course
- **Predecessors**: CreateCourse
- **參數**: `ids`（課程 ID 陣列）
- **Description**:
  - What: 刪除一或多個課程商品
  - Why: 課程下架或不再販售時，管理員需要清除資料
  - When: 管理員在課程列表勾選後點擊刪除

#### Rules
- 前置（狀態）:
  - 所有 `ids` 對應的課程必須存在
- 前置（參數）:
  - `ids` 不可為空陣列
- 後置（狀態）:
  - 課程商品被刪除（wp_delete_post）
  - 關聯的章節、學員紀錄不自動刪除（需手動清理）

---

### CreateChapter
- **Actor**: 管理員
- **Aggregate**: Chapter
- **Predecessors**: CreateCourse
- **參數**: `post_title`（章節名稱）, `post_parent`（父節點 ID，為課程 ID 或父章節 ID）, `parent_course_id`（根課程 ID）, `chapter_video`（影片資料 `{type, id, meta}`）, `chapter_length`（秒數）, `enable_comment`（`yes`/`no`）, `menu_order`（排序）
- **Description**:
  - What: 在指定課程下建立新章節（可巢狀）
  - Why: 課程需要章節結構來組織學習內容
  - When: 管理員在課程編輯頁的「章節」分頁新增章節

#### Rules
- 前置（狀態）:
  - `parent_course_id` 對應的課程必須存在且 `_is_course = 'yes'`
  - 若 `post_parent` 為章節 ID，該章節必須存在且 `post_type = 'pc_chapter'`
- 前置（參數）:
  - `post_title` 不可為空
  - `parent_course_id` 不可為空
  - `chapter_video.type` 必須為 `bunny` / `youtube` / `vimeo` / `code` / `none` 之一
- 後置（狀態）:
  - 建立 `post_type = 'pc_chapter'` 的文章
  - `parent_course_id` 儲存至 post_meta

---

### UpdateChapter
- **Actor**: 管理員
- **Aggregate**: Chapter
- **Predecessors**: CreateChapter
- **參數**: `id`（章節 ID）, `post_title`, `chapter_video`, `chapter_length`, `enable_comment`（皆可選）
- **Description**:
  - What: 更新章節的標題、影片來源、時長等資訊
  - Why: 課程內容需要修改時，管理員可更新章節資料
  - When: 管理員在章節列表點擊編輯

#### Rules
- 前置（狀態）:
  - `id` 對應的章節必須存在且 `post_type = 'pc_chapter'`
- 前置（參數）:
  - `id` 不可為空
- 後置（狀態）:
  - 更新對應章節的 post 及 meta 資料

---

### SortChapters
- **Actor**: 管理員
- **Aggregate**: Chapter
- **Predecessors**: CreateChapter
- **參數**: `chapters`（章節排序陣列，每項含 `id` 與 `menu_order`）
- **Description**:
  - What: 批量更新章節的排序 `menu_order`
  - Why: 管理員需要拖拽調整章節顯示順序
  - When: 管理員在章節列表拖拽後放開

#### Rules
- 前置（狀態）:
  - `chapters` 中每個 `id` 對應的章節都必須存在
- 前置（參數）:
  - `chapters` 不可為空陣列
  - 每項必須包含有效的 `id` 與 `menu_order`
- 後置（狀態）:
  - 批量更新所有章節的 `menu_order`（`wp_update_post`）

---

### AddStudentToCourse
- **Actor**: 管理員 / WooCommerce 訂單系統
- **Aggregate**: StudentEnrollment
- **Predecessors**: CreateCourse
- **參數**: `user_ids`（學員 ID 陣列）, `course_ids`（課程 ID 陣列）, `expire_date`（到期時間，0=永久 / 10位 timestamp / `subscription_{id}`，預設 0）
- **Description**:
  - What: 將學員加入課程，建立存取權（`avl_course_ids` user meta + `pc_avl_coursemeta` table）
  - Why: 學員購課完成後系統自動開通，或管理員手動授權課程存取
  - When: (1) 訂單狀態變更為設定的觸發狀態（預設 `completed`）時，WooCommerce 系統自動呼叫；(2) 管理員在後台學員管理頁手動新增

#### Rules
- 前置（狀態）:
  - `user_ids` 中每個 ID 對應的 WordPress 用戶必須存在
  - `course_ids` 中每個 ID 對應的課程必須存在且 `_is_course = 'yes'`
- 前置（參數）:
  - `user_ids` 不可為空陣列
  - `course_ids` 不可為空陣列
  - `expire_date` 若為 timestamp 必須是 10 位正整數；若為 subscription 格式需符合 `subscription_{id}`
- 後置（狀態）:
  - 對每對 (user_id, course_id) 執行 DB transaction：
    1. `add_user_meta($user_id, 'avl_course_ids', $course_id)` — 若尚未存在
    2. `AVLCourseMeta::update($course_id, $user_id, 'expire_date', $expire_date)`
    3. `AVLCourseMeta::update($course_id, $user_id, 'course_granted_at', now())`
  - 觸發 action `power_course_add_student_to_course`（priority 20）
  - 觸發 action `power_course_after_add_student_to_course`（priority 10，fires after）
  - 觸發 PowerEmail 的 `course_granted` Email 排程

---

### RemoveStudentFromCourse
- **Actor**: 管理員
- **Aggregate**: StudentEnrollment
- **Predecessors**: AddStudentToCourse
- **參數**: `user_ids`（學員 ID 陣列）, `course_ids`（課程 ID 陣列）
- **Description**:
  - What: 撤銷學員的課程存取權
  - Why: 退款、帳號封停或管理員手動移除時需要撤銷存取
  - When: 管理員在課程學員管理頁點擊「移除」

#### Rules
- 前置（狀態）:
  - 學員必須已有該課程的存取權
- 前置（參數）:
  - `user_ids` 不可為空陣列
  - `course_ids` 不可為空陣列
- 後置（狀態）:
  - 刪除 `avl_course_ids` user meta 中對應的 course_id
  - 觸發 action `power_course_after_remove_student_from_course`（`$user_id`, `$course_id`）
  - 學員的章節進度記錄不自動清除

---

### UpdateStudentExpireDate
- **Actor**: 管理員
- **Aggregate**: StudentEnrollment
- **Predecessors**: AddStudentToCourse
- **參數**: `user_ids`（學員 ID 陣列）, `course_ids`（課程 ID 陣列）, `timestamp`（新到期時間，0=永久）
- **Description**:
  - What: 更新學員的課程存取到期時間
  - Why: 管理員需要延長、縮短或改為永久存取
  - When: 管理員在學員管理頁修改到期日並儲存

#### Rules
- 前置（狀態）:
  - 學員必須已有該課程的存取權
- 前置（參數）:
  - `user_ids`、`course_ids` 不可為空
  - `timestamp` 必須為整數（0 或 10位 unix timestamp）
- 後置（狀態）:
  - `AVLCourseMeta::update($course_id, $user_id, 'expire_date', $timestamp)`
  - 觸發 action `power_course_after_update_student_from_course`（`$user_id`, `$course_id`, `$timestamp`）

---

### ToggleFinishChapter
- **Actor**: 學員
- **Aggregate**: ChapterProgress, StudentEnrollment
- **Predecessors**: AddStudentToCourse
- **參數**: `chapter_id`（章節 ID）, 隱含 `user_id`（當前登入學員）
- **Description**:
  - What: 切換章節的完成狀態（完成 ↔ 未完成），並重新計算課程整體進度
  - Why: 學員需要標記已讀章節，系統追蹤學習進度
  - When: 學員在教室頁面點擊「完成章節」或「取消完成」按鈕

#### Rules
- 前置（狀態）:
  - 學員必須已有該章節所屬課程的存取權（`avl_course_ids` 包含 `parent_course_id`）
  - 課程存取未到期（`expire_date = 0` 或 `expire_date > now()`）
- 前置（參數）:
  - `chapter_id` 必須存在且 `post_type = 'pc_chapter'`
  - `user_id` 必須已登入（非訪客）
- 後置（狀態）:
  - **標記完成**: `AVLChapterMeta::add($chapter_id, $user_id, 'finished_at', now())`；觸發 `power_course_chapter_finished`
  - **取消完成**: `AVLChapterMeta::delete($chapter_id, $user_id, 'finished_at')`；觸發 `power_course_chapter_unfinished`
  - 重新計算課程進度（`CourseUtils::get_course_progress`）
  - 若進度達 100%：觸發 `power_course_course_finished`（`$course_id`, `$user_id`）；設定 `AVLCourseMeta::finished_at`

---

### UpdateSettings
- **Actor**: 管理員
- **Aggregate**: Settings
- **Predecessors**: 無
- **參數**: `course_access_trigger`（`completed`/`processing`等WC訂單狀態）, `hide_myaccount_courses`（`yes`/`no`）, `fix_video_and_tabs_mobile`（`yes`/`no`）, `pc_header_offset`（字串，px 數值）, `hide_courses_in_main_query`（`yes`/`no`）, `hide_courses_in_search_result`（`yes`/`no`）, `pc_watermark_qty`（整數）, `pc_watermark_text`（字串）, `pc_pdf_watermark_qty`（整數）, `pc_pdf_watermark_text`（字串）（皆可選）
- **Description**:
  - What: 更新 Power Course 外掛的全局設定
  - Why: 管理員需要調整課程行為、SEO、浮水印等設定
  - When: 管理員在後台「設定」頁面修改後儲存

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `course_access_trigger` 必須是有效的 WooCommerce 訂單狀態（`completed`, `processing` 等）
  - `pc_watermark_qty` / `pc_pdf_watermark_qty` 必須為非負整數
  - 未知屬性會被忽略（記錄 dto_error）
- 後置（狀態）:
  - `Settings::set_properties($params)->save()` → `update_option('power_course_settings', ...)`

---

### BindCoursesToProduct
- **Actor**: 管理員
- **Aggregate**: Course, BundleProduct
- **Predecessors**: CreateCourse
- **參數**: `product_id`（WC 商品 ID）, `bind_courses_data`（陣列，每項含 `course_id`, `limit_type`, `limit_value`, `limit_unit`）
- **Description**:
  - What: 將一或多個課程綁定到指定的 WooCommerce 商品，設定各課程的存取期限規則
  - Why: 一個商品購買後可授權多個課程，且各課程期限可不同
  - When: 管理員在商品管理頁的課程綁定區塊設定後儲存

#### Rules
- 前置（狀態）:
  - `product_id` 對應的商品必須存在
  - `bind_courses_data` 中每個 `course_id` 對應的課程必須存在且 `_is_course = 'yes'`
- 前置（參數）:
  - `product_id` 不可為空
  - `bind_courses_data` 不可為空陣列
  - 每項的 `limit_type` 為 `fixed` 時，`limit_value` 與 `limit_unit` 不可為空
- 後置（狀態）:
  - `bind_courses_data` meta 儲存到商品（`update_post_meta`）
  - 每個 `BindCourseData` 實例化時驗證 `course_id` 有效性

---

### UploadStudentsCSV
- **Actor**: 管理員
- **Aggregate**: StudentEnrollment
- **Predecessors**: CreateCourse
- **參數**: `file`（CSV 檔案），`course_id`（目標課程 ID）
- **Description**:
  - What: 批量上傳 CSV 名單，將學員加入指定課程
  - Why: 大量學員需要快速匯入時，手動逐一新增效率太低
  - When: 管理員在「學員管理」頁面點擊「匯入 CSV」並上傳檔案

#### Rules
- 前置（狀態）:
  - `course_id` 對應的課程必須存在
- 前置（參數）:
  - CSV 格式需符合範例（`email` 欄位必填，`expire_date` 可選）
  - 檔案大小不可超過系統限制
- 後置（狀態）:
  - 對 CSV 中每一列：若 email 不存在則建立新 WP 用戶；呼叫 `AddStudentToCourse` 邏輯
  - 以 Action Scheduler (`pc_batch_add_students_task`) 背景執行，避免逾時
  - 回傳批次任務 ID 或成功/失敗筆數統計

---

## Read Models

### ListCourses
- **Actor**: 管理員
- **Aggregates**: Course
- **回傳欄位**: `id`, `name`, `status`, `price`, `regular_price`, `limit_type`, `limit_value`, `limit_unit`, `course_schedule`, `teacher_ids`, `is_popular`, `is_featured`, `is_free`, `total_students`（選課人數）, `created_at`, `updated_at`
- **Description**:
  - What: 分頁查詢課程列表，支援多種篩選條件
  - Why: 管理員需要瀏覽及管理所有課程
  - When: 管理員進入後台課程管理頁面

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `posts_per_page`（預設 10）, `paged`（預設 1）
  - `orderby`（預設 `date`）, `order`（`ASC`/`DESC`，預設 `DESC`）
  - `status`（預設 `['publish', 'draft']`）
  - `search`（可選，搜尋課程名稱）
- 後置（回應）:
  - 回傳陣列，HTTP Header 包含 `X-WP-Total`（總筆數）及 `X-WP-TotalPages`（總頁數）

---

### GetCourse
- **Actor**: 管理員, 學員
- **Aggregates**: Course, Chapter, BundleProduct
- **回傳欄位**: 完整課程資料 + `chapters`（巢狀章節樹）+ `bundle_products`（銷售方案列表）
- **Description**:
  - What: 取得單一課程的完整詳情，包含章節結構與銷售方案
  - Why: 課程編輯頁需要預填所有欄位；學員前台需要顯示課程內容
  - When: 管理員開啟課程編輯頁；學員進入課程銷售頁

#### Rules
- 前置（狀態）:
  - 指定 `id` 對應的課程必須存在
- 前置（參數）:
  - `id` 必須為正整數且對應 `_is_course = 'yes'` 的商品
- 後置（回應）:
  - 包含完整課程 meta + 章節列表（`post_type = 'pc_chapter'`，按 `menu_order ASC`）+ 銷售方案列表

---

### ListStudents
- **Actor**: 管理員
- **Aggregates**: StudentEnrollment
- **回傳欄位**: `user_id`, `display_name`, `email`, `avl_course_ids`（已選課程列表）, `expire_date`（指定課程到期日）, `course_granted_at`（開通時間）, `finished_at`（完成時間）, `course_progress`（進度 %）
- **Description**:
  - What: 查詢已加入特定課程的學員列表（或全站學員）
  - Why: 管理員需要查看學員名單、進度、到期狀態
  - When: 管理員在課程「學員」分頁，或學員管理全域頁面

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `meta_value`（課程 ID，查詢特定課程的學員；前綴 `!` 代表反向查詢）
  - `search`（可選），`search_field`（`email`/`name`/`id`/`default`）
  - `posts_per_page`（預設 20）, `paged`（預設 1）, `order`（`DESC`）
- 後置（回應）:
  - 學員陣列 + `pagination`（`total`, `total_pages`）

---

### GetRevenue
- **Actor**: 管理員
- **Aggregates**: Course, StudentEnrollment
- **回傳欄位**: `total_revenue`（總營收）, `total_orders`（訂單數）, `revenue_by_course`（各課程營收）, `revenue_by_period`（時間軸資料）
- **Description**:
  - What: 查詢平台的營收統計報表，支援時間範圍與課程篩選
  - Why: 管理員需要追蹤銷售表現與課程收益
  - When: 管理員進入後台「分析」頁面

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `start_date`（ISO 8601），`end_date`（ISO 8601）（皆可選）
  - `course_id`（可選，篩選特定課程）
  - `view_type`（`day`/`week`/`month`，時間粒度）
- 後置（回應）:
  - 依篩選條件聚合的營收資料

---

### ListStudentLogs
- **Actor**: 管理員
- **Aggregates**: StudentEnrollment, ChapterProgress
- **回傳欄位**: `id`, `user_id`, `course_id`, `chapter_id`, `log_type`（`course_granted`/`chapter_finish`等）, `title`, `content`, `user_ip`, `created_at`
- **Description**:
  - What: 查詢學員活動稽核紀錄（`pc_student_logs` 表）
  - Why: 管理員需要追蹤學員行為，包含選課、完課、章節完成等歷史
  - When: 管理員在課程學員管理頁的「活動紀錄」區塊

#### Rules
- 前置（狀態）:
  - 無
- 前置（參數）:
  - `course_id`（可選）, `user_id`（可選）
  - `log_type`（可選，篩選特定類型紀錄）
  - `posts_per_page`（預設 20）, `paged`（預設 1）
- 後置（回應）:
  - 紀錄陣列 + 分頁資訊

---

### ExportStudentsCSV
- **Actor**: 管理員
- **Aggregates**: StudentEnrollment
- **回傳欄位**: `email`, `display_name`, `expire_date`（格式化日期）, `course_granted_at`, `finished_at`, `course_progress`
- **Description**:
  - What: 匯出特定課程所有學員名單為 CSV 檔案
  - Why: 管理員需要將學員資料匯出做後續分析或備份
  - When: 管理員在課程學員管理頁點擊「匯出 CSV」

#### Rules
- 前置（狀態）:
  - `course_id` 對應的課程必須存在
- 前置（參數）:
  - `course_id` 不可為空
- 後置（回應）:
  - 回傳 CSV 格式的 HTTP Response（`Content-Type: text/csv`）
  - 第一列為欄位標頭，後續為學員資料

---

### GetCourseProgress
- **Actor**: 學員
- **Aggregates**: StudentEnrollment, ChapterProgress
- **回傳欄位**: `progress`（float 0-100）, `finished_chapters`（已完成章節 IDs）, `total_chapters`（章節總數）, `last_visit_info`（`{chapter_id, last_visit_at}`）, `expire_date`（到期時間）
- **Description**:
  - What: 查詢當前登入學員在特定課程的學習進度資料
  - Why: 教室頁面需要顯示進度條、已完成章節標記，並判斷課程是否完成
  - When: 學員進入課程教室頁面時載入

#### Rules
- 前置（狀態）:
  - 學員必須已有該課程的存取權
  - 課程存取未到期
- 前置（參數）:
  - `course_id` 不可為空且學員有存取該課程
- 後置（回應）:
  - 進度資料；若課程已到期，`expire_date` 小於當前時間，回傳 `expired: true`
