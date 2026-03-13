# Power Course — Copilot Instructions

> This file is intended for `.github/copilot-instructions.md`.
> **To activate GitHub Copilot context**: move or copy this file to `.github/copilot-instructions.md`
>
> **Last Updated:** 2025-01-31 | **Version:** 0.11.23

---

## Project Identity

**Power Course** (`power-course`) is a WordPress LMS plugin integrating WooCommerce for course selling.

- **PHP Namespace:** `J7\PowerCourse\` → `inc/classes/` and `inc/src/`
- **React Entry:** `js/src/main.tsx` → builds to `js/dist/`
- **Text Domain:** `power-course`
- **Required Plugins:** WooCommerce ≥ 7.6.0, Powerhouse ≥ 3.3.41
- **Depends on** sibling plugin `../powerhouse/`

---

## PHP Conventions

### File & Class Rules
- Every PHP file: `declare(strict_types=1);` at the top
- Namespace: `J7\PowerCourse\{Subdomain}\{ClassName}`
- All service classes use `\J7\WpUtils\Traits\SingletonTrait` — call via `MyClass::instance()`, never `new MyClass()`
- Hooks (`add_action`, `add_filter`) always go in `__construct()`
- Every class needs a one-line Traditional Chinese comment describing its purpose
- Every property: `/** @var string 屬性說明 */`
- Every method: Traditional Chinese docblock describing purpose + PHPDoc type hints

### API Classes
All REST API classes extend `J7\WpUtils\Classes\ApiBase`:
```php
final class MyApi extends ApiBase {
    use \J7\WpUtils\Traits\SingletonTrait;
    protected $namespace = 'power-course';
    protected $apis = [
        ['endpoint' => 'resource',              'method' => 'get'],
        ['endpoint' => 'resource/(?P<id>\d+)', 'method' => 'post'],
    ];
    // callback naming: {method}_{endpoint_snake}_callback()
    public function get_resource_callback($request) { ... }
    public function post_resource_with_id_callback($request) { ... }
}
```

### Database Operations
- Custom tables use `AbstractMetaCRUD` static methods (not raw SQL)
- Multi-step writes use `$wpdb->query('START TRANSACTION')` / `COMMIT` / `ROLLBACK`
- Array meta fields (e.g., `teacher_ids`) use **multiple meta rows**: `delete_meta_data()` then `add_meta_data()` in a loop

### MetaCRUD Usage
```php
// Course meta (pc_avl_coursemeta)
AVLCourseMeta::update($course_id, $user_id, 'expire_date', $timestamp);
AVLCourseMeta::get($course_id, $user_id, 'expire_date', true); // single=true

// Chapter meta (pc_avl_chaptermeta)
AVLChapterMeta::add($chapter_id, $user_id, 'finished_at', wp_date('Y-m-d H:i:s'));
AVLChapterMeta::delete($chapter_id, $user_id, 'finished_at');
```

### Sanitization
```php
$params = WP::sanitize_text_field_deep($params, false); // sanitize all
$params = WP::sanitize_text_field_deep($params, true, ['description']); // skip keys
[
    'data'      => $data,
    'meta_data' => $meta_data,
] = WP::separator($body_params, 'product', $files);
```

### Logging & Errors
```php
Plugin::logger('message', 'debug'|'info'|'warning'|'critical', $context);
// Domain errors: throw new \Exception('message')
// REST errors:   return new \WP_Error('code', 'message', $status_code)
```

### Background Tasks
Use Action Scheduler (not WP-Cron):
```php
\as_schedule_recurring_action(time(), INTERVAL, 'hook_name');
\as_enqueue_async_action('hook_name', $args);
```

---

## Frontend Conventions

### TypeScript / React
- Path alias: `@/` → `js/src/`
- Strict TypeScript — avoid `any`; prefer `zod/v4` for runtime validation
- Formatting: tabs, single quotes, no semicolons (ESLint + Prettier)
- All API calls via custom hooks in `hooks/` directory
- Define types for all API responses

### Refine.dev Patterns
```tsx
// Data fetching
const { data, isLoading } = useList({ resource: 'courses', dataProviderName: 'power-course' })
const { data } = useOne({ resource: 'courses', id, dataProviderName: 'power-course' })
const { mutate: createCourse } = useCreate({ resource: 'courses', dataProviderName: 'power-course' })
const { mutate: updateCourse } = useUpdate({ resource: 'courses', dataProviderName: 'power-course' })
```

### Environment Variables
Always use the `useEnv()` hook — never access `window.power_course_data` directly:
```tsx
const {
  API_URL, SITE_URL, NONCE, KEBAB, SNAKE,
  CURRENT_USER_ID, CURRENT_POST_ID, PERMALINK,
  BUNNY_LIBRARY_ID, BUNNY_CDN_HOSTNAME, BUNNY_STREAM_API_KEY,
  APP1_SELECTOR, APP2_SELECTOR, ELEMENTOR_ENABLED,
  COURSE_PERMALINK_STRUCTURE, AXIOS_INSTANCE
} = useEnv()
```

### CSS
- Power Course has **no own CSS** — all styles live in Powerhouse
- Use DaisyUI utility classes: `.pc-btn`, `.pc-modal`, `.pc-badge`, `.pc-collapse`, etc.
- Tailwind classes with `tw-` prefix in templates
- Ant Design components for admin UI

### Data Providers
```tsx
// Available dataProviderName values:
'default'       // /wp-json/v2/powerhouse
'power-email'   // /wp-json/power-email
'power-course'  // /wp-json/power-course  ← main
'wc-analytics'  // /wp-json/wc-analytics
'wp-rest'       // /wp-json/wp/v2
'wc-rest'       // /wp-json/wc/v3
'wc-store'      // /wp-json/wc/store/v1
'bunny-stream'  // BunnyProvider (Bunny Stream CDN)
```

### Admin Page Routes
| Route | Description |
|-------|-------------|
| `/courses` | Course list (ProTable) |
| `/courses/edit/:id` | Course edit — tabs: price / chapters / students / bundles / description / Q&A / analytics / other |
| `/teachers` | Teacher management |
| `/students` | Student management + CSV export |
| `/products` | Course-product binding |
| `/emails` | Email template list |
| `/emails/edit/:id` | Drag-and-drop email editor |
| `/settings` | Plugin settings |
| `/analytics` | Revenue analytics |
| `/media-library` | WordPress media library |
| `/bunny-media-library` | Bunny CDN media library |

---

## Core Domain Concepts

### Course
A course is a WooCommerce product with `_is_course = 'yes'` meta. Key checks:
```php
CourseUtils::is_course_product($product)      // Is it a course?
CourseUtils::is_avl($course_id, $user_id)     // Does user have access?
CourseUtils::is_course_ready($product)         // Has it launched?
CourseUtils::is_expired($product, $user_id)   // Has access expired?
CourseUtils::get_course_progress($product, $user_id) // float 0–100
```

### Chapter (`pc_chapter` CPT)
- Hierarchical: top-level chapters under a course product, sub-chapters under chapters
- Rewrite slug: `classroom`
- CPT UI hidden in production (`Plugin::$is_local` must be true to show in WP admin)
- `post_parent` = course product ID (top-level) or parent chapter ID (sub-chapters)
- `parent_course_id` post meta = root course ID (usable at any nesting depth)
- Sorted by `menu_order` ASC

### Granting Access
**ALWAYS** fire the action hook — never call the function directly:
```php
do_action(
    \J7\PowerCourse\Resources\Course\LifeCycle::ADD_STUDENT_TO_COURSE_ACTION,
    $user_id,   // int
    $course_id, // int
    $expire_date, // 0=unlimited | timestamp | 'subscription_123'
    $order      // \WC_Order|null
);
```

### Expire Date Types
```php
0                  // Unlimited access
1735689600         // Specific timestamp
'subscription_123' // Follows WC Subscription #123
```

### Bundle Product (銷售方案)
A product with `bundle_type` meta — links to a course via `link_course_ids`. When purchased, grants course access. **Cannot nest bundle products inside other bundles.**

```php
$helper = Helper::instance($product_id); // J7\PowerCourse\BundleProduct\Helper
$helper->is_bundle_product;              // bool
$helper->get_product_ids();              // included WC product IDs
Helper::get_bundle_products($course_id, true); // bundle product IDs for course
```

### Limit (Access Duration)
```php
$limit = Limit::instance($product); // J7\PowerCourse\Resources\Course\Limit
// limit_type: 'unlimited' | 'fixed' | 'assigned' | 'follow_subscription'
// limit_unit: 'timestamp' | 'day' | 'month' | 'year' | null
$expire_date = $limit->calc_expire_date($order); // int timestamp | 'subscription_{id}'
```

---

## REST API Quick Reference

Base: `{site_url}/wp-json/power-course/`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `courses` | List courses (paginated, filtered) |
| GET | `courses/{id}` | Get course + chapters |
| POST | `courses` | Create course |
| POST | `courses/{id}` | Update course |
| DELETE | `courses` / `courses/{id}` | Delete |
| GET | `courses/terms` | Course categories/tags |
| GET | `courses/options` | Filter options |
| GET | `courses/student-logs` | Activity logs |
| POST | `courses/add-students` | Grant access |
| POST | `courses/remove-students` | Revoke access |
| POST | `courses/update-students` | Update expiry |
| GET/POST/DELETE | `chapters` | Chapter CRUD |
| POST | `chapters/sort` | Reorder chapters |
| POST | `chapters/{id}` | Update chapter |
| POST | `toggle-finish-chapters/{id}` | Toggle completion |
| GET/POST | `users` | User management |
| POST | `upload` | File upload |
| GET/POST | `options` | Plugin options |
| GET | `reports/revenue` | Revenue analytics |

---

## Custom Database Tables

| Table | Purpose |
|-------|---------|
| `{prefix}_pc_avl_coursemeta` | User ↔ Course metadata (expire_date, finished_at, last_visit_info) |
| `{prefix}_pc_avl_chaptermeta` | User ↔ Chapter progress (first_visit_at, finished_at) |
| `{prefix}_pc_email_records` | Automated email send records |
| `{prefix}_pc_student_logs` | Student activity audit trail |

Access via Plugin constants: `Plugin::COURSE_TABLE_NAME`, `Plugin::CHAPTER_TABLE_NAME`, `Plugin::EMAIL_RECORDS_TABLE_NAME`, `Plugin::STUDENT_LOGS_TABLE_NAME`

---

## Key WordPress Actions

```php
// Course access
'power_course_add_student_to_course'           // ($user_id, $course_id, $expire_date, $order)
'power_course_after_add_student_to_course'     // same params — fires after access granted
'power_course_after_remove_student_from_course' // ($user_id, $course_id)
'power_course_after_update_student_from_course' // ($user_id, $course_id, $timestamp)

// Course state
'power_course_course_launch'    // ($user_id, $course_id) — schedule reached
'power_course_course_finished'  // ($course_id, $user_id) — 100% progress
'power_course_before_update_product_meta' // ($product, $meta_data) — before save

// Chapter
'power_course_visit_chapter'       // ($chapter_post, $course_product)
'power_course_chapter_finished'    // ($chapter_id, $course_id, $user_id)
'power_course_chapter_unfinished'  // ($chapter_id, $course_id, $user_id)
'power_course_before_classroom_render' // ()

// Cron (every 10 minutes)
'power_course_schedule_action'
```

---

## Plugin Settings (`power_course_settings` option)

```php
$settings = \J7\PowerCourse\Resources\Settings\Model\Settings::instance();
```

| Property | Default | Description |
|----------|---------|-------------|
| `course_access_trigger` | `'completed'` | Order status that grants course access |
| `hide_myaccount_courses` | `'no'` | Hide courses tab in WC My Account |
| `fix_video_and_tabs_mobile` | `'no'` | Fix video/tabs sticky on mobile |
| `pc_header_offset` | `'0'` | Sticky header offset (px) |
| `hide_courses_in_main_query` | `'no'` | Exclude courses from main WP query |
| `hide_courses_in_search_result` | `'no'` | Exclude courses from search results |
| `pc_watermark_qty` | `0` | Video watermark count (0 = disabled) |
| `pc_watermark_text` | `'{display_name} {post_title} IP:{ip}'` | Watermark template |
| `pc_pdf_watermark_qty` | `0` | PDF watermark count |

**Bunny settings** (library_id, cdn_hostname, stream_api_key) → stored in Powerhouse `powerhouse_settings`, accessed via `\J7\Powerhouse\Settings\Model\Settings::instance()`.

---

## Development Commands

```bash
pnpm run dev          # Vite dev server (port 5174)
pnpm run build        # Production build
pnpm run lint:php     # phpcbf + phpcs + phpstan
pnpm run lint:ts      # ESLint TypeScript
pnpm run format       # Prettier-ESLint
pnpm run release      # Bump patch + build + tag + push
pnpm run sync:version # Sync version package.json → plugin.php
```

---

## Video Player Types

| `chapter_video.type` | Player | Notes |
|----------------------|--------|-------|
| `bunny` | VidStack + HLS.js | Bunny Stream CDN (HLS) |
| `youtube` | iframe | YouTube embed |
| `vimeo` | iframe | Vimeo embed |
| `code` | raw HTML | Custom embed code |
| `none` | — | No video |

---

## Email Automation (PowerEmail)

REST namespace: `power-email`. Email triggers via `AtHelper` constants:

| Constant | Slug | Trigger |
|----------|------|---------|
| `AtHelper::COURSE_GRANTED` | `course_granted` | After student gains course access |
| `AtHelper::COURSE_FINISHED` | `course_finish` | When course reaches 100% progress |
| `AtHelper::COURSE_LAUNCHED` | `course_launch` | When course launch schedule fires |
| `AtHelper::CHAPTER_ENTERED` | `chapter_enter` | First time entering a chapter |
| `AtHelper::CHAPTER_FINISHED` | `chapter_finish` | After completing a chapter |

---

## PHP Code Quality

### Library Preference Order
1. Reuse patterns from existing project code first — avoid reinventing the wheel
2. WordPress / WooCommerce built-in functions
3. Powerhouse plugin (`J7\Powerhouse\*`)
4. `J7\WpUtils` library

### Rules
- All PHP files: `declare(strict_types=1);` — no exceptions
- Run `pnpm run lint:php` before committing (phpcbf + phpcs + phpstan); auto-fix errors
- Use Action Scheduler for background tasks, never WP-Cron
- Multi-step DB writes: use transactions (`START TRANSACTION` / `COMMIT` / `ROLLBACK`)
- Array meta (e.g., `teacher_ids`): `delete_meta_data()` first, then `add_meta_data()` in loop — never `update_post_meta()` with an array
- Guest checkout is dynamically disabled when cart contains a course product
- `pc_chapter` CPT admin UI: only shown when `Plugin::$is_local === true`
- Version migration: write in `Compatibility\Compatibility::compatibility()` using `version_compare()`
- **Always grant access via action hook**, never call the method directly:
  ```php
  do_action(LifeCycle::ADD_STUDENT_TO_COURSE_ACTION, $user_id, $course_id, $expire_date, $order);
  ```

---

## Frontend Code Quality

### Library Preference Order
1. Reuse patterns from existing project code first
2. `antd`, `@ant-design/pro-components`
3. `antd-toolkit`

### Rules
- Strict TypeScript — no `any`; use `zod/v4` for runtime validation
- ESLint + Prettier: **tabs, single quotes, no semicolons**
- Run `pnpm run lint:ts` before committing; auto-fix errors
- All API calls via custom hooks in `hooks/` directory
- **Power Course has no own CSS** — all SCSS/Tailwind lives in Powerhouse
- Access env via `useEnv()` hook — never `window.power_course_data` directly

---

## Error Handling & Testing

- No automated tests — manual testing only for now
- Log via `Plugin::logger($message, $level, $context)` (WooCommerce Logger)
- REST API validation: return `\WP_Error`; domain errors: throw `\Exception`

---

## Task Execution Rules

- Before any modification, confirm you are NOT on the `master` branch; if so, ask whether to create a new branch
- Task plans are stored in `.claude/tasks/` — read the plan before starting
- All plans must be reviewed and approved before implementation; each step requires review
- If you spot messy, overly complex, low-performance, or low-readability code, surface it but fix separately

> **Architecture reference**: see `.github/instructions/architecture.instructions.md`
