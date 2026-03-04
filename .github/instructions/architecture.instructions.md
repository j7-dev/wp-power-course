---
applyTo: "**/*.php,**/*.tsx,**/*.ts"
---

# Power Course — Architecture Reference

> **Last Updated:** 2025-01-31 | **Version:** 0.11.23

---

## Directory Structure

```
power-course/
├── plugin.php               # 外掛進入點，定義 Plugin class (Singleton + PluginTrait)
├── inc/
│   ├── classes/             # PSR-4 root → J7\PowerCourse\
│   │   ├── AbstractTable.php         # 建立自訂 DB 表格的抽象類
│   │   ├── Bootstrap.php             # 服務容器 / 初始化所有 singletons
│   │   ├── Admin/
│   │   │   ├── Entry.php             # 管理後台選單進入點
│   │   │   ├── Product.php           # WC 商品後台整合（is_course 勾選框）
│   │   │   └── ProductQuery.php      # 商品查詢擴展
│   │   ├── Api/
│   │   │   ├── Course.php            # 課程 REST API + UserTrait
│   │   │   ├── Course/UserTrait.php  # 學員管理 API (add/remove/update)
│   │   │   ├── Comment.php           # 留言 API
│   │   │   ├── Option.php            # 設定選項 API
│   │   │   ├── Product.php           # 商品 API
│   │   │   ├── Reports/Revenue/Api.php # 營收報表 API
│   │   │   ├── Shortcode.php         # Shortcode 渲染 API
│   │   │   ├── Upload.php            # 檔案上傳 API
│   │   │   └── User.php              # 用戶管理 API
│   │   ├── BundleProduct/
│   │   │   └── Helper.php            # 銷售方案邏輯（bundle_type meta）
│   │   ├── Compatibility/
│   │   │   ├── Compatibility.php     # 版本遷移主控（AS 排程執行）
│   │   │   ├── Chapter.php           # 章節結構遷移
│   │   │   ├── Course.php            # 課程設定遷移
│   │   │   └── BundleProduct.php     # 銷售方案可見度遷移
│   │   ├── FrontEnd/
│   │   │   └── MyAccount.php         # WC My Account 整合
│   │   ├── PowerEmail/
│   │   │   ├── Bootstrap.php         # Email 子系統初始化
│   │   │   └── Resources/
│   │   │       ├── Email/
│   │   │       │   ├── CPT.php        # pc_email 自訂文章類型
│   │   │       │   ├── Api.php        # Email 模板 CRUD API
│   │   │       │   ├── Email.php      # Email 發送邏輯
│   │   │       │   ├── Trigger/
│   │   │       │   │   ├── At.php     # 觸發時機點處理（AS 排程）
│   │   │       │   │   ├── AtHelper.php # 觸發時機點常數與 helpers
│   │   │       │   │   └── Condition.php # 觸發條件判斷
│   │   │       │   └── Replace/
│   │   │       │       ├── User.php   # Email 用戶變數替換
│   │   │       │       ├── Course.php # Email 課程變數替換
│   │   │       │       └── Chapter.php # Email 章節變數替換
│   │   │       └── EmailRecord/
│   │   │           └── CRUD.php       # Email 發送紀錄 CRUD
│   │   ├── Resources/
│   │   │   ├── Loader.php            # 資源初始化器
│   │   │   ├── Chapter/
│   │   │   │   ├── Core/
│   │   │   │   │   ├── Api.php        # 章節 REST API
│   │   │   │   │   ├── CPT.php        # pc_chapter CPT 註冊
│   │   │   │   │   ├── LifeCycle.php  # 章節 hooks（進入/完成/未完成）
│   │   │   │   │   ├── Loader.php     # 章節資源初始化
│   │   │   │   │   └── Templates.php  # 章節模板載入
│   │   │   │   ├── Model/
│   │   │   │   │   └── Chapter.php    # Chapter Model（用戶×章節 資料）
│   │   │   │   └── Utils/
│   │   │   │       ├── MetaCRUD.php   # pc_avl_chaptermeta CRUD
│   │   │   │       └── Utils.php      # 章節工具（格式化、建立、排序）
│   │   │   ├── Comment.php           # 留言整合 hooks
│   │   │   ├── Course/
│   │   │   │   ├── BindCourseData.php  # 單一課程↔商品綁定
│   │   │   │   ├── BindCoursesData.php # 多課程↔商品綁定
│   │   │   │   ├── ExpireDate.php      # 到期日 DTO
│   │   │   │   ├── LifeCycle.php       # 課程 hooks（開通/移除/完成/開課）
│   │   │   │   ├── Limit.php           # 課程限制（無限/固定/指定/跟隨訂閱）
│   │   │   │   ├── MetaCRUD.php        # pc_avl_coursemeta CRUD
│   │   │   │   └── Service/
│   │   │   │       └── AddStudent.php  # 新增學員服務
│   │   │   ├── Order.php              # WC 訂單 hooks（觸發開課）
│   │   │   ├── Settings/
│   │   │   │   ├── Core/Api.php        # 設定 REST API
│   │   │   │   └── Model/Settings.php  # 設定 DTO（power_course_settings）
│   │   │   ├── Student/
│   │   │   │   ├── Core/
│   │   │   │   │   ├── Api.php         # 學員 API
│   │   │   │   │   └── ExtendQuery.php # 擴展 WP_User_Query
│   │   │   │   └── Service/
│   │   │   │       ├── ExportCSV.php   # 學員 CSV 匯出
│   │   │   │       └── Query.php       # 學員查詢服務
│   │   │   ├── StudentLog/
│   │   │   │   ├── CRUD.php            # pc_student_logs CRUD
│   │   │   │   └── StudentLog.php      # 學員日誌 DTO
│   │   │   └── Teacher/
│   │   │       └── Core/ExtendQuery.php # 講師查詢擴展
│   │   ├── Shortcodes/
│   │   │   └── General.php           # 短代碼（課程列表、按鈕等）
│   │   ├── Templates/
│   │   │   ├── Ajax.php              # AJAX 模板端點
│   │   │   └── Templates.php         # 模板系統（single-pc_chapter 等）
│   │   └── Utils/
│   │       ├── Base.php              # 基礎常數與工具（APP1_SELECTOR 等）
│   │       ├── Comment.php           # 留言工具
│   │       ├── Course.php            # 課程工具（is_avl, get_progress 等）
│   │       ├── Duplicate.php         # 複製功能
│   │       ├── MetaCRUD.php          # 自訂 meta 表格 CRUD 抽象
│   │       └── User.php              # 用戶工具
│   ├── src/                 # PSR-4 root → J7\PowerCourse\ (Domain layer)
│   │   └── Domain/
│   │       └── Product/
│   │           ├── Events/Edit.php   # 商品批量編輯 event
│   │           └── Helper/IsCourse.php # _is_course meta helper
│   └── templates/
│       ├── single-pc_chapter.php     # 章節 single 頁面模板
│       ├── course-product-entry.php  # 課程商品入口
│       ├── components/               # 可重用 PHP 元件
│       │   ├── video/                # 影片播放器（vidstack, bunny, youtube, vimeo）
│       │   ├── badge/                # 徽章（popular, feature, join）
│       │   ├── button/               # 按鈕（add-to-cart）
│       │   ├── card/                 # 卡片（pricing, bundle, subscription）
│       │   ├── collapse/             # 折疊（chapters, qa）
│       │   ├── countdown/            # 倒數計時
│       │   ├── icon/                 # SVG 圖示
│       │   ├── progress/             # 進度條
│       │   ├── review/               # 評論元件
│       │   ├── tabs/                 # 分頁元件
│       │   └── user/                 # 用戶資訊
│       └── pages/
│           ├── course-product/       # 課程銷售頁（header/body/sider/tabs/footer）
│           ├── classroom/            # 學習教室（header/body/sider/chapters）
│           ├── my-account/           # WC My Account 整合
│           └── 404/                  # 存取拒絕頁（buy/expired/not-ready）
├── js/
│   └── src/
│       ├── main.tsx                  # React 進入點
│       ├── App1.tsx                  # 管理後台 SPA (refine.dev + HashRouter)
│       ├── App2/                     # VidStack 影片播放器
│       │   ├── index.tsx             # App2 主元件
│       │   └── Player.tsx            # VidStack 播放器元件
│       ├── components/               # 按功能模組分類的 React 元件
│       ├── hooks/                    # 自定義 React Hooks
│       │   ├── useEnv.tsx            # 環境變數 hook（解密）
│       │   └── ...
│       ├── pages/admin/              # 管理後台頁面（對應路由）
│       ├── resources/
│       │   └── index.tsx             # refine.dev 資源定義（路由+圖示）
│       ├── types/                    # TypeScript 類型定義
│       └── utils/                    # 工具函數
├── composer.json                     # PHP 依賴 + PSR-4 autoload
├── package.json                      # JS 依賴 + scripts
├── vite.config.ts                    # Vite 設定（port 5174, v4wp 整合）
├── phpcs.xml                         # PHP CodeSniffer 設定
├── phpstan.neon                      # PHPStan 設定
└── plugin.php                        # 外掛主檔案
```

---

## 資料流

### 課程購買 → 開通權限流程
```
WC 訂單完成
    └─ Resources/Order.php (woocommerce_order_status_changed hook)
        └─ 判斷訂單商品是否有 bind_courses_data meta
            └─ do_action('power_course_add_student_to_course', ...)
                ├─ LifeCycle::add_order_created_log()     (priority 10)
                └─ LifeCycle::add_student_to_course()     (priority 20)
                    ├─ add_user_meta($user_id, 'avl_course_ids', $course_id)
                    ├─ AVLCourseMeta::update('expire_date', ...)
                    ├─ AVLCourseMeta::update('course_granted_at', ...)
                    └─ do_action('power_course_after_add_student_to_course', ...)
                        ├─ LifeCycle::add_course_granted_log()
                        └─ PowerEmail triggers (course_granted)
```

### 學員上課 → 章節完成流程
```
學員造訪 pc_chapter 頁面
    └─ Templates/Templates.php (template_redirect hook)
        └─ do_action('power_course_before_classroom_render')
            └─ Chapter/Core/LifeCycle::register_visit_chapter()
                └─ do_action('power_course_visit_chapter', $chapter, $product)
                    ├─ LifeCycle::save_first_visit_time()   (首次記錄時間)
                    └─ LifeCycle::save_last_visit_info()    (記錄最後造訪)

學員點擊「完成章節」按鈕
    └─ POST /wp-json/power-course/toggle-finish-chapters/{id}
        └─ Chapter/Core/Api::post_toggle_finish_chapters_with_id_callback()
            ├─ AVLChapterMeta::add('finished_at', ...)
            ├─ CourseUtils::get_course_progress() → 計算進度
            └─ do_action('power_course_chapter_finished', ...)
                ├─ LifeCycle::add_chapter_finish_log()
                ├─ PowerEmail triggers (chapter_finish)
                └─ if progress == 100: do_action('power_course_course_finished', ...)
```

### 前端資料流
```
PHP renders HTML with #power_course selector
    └─ wp_localize_script() → power_course_data.env (encrypted)
        └─ main.tsx mounts React
            └─ EnvProvider → useEnv() decrypts env vars
                └─ BunnyProvider → provides Bunny Stream context
                    └─ App1 (refine.dev)
                        └─ dataProvider(API_URL + '/power-course')
                            └─ useList/useOne/useCreate/useUpdate/useDelete
                                └─ TanStack Query (staleTime: 10min)
```

---

## 自訂表格 Schema

### `{prefix}_pc_avl_coursemeta`
```sql
meta_id    BIGINT AUTO_INCREMENT PRIMARY KEY
post_id    BIGINT    -- course product ID
user_id    BIGINT    -- student user ID
meta_key   VARCHAR(255)
meta_value LONGTEXT
```
常用 meta_key: `expire_date`, `finished_at`, `last_visit_info`, `course_granted_at`

### `{prefix}_pc_avl_chaptermeta`
```sql
meta_id    BIGINT AUTO_INCREMENT PRIMARY KEY
post_id    BIGINT    -- pc_chapter post ID
user_id    BIGINT    -- student user ID
meta_key   VARCHAR(255)
meta_value LONGTEXT
```
常用 meta_key: `first_visit_at`, `finished_at`

### `{prefix}_pc_email_records`
```sql
id             BIGINT AUTO_INCREMENT PRIMARY KEY
post_id        BIGINT       -- course ID
user_id        BIGINT       -- student ID
email_id       BIGINT       -- email template ID
email_subject  VARCHAR(255)
trigger_at     VARCHAR(30)  -- AtHelper slug
mark_as_sent   TINYINT(1)   -- 0 or 1
identifier     VARCHAR(255) -- unique email send identifier
email_date     DATETIME
```

### `{prefix}_pc_student_logs`
```sql
id          BIGINT AUTO_INCREMENT PRIMARY KEY
user_id     BIGINT
course_id   BIGINT
chapter_id  BIGINT NULL
log_type    VARCHAR(20)   -- AtHelper slug
title       VARCHAR(255)
content     LONGTEXT
user_ip     VARCHAR(100)
created_at  DATETIME
```

---

## 關鍵 Post Meta 欄位

### 課程商品 (post_type: product)
```
_is_course                    'yes' | 'no'
course_schedule               int (timestamp, 0=立即開課)
course_hour / course_minute   int (手動設定課程時長)
teacher_ids                   多筆 user meta rows
limit_type                    'unlimited'|'fixed'|'assigned'|'follow_subscription'
limit_value                   int|null
limit_unit                    'timestamp'|'day'|'month'|'year'|null
feature_video                 array{type,id,meta}
trial_video                   array{type,id,meta}
qa_list                       array
bind_courses_data             array (哪些商品授權這課程)
editor                        'power-editor' | 'elementor'
is_free                       'yes'|'no'
is_popular                    'yes'|'no'
is_featured                   'yes'|'no'
show_join / show_review / ... 'yes'|'no' (各種顯示控制)
course_launch_action_done     'yes' (排程開課後設定)
```

### 銷售方案商品 (post_type: product)
```
bundle_type      string (非空即為銷售方案)
link_course_ids  int (連結課程 ID)
pbp_product_ids  多筆 rows (包含商品 IDs)
```

### 章節 (post_type: pc_chapter)
```
chapter_video    array{type,id,meta}
chapter_length   int (秒數)
parent_course_id int (根課程 ID)
editor           'power-editor' | 'elementor'
enable_comment   'yes'|'no'
```

### 用戶 Meta
```
avl_course_ids   多筆 user meta rows (每筆存一個 course_id)
```

---

## Compatibility 版本遷移機制

每次外掛更新後，`Compatibility::compatibility()` 透過 Action Scheduler 執行一次：

1. 比對 `pc_compatibility_action_scheduled` option 與 `Plugin::$version`
2. 若版本不同，排程執行 `pc_compatibility_action_scheduler` AS action
3. 執行遷移（建表、資料遷移、meta 設定）
4. 更新 `pc_compatibility_action_scheduled` 為當前版本

新增遷移時，在 `compatibility()` 方法中用 `version_compare()` 判斷是否需要執行。
