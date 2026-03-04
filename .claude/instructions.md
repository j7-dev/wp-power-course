# Power Course — 開發指引 (Claude Code Instructions)

> **Last Updated:** 2025-01-31 | **Version:** 0.11.23

---

## 專案概覽

**Power Course** 是一個 WordPress LMS 課程外掛，整合 WooCommerce 實現課程販售。架構：
- **後端**：PHP 8.0+，WordPress + WooCommerce + Powerhouse 外掛，PSR-4 自動載入
- **前端**：React 18 + TypeScript，refine.dev CRUD 框架，Ant Design，Vite (vite-for-wp)
- **資料庫**：4 張自訂 MySQL 表格 + 標準 WP post/user meta

此專案是 **monorepo (powerrepo)** 的 submodule，相關路徑：
- Powerhouse PHP 類別：`../powerhouse/inc/classes/Domains/`
- J7\WpUtils PHP 函式庫：`../powerhouse/vendor/j7-dev/wp-utils/src/`
- antd-toolkit 前端元件：`../../packages/antd-toolkit/lib/`

---

## 核心架構

### PHP 命名空間與目錄對應
```
J7\PowerCourse\ → inc/classes/  AND  inc/src/
```

### 自訂資料庫表格
| 常數 | 表格名稱 | 用途 |
|------|---------|------|
| `Plugin::COURSE_TABLE_NAME` | `{prefix}_pc_avl_coursemeta` | 用戶↔課程的 metadata (到期日、進度等) |
| `Plugin::CHAPTER_TABLE_NAME` | `{prefix}_pc_avl_chaptermeta` | 用戶↔章節的進度 metadata |
| `Plugin::EMAIL_RECORDS_TABLE_NAME` | `{prefix}_pc_email_records` | 自動發信紀錄 |
| `Plugin::STUDENT_LOGS_TABLE_NAME` | `{prefix}_pc_student_logs` | 學員活動日誌 |

### 關鍵設計模式

#### Singleton 模式
```php
// 所有 service class 使用 SingletonTrait，不直接 new
final class MyService {
    use \J7\WpUtils\Traits\SingletonTrait;
    public function __construct() { /* 在這裡 add_action / add_filter */ }
}
MyService::instance(); // 正確的初始化方式
```

#### REST API 類別
繼承 `J7\WpUtils\Classes\ApiBase`，callback 命名規則：`{method}_{endpoint_snake}_callback()`：
```php
final class Course extends ApiBase {
    protected $namespace = 'power-course';
    protected $apis = [
        ['endpoint' => 'courses',             'method' => 'get'],
        ['endpoint' => 'courses/(?P<id>\d+)', 'method' => 'post'],
    ];
    public function get_courses_callback($request) { ... }
    public function post_courses_with_id_callback($request) { ... }
}
```

#### MetaCRUD 模式 (自訂表格 CRUD)
```php
// 課程 meta (pc_avl_coursemeta)
AVLCourseMeta::update($course_id, $user_id, 'expire_date', $timestamp);
AVLCourseMeta::get($course_id, $user_id, 'expire_date', true); // single=true

// 章節 meta (pc_avl_chaptermeta)
AVLChapterMeta::add($chapter_id, $user_id, 'finished_at', wp_date('Y-m-d H:i:s'));
AVLChapterMeta::delete($chapter_id, $user_id, 'finished_at');
```

#### WP::separator() 資料分離
```php
// 將請求 body params 分離成 WP_Post 欄位 和 post meta
[
    'data'      => $data,      // WP_Post 欄位 (post_title, post_status, etc.)
    'meta_data' => $meta_data, // post meta
] = WP::separator($body_params, 'product', $file_params['files'] ?? []);
```

---

## 核心資源 (Resources)

### Course (課程)
- 課程是 `_is_course = 'yes'` 的 WooCommerce 商品 (Simple 或 Subscription)
- 查詢工具：`J7\PowerCourse\Utils\Course` (abstract class，全靜態方法)
- 生命週期：`J7\PowerCourse\Resources\Course\LifeCycle`
- 關鍵方法：
  ```php
  CourseUtils::is_course_product($product);       // 是否為課程商品
  CourseUtils::is_avl($course_id, $user_id);      // 用戶是否有上課權限
  CourseUtils::is_course_ready($product);          // 課程是否已開課
  CourseUtils::is_expired($product, $user_id);    // 課程是否已到期
  CourseUtils::get_course_progress($product, $user_id); // float 0-100
  CourseUtils::get_all_chapters($product, $return_ids); // 取得所有章節
  ```

### Chapter (章節)
- CPT：`pc_chapter`（slug: `classroom`，支援階層結構）
- 頂層 chapter 的 `post_parent` = 課程商品 ID
- 子章節的 `post_parent` = 父章節 ID
- `parent_course_id` meta = 根課程 ID
- 排序：`menu_order`（ASC）

### Limit (課程觀看限制)
```php
$limit = Limit::instance($product);
// limit_type: 'unlimited' | 'fixed' | 'assigned' | 'follow_subscription'
// limit_value: int|null
// limit_unit: 'timestamp' | 'day' | 'month' | 'year' | null
$expire_date = $limit->calc_expire_date($order); // int timestamp | 'subscription_{id}'
```

### BundleProduct (銷售方案)
- 帶有 `bundle_type` meta 的商品
- 通過 `link_course_ids` 連結課程
- 包含商品 IDs 存在 `pbp_product_ids` meta（多筆 rows，非序列化）
- **限制：銷售方案不能包含其他銷售方案**
```php
$helper = Helper::instance($product_id);
$helper->is_bundle_product;     // bool
$helper->get_product_ids();     // 包含的商品 IDs
Helper::get_bundle_products($course_id, true); // 取得課程的銷售方案 IDs
```

---

## WordPress Hooks (自訂動作)

### 課程生命週期
```php
// ⭐ 開通課程權限（ALWAYS use this action，不要直接呼叫函數）
do_action('power_course_add_student_to_course', $user_id, $course_id, $expire_date, $order);

// 開通後（記錄 log、觸發 email）
do_action('power_course_after_add_student_to_course', $user_id, $course_id, $expire_date, $order);

// 移除學員後
do_action('power_course_after_remove_student_from_course', $user_id, $course_id);

// 更新學員到期日後
do_action('power_course_after_update_student_from_course', $user_id, $course_id, $timestamp);

// 課程開課（排程觸發）
do_action('power_course_course_launch', $user_id, $course_id);

// 課程完成（進度 100%）
do_action('power_course_course_finished', $course_id, $user_id);

// 儲存課程 meta 前（可用於攔截修改）
do_action('power_course_before_update_product_meta', $product, $meta_data);
```

### 章節生命週期
```php
// 首次進入章節
do_action('power_course_visit_chapter', $chapter_post, $course_product);

// 標示章節為完成
do_action('power_course_chapter_finished', $chapter_id, $course_id, $user_id);

// 標示章節為未完成
do_action('power_course_chapter_unfinished', $chapter_id, $course_id, $user_id);

// 教室頁面渲染前（用於 visit 追蹤）
do_action('power_course_before_classroom_render');
```

### 排程
```php
// Action Scheduler 每 10 分鐘執行（處理課程開課檢測）
do_action('power_course_schedule_action');
```

---

## REST API 端點

Namespace: `power-course`，base: `{site_url}/wp-json/power-course/`

| Method | Endpoint | 說明 |
|--------|----------|------|
| GET | `courses` | 列出課程（分頁、篩選） |
| GET | `courses/{id}` | 取得單一課程（含章節） |
| POST | `courses` | 建立課程 |
| POST | `courses/{id}` | 更新課程 |
| DELETE | `courses` | 批量刪除 |
| DELETE | `courses/{id}` | 刪除單一課程 |
| GET | `courses/terms` | 取得分類/標籤 |
| GET | `courses/options` | 篩選選項（分類、標籤、價格範圍） |
| GET | `courses/student-logs` | 學員活動日誌 |
| POST | `courses/add-students` | 開通課程權限 |
| POST | `courses/remove-students` | 移除課程權限 |
| POST | `courses/update-students` | 更新學員到期日 |
| GET/POST/DELETE | `chapters` | 章節 CRUD |
| POST | `chapters/sort` | 章節排序 |
| POST | `chapters/{id}` | 更新章節 |
| POST | `toggle-finish-chapters/{id}` | 切換章節完成狀態 |
| GET/POST | `users` | 用戶管理 |
| POST | `upload` | 檔案上傳 |
| GET/POST | `options` | 外掛選項 |
| GET | `reports/revenue` | 營收報表 |

---

## 前端架構

### 進入點 (`js/src/main.tsx`)

| App | 掛載選擇器 | 說明 |
|-----|-----------|------|
| `App1` | `#power_course` (`Base::APP1_SELECTOR`) | 管理後台 SPA (refine.dev) |
| `App2` | `.pc-vidstack` (`Base::APP2_SELECTOR`) | VidStack 影片播放器 |

### useEnv() Hook — 環境變數
PHP 透過 `wp_localize_script` 加密傳遞環境變數，前端用 `useEnv()` 解密：
```typescript
const {
  API_URL, SITE_URL, NONCE, KEBAB, SNAKE,
  CURRENT_USER_ID, CURRENT_POST_ID, PERMALINK,
  BUNNY_LIBRARY_ID, BUNNY_CDN_HOSTNAME, BUNNY_STREAM_API_KEY,
  APP1_SELECTOR, APP2_SELECTOR, ELEMENTOR_ENABLED,
  COURSE_PERMALINK_STRUCTURE, AXIOS_INSTANCE
} = useEnv()
```

### Data Providers (refine.dev)
```typescript
{
  'default':      '/wp-json/v2/powerhouse',
  'power-email':  '/wp-json/power-email',
  'power-course': '/wp-json/power-course',   // ← 主要用這個
  'wc-analytics': '/wp-json/wc-analytics',
  'wp-rest':      '/wp-json/wp/v2',
  'wc-rest':      '/wp-json/wc/v3',
  'wc-store':     '/wp-json/wc/store/v1',
  'bunny-stream': BunnyProvider,
}
```

### 管理頁面路由

| 路由 | 說明 |
|------|------|
| `/courses` | 課程列表 |
| `/courses/edit/:id` | 課程編輯（分頁：價格/章節/學員/銷售方案/描述/Q&A/分析/其他） |
| `/teachers` | 講師管理 |
| `/students` | 學員管理 + CSV 匯出 |
| `/products` | 課程權限綁定（商品管理） |
| `/emails` | Email 模板列表 |
| `/emails/edit/:id` | 拖拉式 Email 編輯器 |
| `/settings` | 外掛設定 |
| `/analytics` | 營收分析 |
| `/media-library` | WordPress 媒體庫 |
| `/bunny-media-library` | Bunny CDN 媒體庫 |

---

## 設定參考 (`power_course_settings`)

| 屬性 | 預設值 | 說明 |
|------|--------|------|
| `course_access_trigger` | `'completed'` | 觸發開課的訂單狀態 |
| `hide_myaccount_courses` | `'no'` | 隱藏 My Account 的課程標籤 |
| `fix_video_and_tabs_mobile` | `'no'` | 手機版影片/tabs 置頂 |
| `pc_header_offset` | `'0'` | 置頂偏移距離 (px) |
| `hide_courses_in_main_query` | `'no'` | 在主查詢中隱藏課程 |
| `hide_courses_in_search_result` | `'no'` | 在搜尋結果中隱藏課程 |
| `pc_watermark_qty` | `0` | 影片浮水印數量（0=停用） |
| `pc_watermark_text` | `'用戶 {display_name}...'` | 浮水印模板文字 |
| `pc_pdf_watermark_qty` | `0` | PDF 浮水印數量 |

> **注意**：Bunny 設定（library_id, cdn_hostname, stream_api_key）儲存在 **Powerhouse** 的 `powerhouse_settings` option，不在 Power Course settings。

---

## 影片播放器

| chapter_video.type | 播放器 | 說明 |
|-------------------|--------|------|
| `bunny` | VidStack + HLS.js | Bunny Stream CDN (HLS 串流) |
| `youtube` | iframe | YouTube 嵌入 |
| `vimeo` | iframe | Vimeo 嵌入 |
| `code` | raw HTML | 自訂嵌入代碼 |
| `none` | — | 無影片 |

---

## Email 自動化 (PowerEmail)

Email 觸發時機點 (`AtHelper` 常數)：
| 常數 | Slug | 觸發時機 |
|------|------|---------|
| `COURSE_GRANTED` | `course_granted` | 學員獲得課程權限後 |
| `COURSE_FINISHED` | `course_finish` | 課程完成（100%）時 |
| `COURSE_LAUNCHED` | `course_launch` | 課程開課時 |
| `CHAPTER_ENTERED` | `chapter_enter` | 首次進入章節時 |
| `CHAPTER_FINISHED` | `chapter_finish` | 完成章節時 |

---

## PHP 程式碼品質

- 優先參考專案內其他寫過的程式碼風格以及使用的類及函數，**避免重複造輪子**
- 再來考慮使用 wordpress, woocommerce 的特性以及函數
- 再來考慮使用 Powerhouse 外掛, J7\WpUtils 的特性以及函數
- 需要符合 phpcs 以及 phpstan 的規則，**盡可能不要 ignore 規則**
- 所有 php 檔案都必須宣告 `declare(strict_types=1);`
- 函數都必須要有**繁體中文**註解，一句話講清楚用途，並且函數的輸入、輸出變數要都有 phpstan 的型別註釋以及強型別定義
- 如果有定時任務優先使用 **ActionScheduler** 來實作，而非 wp-cron
- 資料庫多步驟寫入使用 transaction (`START TRANSACTION` / `COMMIT` / `ROLLBACK`)
- 提交前必須通過 `pnpm run lint:php` 檢查，有錯誤請自動修正
- PHP 8.0+ 語法，使用 PSR-4 autoloading
- 所有 API 端點需繼承 `ApiBase`
- 遵循 WordPress Coding Standards
- class 類必須要有繁體中文註解，一句話講清楚用途
- class 類的屬性定義，盡可能使用單行中文註解，如下：
```php
/** @var string 文章名稱 */
public string $name;
```

### 特殊注意事項（PHP）
- `teacher_ids` 等陣列 meta 使用**多筆 meta rows**（非序列化）：先 `delete_meta_data()` 再用 `add_meta_data()` 迴圈新增
- 課程 CPT (`pc_chapter`) 的 `show_ui` / `show_in_menu` 僅在 `Plugin::$is_local === true` 時顯示
- 新增學員**永遠**使用 action hook，不直接呼叫函數：
  ```php
  do_action(LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, $user_id, $course_id, $expire_date, $order);
  ```
- 當購物車含課程商品時，訪客結帳會被動態停用（Bootstrap::prevent_guest_checkout）
- 版本升級的相容性遷移寫在 `Compatibility\Compatibility::compatibility()` 並比對版本號

---

## 前端 程式碼品質

- 優先參考其他寫過的程式碼風格以及使用的類及函數，避免重複造輪子
- 再來考慮使用 antd, @ant-design/pro-components, antd-toolkit 這個 library 的組件還有 custom hooks 來實作功能
- TypeScript 盡量避免使用 `any`；如可能，使用 `zod/v4` 做 runtime 型別驗證
- 前端：ESLint + Prettier，使用 **tabs、單引號、無分號**
- 路徑別名：`@/` 映射到 `js/src/`
- 使用 Ant Design 組件庫，遵循既有設計規範
- API 呼叫統一使用 custom hooks，位於 `hooks/` 目錄
- TypeScript 嚴格模式，所有 API 回應需定義類型
- **Power Course 沒有自己的 CSS**，所有 CSS（scss & tailwind）都寫在 Powerhouse 內
- 提交前必須通過 `pnpm run lint:ts` 檢查，有錯誤請自動修正
- 前端 env 透過 `useEnv()` hook 取得，**不要**直接存取 `power_course_data`

---

## 錯誤處理與測試標準

- 目前專案沒有自動化測試，都是手動測試，暫時不要求測試，等到有自動化測試再補充
- 使用 `Plugin::logger($message, $level, $context)` 記錄日誌（WooCommerce Logger）
- REST API 驗證錯誤回傳 `\WP_Error`，domain 錯誤拋出 `\Exception`

---

## 任務執行

- 任何修改前，先確認是否在 master 分支，如果是，提醒我是否確認創建新的分支
- 任務計畫都會寫在 `.claude/tasks/` 目錄下，請先閱讀任務目標跟計畫
- 所有計畫必須先讓我審核，確認無誤後才能動工，並要求每個步驟都需經過 Review
- 過程中，如果有發現代碼混亂、複雜度過高或者效能低下，或可讀性差，也可以簡單先提出來，之後再作改善
- **更多詳細架構說明**請參考 `.claude/architecture.md`
