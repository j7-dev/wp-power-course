---
name: power-course-php
description: Power Course WordPress LMS 外掛的 PHP 開發規範與架構指南。涵蓋 Singleton 模式、REST API 設計、資料庫操作、Hook 系統、Chapter/PowerEmail 子系統等完整規範，適用於 inc/**/*.php 範圍內的所有 PHP 開發工作。
---

# Power Course PHP 開發 SKILL

## 專案概覽

Power Course 是一個 WordPress LMS 外掛，整合 WooCommerce 販售課程。  
- **PHP Namespace 根：** `J7\PowerCourse\`  
- **主要目錄：** `inc/classes/`（核心邏輯）、`inc/src/`（輔助領域工具）、`inc/templates/`（模板）  
- **Required Plugins：** WooCommerce ≥ 7.6.0、Powerhouse ≥ 3.3.41  
- **依賴套件：** `J7\WpUtils`（透過 Powerhouse vendor）

---

## 一、PHP 檔案基本規範

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
 * @return array<string, mixed> 課程資料
 */
public function get_course_data( int $course_id ): array { ... }
```

- 每個屬性必須有 `/** @var type 說明 */`
- 每個方法必須有 docblock + PHPDoc 型別提示
- 說明文字使用**繁體中文**

---

## 二、類別設計模式

### 2.1 Singleton（final class + SingletonTrait）

適用於：需要掛載 hooks 的服務類、API 類、CPT 類

```php
final class MyService {
    use \J7\WpUtils\Traits\SingletonTrait;

    /** @var string 服務名稱 */
    public string $name = 'my-service';

    public function __construct() {
        \add_action( 'init', [ $this, 'init_callback' ] );
        \add_filter( 'some_filter', [ $this, 'filter_callback' ] );
    }
}

// 呼叫
MyService::instance(); // ✅
new MyService();       // ❌ 禁止
```

**規則：**
- Hooks **永遠只在** `__construct()` 中 `add_action` / `add_filter`
- 永遠使用 `MyClass::instance()` 初始化，不用 `new`
- `final class` 防止繼承

### 2.2 Abstract Class（純靜態工具類）

適用於：無狀態工具方法、跨模組共用邏輯

```php
abstract class CourseUtils {
    /**
     * 判斷是否為課程商品
     *
     * @param \WC_Product $product WC 商品物件
     * @return bool
     */
    public static function is_course_product( \WC_Product $product ): bool {
        return 'yes' === $product->get_meta('_is_course');
    }
}

// 呼叫
CourseUtils::is_course_product($product); // ✅
new CourseUtils(); // ❌ abstract，無法實例化
```

### 2.3 一般 Class（帶靜態工廠的值物件）

適用於：封裝特定業務資料的非 Singleton 物件

```php
class Limit {
    /** @var string 限制類型 */
    public string $limit_type = 'unlimited';

    /**
     * 靜態工廠方法
     *
     * @param \WC_Product|int $product 商品物件或 ID
     * @return static
     */
    public static function instance( \WC_Product|int $product ): static {
        $obj = new static();
        // 從 product meta 填充屬性...
        return $obj;
    }
}

// 呼叫
$limit = Limit::instance($product); // ✅
```

### 2.4 Trait（跨類別共用邏輯）

適用於：多個 API class 共用的 callback 方法

```php
trait UserTrait {
    /**
     * 取得課程學生列表
     *
     * @param \WP_REST_Request $request REST 請求
     * @return \WP_REST_Response
     */
    public function get_courses_with_id_students_callback( \WP_REST_Request $request ): \WP_REST_Response {
        // ...
    }
}

final class CourseApi extends ApiBase {
    use \J7\WpUtils\Traits\SingletonTrait;
    use UserTrait; // 引入跨模組共用邏輯
}
```

---

## 三、REST API 規範

### 3.1 模式 A — 繼承 ApiBase（推薦/主要模式）

```php
final class MyApi extends \J7\WpUtils\Classes\ApiBase {
    use \J7\WpUtils\Traits\SingletonTrait;

    /** @var string REST namespace */
    protected $namespace = 'power-course';

    /** @var array<int, array{endpoint: string, method: string, permission_callback?: callable|null}> */
    protected $apis = [
        [
            'endpoint'            => 'resource',
            'method'              => 'get',
            'permission_callback' => null, // null = 公開
        ],
        [
            'endpoint'            => 'resource/(?P<id>\d+)',
            'method'              => 'post',
        ],
    ];

    public function __construct() {
        parent::__construct();
    }

    // callback 命名：{method}_{endpoint_snake}_callback
    public function get_resource_callback( \WP_REST_Request $request ): \WP_REST_Response {
        // ...
    }

    public function post_resource_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response {
        // ...
    }
}
```

### 3.2 模式 B — ApiRegisterTrait（舊式，用於 Product.php）

```php
final class Product {
    use \J7\WpUtils\Traits\SingletonTrait;
    use \J7\WpUtils\Traits\ApiRegisterTrait;

    public function __construct() {
        \add_action( 'rest_api_init', [ $this, 'register_api_products' ] );
    }
}
```

> 新增 API 類別優先使用模式 A（ApiBase 繼承）。

### 3.3 REST 回應規範

```php
// 成功回應
return new \WP_REST_Response(
    [
        'code'    => 'get_resource_success',
        'message' => '取得成功',
        'data'    => $data,
    ],
    200
);

// 錯誤回應
return new \WP_REST_Response(
    [
        'code'    => 'get_resource_error',
        'message' => '找不到資源',
        'data'    => null,
    ],
    404
);

// 或使用 WP_Error
return new \WP_Error( 'error_code', '錯誤訊息', [ 'status' => 400 ] );
```

### 3.4 分頁 Header

```php
$response = new \WP_REST_Response( $data, 200 );
$response->header( 'X-WP-Total',       $total );
$response->header( 'X-WP-TotalPages',  $total_pages );
$response->header( 'X-WP-CurrentPage', $page );
$response->header( 'X-WP-PageSize',    $posts_per_page );
return $response;
```

### 3.5 請求參數處理

```php
$body_params = $request->get_json_params() ?? [];
$body_params = \J7\WpUtils\Classes\WP::sanitize_text_field_deep( $body_params, false );
// 跳過特定鍵的消毒（如 description 含 HTML）
$body_params = \J7\WpUtils\Classes\WP::sanitize_text_field_deep( $body_params, true, [ 'description' ] );

// 分離 post data 和 meta data
[
    'data'      => $data,
    'meta_data' => $meta_data,
] = \J7\WpUtils\Classes\WP::separator( $body_params, 'product', $files );
```

---

## 四、資料庫操作規範

### 4.1 自定義資料表（Abstract CRUD）

```php
// 繼承抽象 CRUD 類別
abstract class MetaCRUD {
    /** @var string 資料表名稱（不含 $wpdb->prefix） */
    public static string $table_name = Plugin::COURSE_TABLE_NAME;

    public static function get( array $where ): array { /* ... */ }
    public static function add( int $post_id, int $user_id, string $meta_key, mixed $meta_value ): int|false { /* ... */ }
    public static function update( array $where, array $data ): int|false { /* ... */ }
    public static function delete( int $id ): int|false { /* ... */ }
}
```

### 4.2 自定義資料表名稱常數（Plugin.php）

| 常數 | 表名 | 用途 |
|------|------|------|
| `Plugin::COURSE_TABLE_NAME` | `{prefix}_pc_avl_coursemeta` | 學員↔課程 meta |
| `Plugin::CHAPTER_TABLE_NAME` | `{prefix}_pc_avl_chaptermeta` | 學員↔章節進度 |
| `Plugin::EMAIL_RECORDS_TABLE_NAME` | `{prefix}_pc_email_records` | Email 寄送記錄 |
| `Plugin::STUDENT_LOGS_TABLE_NAME` | `{prefix}_pc_student_logs` | 學生行為日誌 |

### 4.3 多步驟寫入使用 Transaction

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

### 4.4 陣列型 Meta 欄位（多筆 meta row 模式）

```php
// ❌ 不要用 update_post_meta 儲存陣列
// ✅ 先刪除再逐筆新增
$product->delete_meta_data( 'teacher_ids' );
foreach ( $teacher_ids as $teacher_id ) {
    $product->add_meta_data( 'teacher_ids', $teacher_id );
}
$product->save_meta_data();
```

### 4.5 批次排序使用 CASE WHEN SQL

```php
// 例：sort_chapters() 在 Chapter\Core\Api.php
$wpdb->query(
    "UPDATE {$wpdb->posts} SET menu_order = CASE id
        WHEN {$id1} THEN 0
        WHEN {$id2} THEN 1
    END
    WHERE id IN ({$ids_string})"
);
```

---

## 五、Hook 系統規範

### 5.1 核心 Action Hook 常數（LifeCycle.php）

```php
use J7\PowerCourse\Resources\Course\LifeCycle;

// 開通課程（永遠用 do_action，不直接呼叫 LifeCycle 方法）
\do_action(
    LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, // 'power_course_add_student_to_course'
    $user_id,    // int
    $course_id,  // int
    $expire_date, // 0=無期限 | timestamp | 'subscription_{id}'
    $order        // \WC_Order|null
);
```

| 常數 | Hook 名稱 | 觸發時機 |
|------|-----------|----------|
| `ADD_STUDENT_TO_COURSE_ACTION` | `power_course_add_student_to_course` | 請求開通課程 |
| `AFTER_ADD_STUDENT_TO_COURSE_ACTION` | `power_course_after_add_student_to_course` | 開通完成後 |
| `AFTER_REMOVE_STUDENT_FROM_COURSE_ACTION` | `power_course_after_remove_student_from_course` | 移除學員後 |
| `AFTER_UPDATE_STUDENT_FROM_COURSE_ACTION` | `power_course_after_update_student_from_course` | 更新學員後 |
| `COURSE_LAUNCHED_ACTION` | `power_course_course_launch` | 課程開課日到達 |
| `COURSE_FINISHED_ACTION` | `power_course_course_finished` | 學員進度達 100% |
| `BEFORE_UPDATE_PRODUCT_META_ACTION` | `power_course_before_update_product_meta` | 儲存商品 meta 前 |

### 5.2 Chapter Hook 常數（Chapter\Core\LifeCycle.php）

| 常數 | Hook 名稱 |
|------|-----------|
| `CHAPTER_ENTERED_ACTION` | `power_course_visit_chapter` |
| `CHAPTER_FINISHED_ACTION` | `power_course_chapter_finished` |
| `CHAPTER_UNFINISHEDED_ACTION` | `power_course_chapter_unfinished` |

### 5.3 重要規則

> **永遠使用 `do_action()` 觸發課程開通，不要直接呼叫 `LifeCycle` 的 private/protected 方法**

---

## 六、日誌與錯誤處理

```php
// 記錄日誌
Plugin::logger( 'message', 'debug' );    // 開發除錯
Plugin::logger( 'message', 'info' );     // 一般資訊
Plugin::logger( 'message', 'warning' );  // 警告
Plugin::logger( 'message', 'critical', $context ); // 嚴重錯誤，可傳入 context array

// 域邏輯錯誤 → throw Exception
throw new \Exception( '課程不存在' );

// REST 錯誤 → WP_Error 或 WP_REST_Response
return new \WP_Error( 'course_not_found', '課程不存在', [ 'status' => 404 ] );
```

---

## 七、背景任務（Action Scheduler）

```php
// 排程一次性非同步任務
\as_enqueue_async_action( 'hook_name', $args, 'group_name' );

// 排程延遲任務（指定時間戳）
\as_schedule_single_action( $timestamp, 'hook_name', $args, 'group_name' );

// 排程定期任務
\as_schedule_recurring_action( time(), INTERVAL, 'hook_name', [], 'group_name' );

// 取消排程（防重複排程前先查詢）
$scheduled = \as_get_scheduled_actions(
    [
        'hook'     => 'power_email_send_course_granted',
        'args'     => [ 'user_id' => $user_id ],
        'status'   => \ActionScheduler_Store::STATUS_PENDING,
        'per_page' => 1,
    ]
);
if ( empty( $scheduled ) ) {
    \as_schedule_single_action( $timestamp, 'hook_name', $args );
}
```

> 使用 AS 而不是 WP-Cron 處理背景任務。

---

## 八、課程（Course）系統

### 8.1 課程識別

```php
// 課程 = WooCommerce 商品 + _is_course meta = 'yes'
CourseUtils::is_course_product( $product );     // bool
CourseUtils::is_avl( $course_id, $user_id );    // 學員是否有存取權
CourseUtils::is_course_ready( $product );        // 課程是否已開課
CourseUtils::is_expired( $product, $user_id );  // 存取是否已到期
CourseUtils::get_course_progress( $product, $user_id ); // float 0-100
```

### 8.2 過期日期類型（ExpireDate）

| 值 | 含義 |
|----|------|
| `0` | 無期限存取 |
| `1735689600` | 特定 Unix timestamp |
| `'subscription_123'` | 跟隨 WC Subscription #123 |

### 8.3 課程限制類型（Limit.php）

```php
$limit = Limit::instance( $product );
// limit_type: 'unlimited' | 'fixed' | 'assigned' | 'follow_subscription'
// limit_unit: 'timestamp' | 'day' | 'month' | 'year'
$expire_date = $limit->calc_expire_date( $order ); // 計算實際到期時間
```

`fixed` 類型：過期時間固定為當天 `15:59:00`。

### 8.4 Bundle Product（銷售方案）

```php
// Bundle = WC Product + bundle_type meta
// INCLUDE_PRODUCT_IDS_META_KEY = 'pbp_product_ids'
// LINK_COURSE_IDS_META_KEY = 'link_course_ids'
// 購買 Bundle 時自動開通關聯課程
// ❌ Bundle 內不可再包含 Bundle Product
```

### 8.5 AddStudent（防重複學員新增）

```php
$add_student = new AddStudent();
$add_student->add_item( $user_id, $course_id, $expire_date, $order );
// 相同 course_id + user_id 的後者會蓋前者（去重）
$add_student->do_action(); // 統一觸發所有 ADD_STUDENT_TO_COURSE_ACTION
```

---

## 延伸參考

以下為詳細子系統文件，按需載入：

- [章節（Chapter）系統](references/chapter-system.md) — pc_chapter CPT、層級結構、MetaCRUD、排序
- [PowerEmail 子系統](references/power-email.md) — Email CPT、觸發機制、Replace 變數、EmailRecord
- [模板系統](references/template-system.md) — inc/templates/ 結構、覆寫機制、CSS 規範
- [Settings、水印與 Student 系統](references/settings-watermark-student.md) — 設定 DTO、水印佔位符、學員查詢、StudentLog
- [Admin 擴充與 Compatibility 層](references/admin-compat.md) — Admin\Product、Compatibility、BundleProduct\Helper

---

## 十七、常見錯誤模式（務必避免）

```php
// ❌ 直接呼叫 LifeCycle 方法開通課程
LifeCycle::add_student_to_course( $user_id, $course_id, $expire_date );

// ✅ 使用 do_action
do_action( LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, $user_id, $course_id, $expire_date, $order );

// ❌ 用 new 建立 Singleton
$service = new MyService();

// ✅ 用 instance()
$service = MyService::instance();

// ❌ 在 init() 等方法中掛 hooks
public function init() {
    add_action( 'save_post', [ $this, 'save' ] );
}

// ✅ hooks 只在 __construct()
public function __construct() {
    add_action( 'save_post', [ $this, 'save' ] );
}

// ❌ 用 update_post_meta 儲存陣列
update_post_meta( $id, 'teacher_ids', [ 1, 2, 3 ] );

// ✅ 先刪除再逐筆新增
$product->delete_meta_data( 'teacher_ids' );
foreach ( $ids as $id ) {
    $product->add_meta_data( 'teacher_ids', $id );
}

// ❌ Bundle 包含 Bundle
// Bundle Product 內的 include_product_ids 不可包含另一個 Bundle Product
```

---

## 十八、Cron 排程（每 10 分鐘）

```php
// hook: 'power_course_schedule_action'
// 由 inc/classes/Bootstrap.php 透過 Action Scheduler 設定
```

---

## 十九、開發常用指令

```bash
pnpm run dev          # 啟動開發伺服器 (port 5174)
pnpm run build        # 建置生產版本
pnpm run lint:php     # phpcbf + phpcs + phpstan
pnpm run lint:ts      # ESLint TypeScript
pnpm run format       # Prettier 格式化
pnpm run release      # 發佈 patch + 建置 + tag + push
pnpm run sync:version # 同步版本 package.json → plugin.php
```
