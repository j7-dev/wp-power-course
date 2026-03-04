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
- **Part of monorepo** `powerrepo` — sibling packages at `../powerhouse/` and `../../packages/antd-toolkit/`

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
const { API_URL, NONCE, KEBAB, CURRENT_USER_ID, BUNNY_LIBRARY_ID, AXIOS_INSTANCE } = useEnv()
```

### CSS
- Power Course has **no own CSS** — all styles live in Powerhouse
- Use DaisyUI utility classes: `.pc-btn`, `.pc-modal`, `.pc-badge`, `.pc-collapse`, etc.
- Tailwind classes with `tw-` prefix in templates
- Ant Design components for admin UI

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

---

## REST API Quick Reference

Base: `{site_url}/wp-json/power-course/`

```
GET    courses                    List courses
GET    courses/{id}               Get course + chapters
POST   courses                    Create course
POST   courses/{id}               Update course
DELETE courses / courses/{id}     Delete

POST   courses/add-students       Grant access
POST   courses/remove-students    Revoke access
POST   courses/update-students    Update expiry
GET    courses/student-logs       Activity logs

GET    chapters                   List chapters
POST   chapters                   Create chapter(s)
POST   chapters/sort              Reorder
POST   chapters/{id}              Update chapter
DELETE chapters / chapters/{id}   Delete
POST   toggle-finish-chapters/{id} Toggle completion
```

---

## Custom Database Tables

| Table | Purpose |
|-------|---------|
| `{prefix}_pc_avl_coursemeta` | User ↔ Course metadata (expire_date, finished_at, last_visit_info) |
| `{prefix}_pc_avl_chaptermeta` | User ↔ Chapter progress (first_visit_at, finished_at) |
| `{prefix}_pc_email_records` | Automated email send records |
| `{prefix}_pc_student_logs` | Student activity audit trail |

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
$settings->course_access_trigger    // 'completed' — order status for access grant
$settings->hide_myaccount_courses   // 'yes'|'no'
$settings->pc_watermark_qty         // int — 0 disables watermark
$settings->pc_watermark_text        // '{display_name} {post_title} IP:{ip}'
```

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
