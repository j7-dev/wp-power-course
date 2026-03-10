---
name: power-course
description: >-
  Power Course WordPress LMS 外掛全端開發技能 — 涵蓋前端 React + TypeScript + Refine.dev + Ant Design（js/src/ 內全部 223 個 TS/TSX 檔案），以及後端 PHP + WordPress + WooCommerce 的完整架構、模式、API 設計與編碼規範（inc/**/*.php）。
---

# Power Course 全端開發技能

> 適用範圍：新增功能、修改元件、建立頁面、整合 API、後端邏輯、資料庫操作。

---

## 專案概覽

Power Course 是一個 WordPress LMS 外掛，整合 WooCommerce 販售課程。

- **PHP Namespace 根：** `J7\PowerCourse\`
- **前端目錄：** `js/src/`（223 個 TS/TSX 檔案）
- **後端目錄：** `inc/classes/`（核心邏輯）、`inc/src/`（輔助領域工具）、`inc/templates/`（模板）
- **Required Plugins：** WooCommerce ≥ 7.6.0、Powerhouse ≥ 3.3.41
- **後端依賴：** `J7\WpUtils`（透過 Powerhouse vendor）

---

# 一、前端（JavaScript / TypeScript）

> **參考文件：**
> - [`references/frontend/types.md`](references/frontend/types.md) — 完整型別定義
> - [`references/frontend/file-list.md`](references/frontend/file-list.md) — 全 223 檔案清單

## 1. 專案架構總覽

### 1.1 雙應用入口

專案在 `main.tsx` 中掛載兩個獨立 React 應用：

| 應用 | 選擇器 | 用途 | 框架 |
|------|---------|------|------|
| **App1** | `APP1_SELECTOR` (多個) | 管理後台 SPA | Refine.dev + HashRouter |
| **App2** | `APP2_SELECTOR` (多個) | 影片播放器 | VidStack + 獨立狀態 |

```tsx
// main.tsx 掛載模式
document.querySelectorAll(APP1_SELECTOR).forEach(el => {
  createRoot(el).render(<App1 />)
})
document.querySelectorAll(APP2_SELECTOR).forEach(el => {
  createRoot(el).render(<App2 {...dataset} />)
})
```

### 1.2 Provider 堆疊 (App1)

```
QueryClientProvider
  └─ StyleProvider (antd cssinjs hashPriority="high")
      └─ EnvProvider (antd-toolkit)
          └─ BunnyProvider (antd-toolkit)
              └─ ConfigProvider (antd theme)
                  └─ Refine (dataProvider, resources, routerProvider)
                      └─ ThemedLayoutV2
                          └─ <Outlet /> (路由頁面)
```

### 1.3 QueryClient 設定

```tsx
{
  refetchOnWindowFocus: false,
  retry: 0,
  staleTime: 1000 * 60 * 10,  // 10 分鐘
  cacheTime: 1000 * 60 * 10,
}
```

### 1.4 七個 Data Provider

| 名稱 | 用途 | base URL |
|------|------|----------|
| `default` (powerhouse) | Powerhouse API | `/wp-json/powerhouse/` |
| `power-course` | 課程核心 API | `/wp-json/power-course/` |
| `power-email` | 郵件 API | `/wp-json/power-email/` |
| `wc-analytics` | WC 分析 API | `/wp-json/wc-analytics/` |
| `wp-rest` | WordPress REST | `/wp-json/wp/v2/` |
| `wc-rest` | WooCommerce REST | `/wp-json/wc/v3/` |
| `wc-store` | WC Store API | `/wp-json/wc/store/v1/` |
| `bunny-stream` | Bunny CDN | 外部 API |

### 1.5 路由結構 (HashRouter)

所有頁面使用 `React.lazy()` + `<Suspense fallback={<PageLoading />}>` 延遲載入：

```
#/courses          → CourseList
#/courses/edit/:id → CoursesEdit (tabs: 描述/價格/學生/銷售方案/分析/QA/公告/其他)
#/teachers         → Teachers
#/students         → Students
#/products         → Products
#/shortcodes       → Shortcodes
#/settings         → Settings
#/analytics        → Analytics
#/emails           → EmailsList
#/emails/edit/:id  → EmailEdit
#/media-library    → MediaLibraryPage
#/bunny-media-library → BunnyMediaLibraryPage
```

---

## 2. 型別系統

> 完整型別定義詳見 [`references/frontend/types.md`](references/frontend/types.md)

### 三個平行型別命名空間

| 命名空間 | 用途 | 主要型別 |
|----------|------|---------|
| `types/wcRestApi/` | WC REST API 後台管理 | `TProduct`, `TProductVariation`, `TMeta`, `TAttribute` |
| `types/wcStoreApi/` | WC Store API 前台商店 | `TProduct`, `TCart`, `TCartItem` |
| `types/wpRestApi/` | WP REST API 文章 | `TPost`, `TPostsArgs`, `TPagination`, `TImage` |

- **共用型別**: `THttpMethods`, `TOrderBy`, `TOrder`, `TDataProvider`
- **業務型別** (`pages/admin/Courses/List/types/`): `TCourseBaseRecord`, `TChapterRecord`, `TUserRecord`, `TExpireDate`
- **全域型別** (`types/global.d.ts`): `Window.appData.env`, `Window.wpApiSettings`

---

## 3. Hooks 慣例

### 3.1 環境變數 Hook

```tsx
// hooks/useEnv.tsx — 永遠用 useEnv() 存取環境變數，禁止直接讀 window
import { useEnv as useAntdToolkitEnv } from 'antd-toolkit'

export const useEnv = () => {
  const env = useAntdToolkitEnv()
  return {
    ...env,
    APP1_SELECTOR: env?.APP1_SELECTOR,
    ELEMENTOR_ENABLED: env?.ELEMENTOR_ENABLED,
    AXIOS_INSTANCE: env?.AXIOS_INSTANCE,
    // ... Power Course 專屬變數
  }
}
```

**重要**: 環境變數在 `utils/env.tsx` 中由 `simpleDecrypt(window.power_course_data.env)` 解密取得。

### 3.2 CRUD Drawer Hook

```tsx
// hooks/useUserFormDrawer.tsx — 表單 Drawer 完整 CRUD 模式
const { drawerProps, formProps, saveButtonProps, show, close } = useUserFormDrawer({
  action: 'create' | 'edit',
  resource: 'users',
})
// 特點：
// - multipart/form-data 上傳 (toFormData())
// - 未儲存變更偵測 (isEqual from lodash-es)
// - Popconfirm 關閉防護
// - 自動 invalidate 快取
```

### 3.3 Select Hook 模式

```tsx
// hooks/useProductSelect.tsx & hooks/useCourseSelect.tsx
const { selectProps } = useProductSelect()  // 或 useCourseSelect()
// 特點：
// - Refine useSelect 包裝
// - 伺服器端搜尋 (debounce 500ms)
// - dataProviderName: 'power-course'
// - 整合 antd-toolkit 的 defaultSelectProps
```

### 3.4 其他常用 Hooks

```tsx
// hooks/useGCDItems.tsx — 計算多選項目的交集
const { GCDItems, renderGCDItems } = useGCDItems<TCourseRecord>({
  items: selectedUsers.map(u => u.avl_courses),
  rowKey: 'id',
})

// hooks/useEditorDrawer.tsx — Notion 風格編輯器 Drawer 狀態管理
const { editorDrawerProps } = useEditorDrawer()
```

---

## 4. 工具函數

### 4.1 常數 (utils/constants.ts)

所有 UI 標籤使用**繁體中文**，例如 `productTypes`、`postStatus` 等。

### 4.2 狀態映射器 (utils/functions/)

```typescript
// 回傳 { label: string, color: string } 供 Ant Design Tag 使用
getOrderStatus(status)  // WC 訂單狀態
getPostStatus(status)   // WP 文章狀態
getASStatus(status)     // Action Scheduler 狀態
```

### 4.3 日期、影片 URL 解析

```typescript
parseDatePickerValue(value)  // 處理 10/13 位時間戳
getDateRangeProps()          // DateRangePicker disabled 規則
getYoutubeVideoId(url)       // youtu.be / youtube.com
getVimeoVideoId(url)         // 標準 Vimeo URL
```

### 4.4 其他工具

```typescript
getInitialFilters(params)  // key-value → Refine CrudFilters
// 預設幣值：NT$（新台幣）
```

---

## 5. 元件設計模式

### 5.1 元件分類架構

```
components/
├── general/      → 通用 UI 元件（與業務無關）
├── formItem/     → 表單元素包裝器（Form.Item 整合）
├── layout/       → 版面元件（Header, Sider, Layout）
├── course/       → 課程管理元件（章節排序、編輯）
├── chapters/     → 章節編輯元件
├── product/      → 商品展示元件（價格、庫存、分類）
├── user/         → 使用者管理元件（表格、存取權限）
├── emails/       → 郵件發送條件設定
└── post/         → 文章上傳元件
```

### 5.2 通用規則

- 每個目錄有 `index.tsx` barrel export
- 效能敏感元件用 `React.memo()` 包裝
- Props 定義 TypeScript 介面，型別放 `types/index.ts`

### 5.3 常用通用元件

```tsx
<Gallery images={['url1']} selectedImage="url1" />
<PopconfirmDelete popconfirmProps={{ onConfirm: handleDelete }} type="icon" | "button" />
<PageLoading type="empty" | "general" />
<DuplicateButton id={recordId} invalidateProps={{ resource: 'courses' }} />
<WaterMark qty={5} text="使用者名稱" interval={30} isPlaying={true} />
<ListSelect listSelectProps={useListSelect({...})} rowName="display_name" />
```

### 5.4 表單元素包裝器 (formItem/)

```tsx
<FiSwitch formItemProps={{ name: 'is_active' }} />   // boolean ↔ 'yes'/'no'
<DatePicker formItemProps={{ name: 'expire_date' }} /> // unix timestamp
<VideoInput />    // 多平台: youtube/vimeo/bunny/code
<VideoLength />   // 秒數 ↔ 時/分/秒
<WatchLimit />    // unlimited/fixed/assigned/follow_subscription
```

### 5.5 VideoInput 子元件架構

```
VideoInput/
├── index.tsx       → 主元件（Select 選擇類型）
├── Iframe.tsx      → 抽象 iframe（URL 解析 + 預覽）
├── Youtube.tsx / Vimeo.tsx / Bunny.tsx / Code.tsx
└── types/index.ts  → TVideoType, TVideo
```

### 5.6 使用者存取權限操作

```tsx
<GrantCourseAccess user_ids={ids} />                              // POST /courses/add-students
<RemoveCourseAccess user_ids={ids} course_ids={cids} />           // POST /courses/remove-students
<ModifyCourseExpireDate user_ids={ids} course_ids={cids} />       // POST /courses/update-students
```

---

## 6. 頁面層模式

### 6.1 課程編輯頁 Tab 結構

| Tab | 元件 | 功能 |
|-----|------|------|
| 課程描述 | `CourseDescription` | 名稱、slug、分類、媒體、講師 |
| 價格設定 | `CoursePrice` | 定價、特價、庫存、觀看期限 |
| 學生管理 | `CourseStudents` | 新增/移除/更新學生、匯出 CSV |
| 銷售方案 | `CourseBundles` | Bundle 商品管理（可排序列表） |
| 分析報表 | `CourseAnalysis` | Analytics 頁面 |
| QA 管理 | `CourseQA` | 可排序 Q&A 列表 |
| 其他設定 | `CourseOther` | 顯示/可見性/UI 選項 |

### 6.2 狀態管理模式

```tsx
// React Context（頁面/元件樹狀態）
const RecordContext = createContext<TCourseRecord | undefined>(undefined)
export const useRecord = () => useContext(RecordContext)

// Jotai（全域跨元件狀態）
selectedUserIdsAtom / historyDrawerAtom / mediaLibraryAtom
selectedProductsAtom / courseAtom / bundleProductAtom
```

### 6.3 列表頁 / 編輯頁模板

```tsx
// 列表頁
export const ListPage = () => {
  const { tableProps, searchFormProps } = useTable({ ... })
  return (
    <>
      <Filter searchFormProps={searchFormProps} />
      <Table {...tableProps} columns={columns} />
    </>
  )
}

// 編輯頁
export const EditPage = () => {
  const { formProps, saveButtonProps } = useForm({ ... })
  return (
    <Edit saveButtonProps={saveButtonProps}>
      <Form {...formProps}><Tabs items={tabItems} /></Form>
    </Edit>
  )
}
```

---

## 7. App2: VidStack 影片播放器

```tsx
// App2/Player.tsx：浮水印覆蓋、自動播放/靜音、HLS 串流、播放事件追蹤
// App2/Ended.tsx：10 秒倒數覆蓋，導航至下一章節，可取消
```

---

## 8. Refine API 呼叫慣例

```tsx
// 讀取 — 永遠指定 dataProviderName
const { data } = useList({
  resource: 'courses',
  dataProviderName: 'power-course',  // ← 必要
})

// 自訂端點
const apiUrl = useApiUrl('power-course')
const { mutate } = useCustomMutation()
mutate({
  url: `${apiUrl}/courses/add-students`,
  method: 'post',
  values: { user_ids, course_ids },
})
```

---

## 9. 編碼慣例與規範

| 項目 | 規範 |
|------|------|
| 縮排 | Tabs |
| 引號 | 單引號 |
| 分號 | 不使用 |
| 語言 | UI/注解繁體中文，變數/API 英文 |
| 幣值 | NT$（新台幣） |
| 元件檔案 | PascalCase 目錄 + `index.tsx` |
| Hook 檔案 | `use{Feature}.tsx` |
| 型別檔案 | `types/index.ts` |
| Import 路徑別名 | `@/` → `js/src/` |

---

# 二、後端（PHP）

> **參考文件（按需載入）：**
> - [`references/backend/chapter-system.md`](references/backend/chapter-system.md) — pc_chapter CPT、層級結構、MetaCRUD、排序
> - [`references/backend/power-email.md`](references/backend/power-email.md) — Email CPT、觸發機制、Replace 變數、EmailRecord
> - [`references/backend/template-system.md`](references/backend/template-system.md) — inc/templates/ 結構、覆寫機制、CSS 規範
> - [`references/backend/settings-watermark-student.md`](references/backend/settings-watermark-student.md) — 設定 DTO、水印佔位符、學員查詢、StudentLog
> - [`references/backend/admin-compat.md`](references/backend/admin-compat.md) — Admin\Product、Compatibility、BundleProduct\Helper

## 1. PHP 檔案基本規範

### 1.1 每個 PHP 檔案的強制頭部

```php
<?php
/**
 * 此類別的一行繁體中文說明
 */

declare( strict_types=1 );

namespace J7\PowerCourse\{Subdomain}\{ClassName};
```

- `declare(strict_types=1)` 必須出現在每個 PHP 檔案
- namespace 遵照 PSR-4，與目錄結構對應
- 每個類別需要一行繁體中文 docblock 說明用途

### 1.2 屬性與方法文件規範

```php
/** @var string 屬性用途說明（繁體中文） */
public static string $table_name = Plugin::COURSE_TABLE_NAME;

/**
 * 取得課程資料
 *
 * @param int $course_id 課程 ID
 * @return array<string, mixed>
 */
public function get_course_data( int $course_id ): array { ... }
```

---

## 2. 類別設計模式

### 2.1 Singleton（final class + SingletonTrait）

適用於：需要掛載 hooks 的服務類、API 類、CPT 類

```php
final class MyService {
    use \J7\WpUtils\Traits\SingletonTrait;

    public function __construct() {
        \add_action( 'init', [ $this, 'init_callback' ] );
    }
}

MyService::instance(); // ✅
new MyService();       // ❌ 禁止
```

**規則：** Hooks 永遠只在 `__construct()` 中掛，`final class` 防止繼承。

### 2.2 Abstract Class（純靜態工具類）

```php
abstract class CourseUtils {
    public static function is_course_product( \WC_Product $product ): bool {
        return 'yes' === $product->get_meta('_is_course');
    }
}
CourseUtils::is_course_product($product); // ✅
```

### 2.3 一般 Class（帶靜態工廠的值物件）

```php
class Limit {
    public static function instance( \WC_Product|int $product ): static {
        $obj = new static();
        // 從 product meta 填充屬性...
        return $obj;
    }
}
$limit = Limit::instance($product); // ✅
```

### 2.4 Trait（跨類別共用邏輯）

```php
trait UserTrait {
    public function get_courses_with_id_students_callback( \WP_REST_Request $request ): \WP_REST_Response { ... }
}

final class CourseApi extends ApiBase {
    use \J7\WpUtils\Traits\SingletonTrait;
    use UserTrait;
}
```

---

## 3. REST API 規範

### 3.1 模式 A — 繼承 ApiBase（推薦）

```php
final class MyApi extends \J7\WpUtils\Classes\ApiBase {
    use \J7\WpUtils\Traits\SingletonTrait;

    protected $namespace = 'power-course';

    protected $apis = [
        [
            'endpoint'            => 'resource',
            'method'              => 'get',
            'permission_callback' => null, // null = 公開
        ],
        [
            'endpoint' => 'resource/(?P<id>\d+)',
            'method'   => 'post',
        ],
    ];

    // callback 命名：{method}_{endpoint_snake}_callback
    public function get_resource_callback( \WP_REST_Request $request ): \WP_REST_Response { ... }
    public function post_resource_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response { ... }
}
```

### 3.2 REST 回應規範

```php
// 成功
return new \WP_REST_Response(
    [ 'code' => 'get_resource_success', 'message' => '取得成功', 'data' => $data ],
    200
);

// 錯誤
return new \WP_Error( 'error_code', '錯誤訊息', [ 'status' => 400 ] );
```

### 3.3 分頁 Header

```php
$response->header( 'X-WP-Total',       $total );
$response->header( 'X-WP-TotalPages',  $total_pages );
$response->header( 'X-WP-CurrentPage', $page );
$response->header( 'X-WP-PageSize',    $posts_per_page );
```

### 3.4 請求參數處理

```php
$body_params = $request->get_json_params() ?? [];
$body_params = \J7\WpUtils\Classes\WP::sanitize_text_field_deep( $body_params, false );

[ 'data' => $data, 'meta_data' => $meta_data ] =
    \J7\WpUtils\Classes\WP::separator( $body_params, 'product', $files );
```

---

## 4. 資料庫操作規範

### 4.1 自定義資料表名稱常數（Plugin.php）

| 常數 | 表名 | 用途 |
|------|------|------|
| `Plugin::COURSE_TABLE_NAME` | `{prefix}_pc_avl_coursemeta` | 學員↔課程 meta |
| `Plugin::CHAPTER_TABLE_NAME` | `{prefix}_pc_avl_chaptermeta` | 學員↔章節進度 |
| `Plugin::EMAIL_RECORDS_TABLE_NAME` | `{prefix}_pc_email_records` | Email 寄送記錄 |
| `Plugin::STUDENT_LOGS_TABLE_NAME` | `{prefix}_pc_student_logs` | 學生行為日誌 |

### 4.2 多步驟寫入使用 Transaction

```php
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    // 多個寫入操作...
    $wpdb->query( 'COMMIT' );
} catch ( \Exception $e ) {
    $wpdb->query( 'ROLLBACK' );
    Plugin::logger( $e->getMessage(), 'critical' );
}
```

### 4.3 陣列型 Meta 欄位

```php
// ❌ 不要用 update_post_meta 儲存陣列
// ✅ 先刪除再逐筆新增
$product->delete_meta_data( 'teacher_ids' );
foreach ( $teacher_ids as $teacher_id ) {
    $product->add_meta_data( 'teacher_ids', $teacher_id );
}
$product->save_meta_data();
```

### 4.4 批次排序使用 CASE WHEN SQL

```php
$wpdb->query(
    "UPDATE {$wpdb->posts} SET menu_order = CASE id
        WHEN {$id1} THEN 0
        WHEN {$id2} THEN 1
    END WHERE id IN ({$ids_string})"
);
```

---

## 5. Hook 系統規範

### 5.1 核心 Action Hook 常數（LifeCycle.php）

| 常數 | Hook 名稱 | 觸發時機 |
|------|-----------|----------|
| `ADD_STUDENT_TO_COURSE_ACTION` | `power_course_add_student_to_course` | 請求開通課程 |
| `AFTER_ADD_STUDENT_TO_COURSE_ACTION` | `power_course_after_add_student_to_course` | 開通完成後 |
| `AFTER_REMOVE_STUDENT_FROM_COURSE_ACTION` | `power_course_after_remove_student_from_course` | 移除學員後 |
| `AFTER_UPDATE_STUDENT_FROM_COURSE_ACTION` | `power_course_after_update_student_from_course` | 更新學員後 |
| `COURSE_LAUNCHED_ACTION` | `power_course_course_launch` | 課程開課日到達 |
| `COURSE_FINISHED_ACTION` | `power_course_course_finished` | 學員進度達 100% |

### 5.2 重要規則

> **永遠使用 `do_action()` 觸發課程開通，不要直接呼叫 `LifeCycle` 的方法**

```php
// ✅ 正確
do_action( LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, $user_id, $course_id, $expire_date, $order );

// ❌ 禁止
LifeCycle::add_student_to_course( $user_id, $course_id, $expire_date );
```

---

## 6. 課程系統

### 6.1 課程識別

```php
// 課程 = WooCommerce 商品 + _is_course meta = 'yes'
CourseUtils::is_course_product( $product );     // bool
CourseUtils::is_avl( $course_id, $user_id );    // 學員是否有存取權
CourseUtils::is_course_ready( $product );        // 課程是否已開課
CourseUtils::is_expired( $product, $user_id );  // 存取是否已到期
CourseUtils::get_course_progress( $product, $user_id ); // float 0-100
```

### 6.2 過期日期類型

| 值 | 含義 |
|----|------|
| `0` | 無期限存取 |
| `1735689600` | 特定 Unix timestamp |
| `'subscription_123'` | 跟隨 WC Subscription #123 |

### 6.3 課程限制類型（Limit.php）

```php
$limit = Limit::instance( $product );
// limit_type: 'unlimited' | 'fixed' | 'assigned' | 'follow_subscription'
$expire_date = $limit->calc_expire_date( $order );
```

### 6.4 AddStudent（防重複學員新增）

```php
$add_student = new AddStudent();
$add_student->add_item( $user_id, $course_id, $expire_date, $order );
// 相同 course_id + user_id 的後者會蓋前者（去重）
$add_student->do_action();
```

---

## 7. 日誌與錯誤處理

```php
Plugin::logger( 'message', 'debug' );
Plugin::logger( 'message', 'info' );
Plugin::logger( 'message', 'warning' );
Plugin::logger( 'message', 'critical', $context );

// 域邏輯錯誤
throw new \Exception( '課程不存在' );

// REST 錯誤
return new \WP_Error( 'course_not_found', '課程不存在', [ 'status' => 404 ] );
```

---

## 8. 背景任務（Action Scheduler）

```php
\as_enqueue_async_action( 'hook_name', $args, 'group_name' );          // 非同步
\as_schedule_single_action( $timestamp, 'hook_name', $args, 'group' ); // 延遲
\as_schedule_recurring_action( time(), INTERVAL, 'hook_name', [] );     // 定期
```

> 使用 AS 而不是 WP-Cron 處理背景任務。

---

## 9. 常見錯誤模式（務必避免）

```php
// ❌ 直接呼叫 LifeCycle → ✅ 使用 do_action
// ❌ new MyService()     → ✅ MyService::instance()
// ❌ hooks 在 init()     → ✅ hooks 只在 __construct()
// ❌ update_post_meta 儲存陣列 → ✅ delete + 逐筆 add_meta_data
// ❌ Bundle 包含 Bundle  → Bundle 內不可再包含 Bundle Product
```

---

## 10. 開發常用指令

```bash
pnpm run dev          # 啟動前端開發伺服器 (port 5174)
pnpm run build        # 建置生產版本
pnpm run lint:php     # phpcbf + phpcs + phpstan
pnpm run lint:ts      # ESLint TypeScript
pnpm run format       # Prettier 格式化
pnpm run release      # 發佈 patch + 建置 + tag + push
composer run phpstan  # PHP 靜態分析
```
