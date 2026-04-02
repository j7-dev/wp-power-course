# Implementation Plan: Course Linear Viewing Mode (Issue #165)

## Overview

Implement a per-course "linear viewing" mode for the Power Course LMS plugin. When enabled by an admin, students must complete chapters sequentially (by flattened `menu_order`) -- each chapter unlocks only after the previous one is completed. The feature spans backend PHP (product meta storage, template interception, REST API guards) and frontend vanilla TS (sidebar lock icons, partial unlock after completion).

## Scope Mode: HOLD SCOPE

Well-defined feature with clear specifications. Estimated ~12 files affected.

## Requirements Recap

1. **Admin Setting**: Per-course toggle `enable_linear_mode` stored as WC Product Meta (`'yes'`/`'no'`, default `'no'`)
2. **Unlock Logic**: Chapter N is unlocked iff `(N is first) OR (N itself is finished) OR (N-1 is finished)`
3. **All chapters** (including parent chapters) participate in linear order via `ChapterUtils::get_flatten_post_ids()`
4. **Admin/Instructor bypass**: Users with `manage_woocommerce` capability or the course author skip all locks
5. **No undo finish**: In linear mode, `toggle-finish` only allows marking complete, not un-marking
6. **Dual interception**: PHP template layer (`single-pc_chapter.php`) + REST API layer (`toggle-finish`)
7. **JS partial unlock**: After completing a chapter, the frontend updates the sidebar locally without page reload
8. **Locked chapter page**: A dedicated locked template shows lock icon, message, and link to previous chapter

## Known Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Template cache (`get_children_posts_html`) could serve stale sidebar HTML with wrong lock/unlock icons | High | The sidebar already uses `get_children_posts_html_uncached()` in the classroom context; the cached version is used elsewhere -- verify no classroom path hits cache |
| `get_flatten_post_ids()` uses `wp_cache` -- if chapters are reordered between requests, stale order could cause wrong lock decisions | Medium | The cache is per-request (object cache), not persistent transient; reordering already invalidates via `sort_chapters`. Acceptable. |
| Race condition: Two tabs complete the same chapter simultaneously | Low | `AVLChapterMeta::add` is idempotent; second call is a no-op |
| JS partial unlock doesn't handle network failure gracefully | Medium | Add error handling in the `complete()` callback |

## Architecture Changes

### New Files

| File | Purpose | Agent |
|------|---------|-------|
| `inc/classes/Resources/Chapter/Utils/LinearAccess.php` | Pure static helper: `is_chapter_locked()`, `get_prev_required_chapter()`, `can_bypass_linear()` | wordpress-master |
| `inc/templates/components/icon/lock.php` | Lock SVG icon template (Heroicons style) | wordpress-master |
| `inc/templates/pages/classroom/locked.php` | Locked chapter placeholder page | wordpress-master |

### Modified Files

| File | Change | Agent |
|------|--------|-------|
| `inc/templates/single-pc_chapter.php` | Add linear lock check after existing access control | wordpress-master |
| `inc/classes/Resources/Chapter/Core/Api.php` | Modify `post_toggle_finish_chapters_with_id_callback`: add lock guard + prevent un-finish + return next_chapter info | wordpress-master |
| `inc/classes/Resources/Chapter/Utils/Utils.php` | Modify `get_chapter_icon_html()` for lock icon; modify `get_children_posts_html_uncached()` for `pc-chapter-locked` CSS class | wordpress-master |
| `inc/templates/pages/classroom/header.php` | Hide "mark as unfinished" button when linear mode enabled and chapter is finished | wordpress-master |
| `inc/classes/Api/Course.php` | Add `enable_linear_mode` to `format_course_records()` response | wordpress-master |
| `inc/assets/src/events/finishChapter.ts` | Handle `next_chapter_id`, `next_chapter_unlocked`, `next_chapter_icon_html` in response; update sidebar lock states | wordpress-master |
| `inc/assets/src/store.ts` | Add `next_chapter_id`, `next_chapter_unlocked`, `next_chapter_icon_html` to `finishChapterAtom` | wordpress-master |
| `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx` | Add `enable_linear_mode` FiSwitch toggle | react-master |
| `inc/templates/pages/classroom/chapters.php` | Prevent navigation to locked chapters in sidebar JS | wordpress-master |

## Data Flow Analysis

### Toggle-Finish with Linear Mode

```
STUDENT CLICKS "MARK COMPLETE"
       |
       v
finishChapter.ts --POST--> toggle-finish-chapters/{id}
       |                           |
       |                    +------+------+
       |                    v             v
       |              [Linear ON?]   [Linear OFF?]
       |                    |             |
       |              +-----+-----+       +--> Original logic (no change)
       |              v           v
       |        [Chapter      [Already
       |         locked?]      finished?]
       |           |              |
       |        403 STOP     [Trying to
       |                      un-finish?]
       |                         |
       |                    403 STOP
       |
       |              [Chapter unlocked + marking complete]
       |                         |
       |                    +----+----+
       |                    v         v
       |              [Success]   [Failure]
       |                    |         |
       |                    v         +--> 400 + error message
       |              Calculate next_chapter:
       |              - next_chapter_id
       |              - next_chapter_unlocked
       |              - next_chapter_icon_html
       |                    |
       |                    v
       |              200 + extended data
       |
       <--------------------+
       |
finishChapter.ts handles response:
  1. Update current chapter icon -> check (complete)
  2. If next_chapter_unlocked:
     - Update next chapter icon -> video (unlocked)
     - Remove pc-chapter-locked class from next chapter <li>
     - Re-enable click navigation on next chapter
  3. Show dialog: "Completed! Next chapter unlocked"
```

### Template-Layer Lock Check

```
BROWSER --GET--> /classroom/{chapter-slug}
                      |
                 single-pc_chapter.php
                      |
               +------+------+
               v              v
         [Not logged in]  [Logged in]
              |               |
           redirect       +---+---+
           to login       v       v
                     [No access] [Has access]
                         |          |
                     show 404/buy   |
                                +---+----+
                                v        v
                          [Expired]  [Active]
                              |         |
                          show 404/   +--+--+
                          expired     v     v
                                [Admin/  [Student]
                                Author]     |
                                   |    +---+----+
                                   |    v        v
                                   | [Linear  [Linear
                                   |  OFF]     ON]
                                   |    |       |
                                   |    |   +---+---+
                                   |    |   v       v
                                   |    | [Unlocked] [Locked]
                                   |    |    |         |
                                   v    v    v         v
                              Render classroom    Render locked.php
```

## Error Handling Registry

| Method/Path | Possible Failure | Error Type | Handling | User Visible? |
|------------|-----------------|------------|----------|---------------|
| `toggle-finish` + locked chapter | Student tries to complete a locked chapter | 403 | Return: "此章節尚未解鎖，請先完成前面的章節" | Yes |
| `toggle-finish` + un-finish in linear mode | Student tries to un-mark a finished chapter | 403 | Return: "線性觀看模式下無法取消已完成的章節" | Yes |
| `toggle-finish` + course not found | `wc_get_product` returns false | 400 | Existing: "找不到課程" | Yes |
| `LinearAccess::is_chapter_locked` + no course_id | Chapter has no parent course | Graceful | Return false (not locked) | No |
| Template + locked chapter | Student visits locked chapter URL | Page render | Show `locked.php` template | Yes |

## Failure Mode Registry

| Code Path | Failure Mode | Handled? | Has Test? | User Visible? | Recovery Path |
|-----------|-------------|----------|-----------|---------------|---------------|
| `LinearAccess::is_chapter_locked()` with deleted prev chapter | prev chapter in flat order was deleted | Plan | Plan E2E | No (graceful: unlock) | Fallback: treat as unlocked |
| JS partial unlock on slow network | AJAX completes but DOM update fails | Plan | Plan E2E | No (sidebar stale) | Page refresh |
| Mid-session linear mode toggle | Admin enables while student is viewing | Plan | Plan E2E | Yes | Student refreshes |

## Implementation Steps

### Phase 1: Core Backend Logic (Foundation)

#### Step 1.1: Create `LinearAccess` utility class

**File**: `inc/classes/Resources/Chapter/Utils/LinearAccess.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: Create a new `final class LinearAccess` with four static methods:

- `is_linear_mode_enabled(int $course_id): bool` -- reads product meta `enable_linear_mode`, returns true if `'yes'`
- `can_bypass_linear(int $course_id, ?int $user_id = null): bool` -- returns true if user has `manage_woocommerce` OR is course author
- `is_chapter_locked(int $chapter_id, ?int $user_id = null, ?int $course_id = null): bool` -- core formula: locked = linear_mode_enabled AND NOT can_bypass AND NOT (is_first OR self_finished OR prev_finished)
- `get_prev_required_chapter_id(int $chapter_id, int $course_id): ?int` -- returns previous chapter_id in flatten order

Reason: Centralizes all linear access logic in one testable location.
Dependency: None
Risk: Low

#### Step 1.2: Create lock icon template

**File**: `inc/templates/components/icon/lock.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: Create lock SVG icon following `icon/video.php` pattern. Accept `$args` with `class` and `color`.
Dependency: None
Risk: Low

#### Step 1.3: Create locked chapter template

**File**: `inc/templates/pages/classroom/locked.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: Template renders: large lock icon, heading "此章節尚未解鎖", description with prev chapter title, and "前往上一章節" button. Does NOT load video/content.
Receives: `chapter` (WP_Post), `prev_chapter` (WP_Post), `product` (WC_Product).
Dependency: Step 1.2
Risk: Low

### Phase 2: Backend Integration (Template + API Guards)

#### Step 2.1: Add linear lock check to `single-pc_chapter.php`

**File**: `inc/templates/single-pc_chapter.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: After existing access control (line 64), add linear lock check. When locked, conditionally load `classroom/locked` instead of `classroom/body` at line 87. The locked page still has sidebar and header for navigation.

Dependency: Steps 1.1, 1.3
Risk: Medium

#### Step 2.2: Modify `toggle-finish` API for linear mode guards

**File**: `inc/classes/Resources/Chapter/Core/Api.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: In `post_toggle_finish_chapters_with_id_callback()`:
1. After product check (line 286): if chapter is locked, return 403
2. Before unfinish logic (line 291): if linear mode enabled and trying to unfinish as non-admin, return 403
3. After successful mark-complete: add `next_chapter_id`, `next_chapter_unlocked`, `next_chapter_icon_html` to response

Dependency: Step 1.1
Risk: Medium

#### Step 2.3: Modify sidebar chapter icons for lock state

**File**: `inc/classes/Resources/Chapter/Utils/Utils.php`
**Agent**: `@wp-workflows:wordpress-master`

Action:
a) `get_chapter_icon_html()`: Add lock icon check at beginning
b) `get_children_posts_html_uncached()`: Add `pc-chapter-locked` CSS class to locked chapters' `<li>`

Dependency: Steps 1.1, 1.2
Risk: Low

#### Step 2.4: Modify header to hide un-finish button in linear mode

**File**: `inc/templates/pages/classroom/header.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: When linear mode enabled and chapter is finished, render button as disabled "已完成" indicator. Add `data-linear-mode="yes"` attribute.

Dependency: Step 1.1
Risk: Low

### Phase 3: Frontend Changes (Admin + Classroom)

#### Step 3.1: Add `enable_linear_mode` to Course API response

**File**: `inc/classes/Api/Course.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: Add `'enable_linear_mode' => (string) $product->get_meta('enable_linear_mode') ?: 'no'` to `format_course_records()` `$extra_array`.

Dependency: None
Risk: Low

#### Step 3.2: Add linear mode toggle in admin course settings

**File**: `js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx`
**Agent**: `@wp-workflows:react-master`

Action: Add `<Heading>學習模式</Heading>` section with `FiSwitch` for `enable_linear_mode`. Place before "發佈時間" section.

Dependency: Step 3.1
Risk: Low

#### Step 3.3: Modify `finishChapter.ts` for linear mode partial unlock

**File**: `inc/assets/src/events/finishChapter.ts`
**Agent**: `@wp-workflows:wordpress-master`

Action:
1. Extract `next_chapter_id`, `next_chapter_unlocked`, `next_chapter_icon_html` from response
2. In success handler: update next chapter's icon and remove `pc-chapter-locked` class
3. Update dialog message for linear mode unlock notification

Dependency: Step 2.2
Risk: Medium

#### Step 3.4: Update `store.ts` atom

**File**: `inc/assets/src/store.ts`
**Agent**: `@wp-workflows:wordpress-master`

Action: Add `next_chapter_id`, `next_chapter_unlocked`, `next_chapter_icon_html` to `finishChapterAtom`.

Dependency: None
Risk: Low

#### Step 3.5: Prevent navigation to locked chapters in sidebar JS

**File**: `inc/templates/pages/classroom/chapters.php`
**Agent**: `@wp-workflows:wordpress-master`

Action: In click handler, check `$li.hasClass('pc-chapter-locked')` and return early.

Dependency: Step 2.3
Risk: Low

## Test Strategy

### Integration Tests (PHP)

- `LinearAccess::is_chapter_locked()` scenarios:
  - First chapter always unlocked
  - Chapter with prev completed = unlocked
  - Chapter with prev not completed = locked
  - Already completed chapter = unlocked
  - Admin/author always unlocked
  - Non-linear course = all unlocked
- `toggle-finish` API:
  - Locked chapter returns 403
  - Un-finish in linear mode returns 403
  - Successful finish returns next_chapter fields
  - Last chapter finish returns null next_chapter_id

### E2E Tests (Playwright)

- Admin enables linear mode on a course
- Student sees first chapter unlocked, rest locked
- Student completes first chapter, second unlocks (no reload)
- Student accesses locked chapter via URL, sees locked page
- Student completes all chapters sequentially
- Admin disables linear mode, all chapters accessible
- Admin can view locked chapters without restriction

### Test Execution Commands

```bash
composer run test
pnpm run test:e2e:admin
pnpm run test:e2e:frontend
pnpm run test:e2e:integration
```

## Success Criteria

- [ ] Admin can enable/disable linear mode per course via React admin UI
- [ ] `enable_linear_mode` persisted as WC Product Meta and returned via Course API
- [ ] Students see lock icons on locked chapters in sidebar
- [ ] Students cannot navigate to locked chapters (sidebar click or direct URL)
- [ ] Locked chapter URL shows friendly lock page with prev chapter guidance
- [ ] Completing a chapter unlocks the next one immediately (no page reload)
- [ ] Students cannot un-mark completed chapters in linear mode
- [ ] Admins and course authors bypass all locks
- [ ] Disabling linear mode immediately unlocks all chapters
- [ ] All existing non-linear course behavior unchanged
- [ ] PHP passes `pnpm run lint:php` (PHPCS + PHPStan level 9)
- [ ] TypeScript passes `pnpm run lint:ts`

## Constraints

- No "free preview" exemption (deferred per Q7)
- No progress percentage gate -- only binary complete/not-complete
- No email notification when chapters unlock
- No bulk linear mode toggle for multiple courses
- No per-student "skip chapter" admin action
- Lock state is computed on-the-fly, not stored persistently
