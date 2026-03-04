# wp-power-course Development Patterns

> Auto-generated skill from repository analysis

## Overview

This skill covers development patterns for wp-power-course, a WordPress plugin built with TypeScript and Vite. The codebase follows a hybrid PHP/TypeScript architecture with React admin components, RESTful APIs, and template-based frontend rendering. The project uses conventional commits and maintains a structured workflow for releases, documentation updates, and feature development.

## Coding Conventions

### File Naming
- **TypeScript/JavaScript**: Use camelCase for files
  ```
  courseTypes.ts
  productAdmin.tsx
  apiHelpers.js
  ```

### Import/Export Style
- **Imports**: Use relative paths
  ```typescript
  import { CourseType } from '../types/index'
  import ProductAdmin from './components/ProductAdmin'
  ```
- **Exports**: Use named exports
  ```typescript
  export const CourseApi = {
    // implementation
  }
  export { ProductAdmin, CourseList }
  ```

### Commit Messages
- Use conventional commit format with prefixes: `chore`, `fix`, `feat`, `refactor`
- Keep messages concise (~27 characters average)
- Examples:
  ```
  feat: add course filtering
  fix: product admin validation
  chore: release v1.2.3
  refactor: cleanup api calls
  ```

## Workflows

### Version Release
**Trigger:** When ready to release a new plugin version  
**Command:** `/release`

1. Update version number in `package.json`
2. Update version number in `plugin.php` header
3. Commit changes with message format: `chore: release vX.X.X`
4. Tag the release if needed

**Example:**
```json
// package.json
{
  "version": "1.2.3"
}
```
```php
// plugin.php
/**
 * Version: 1.2.3
 */
```

### Documentation Update
**Trigger:** When project documentation needs updating  
**Command:** `/update-docs`

1. Update `.claude/instructions.md` or `.claude/architecture.md` with development guidelines
2. Sync changes to `.github/copilot-instructions.md` for GitHub Copilot
3. Update `README.md` if user-facing changes exist
4. Commit with `docs:` or `chore:` prefix

**Files involved:**
```
.claude/instructions.md
.claude/architecture.md
.github/copilot-instructions.md
README.md
```

### Product Admin Feature
**Trigger:** When adding or modifying product-related admin functionality  
**Command:** `/product-feature`

1. Update PHP API class (`inc/classes/Admin/Product.php` or `inc/classes/Api/Course.php`)
2. Update TypeScript types in `js/src/pages/admin/Courses/List/types/index.ts`
3. Update React admin components in `js/src/pages/admin/Courses/Edit/**`
4. Test admin interface functionality

**Example flow:**
```php
// inc/classes/Api/Course.php
public function update_course_meta($course_id, $meta_data) {
    // API implementation
}
```
```typescript
// types/index.ts
export interface CourseMetaData {
    title: string;
    description: string;
    // additional fields
}
```

### Template Component Update
**Trigger:** When modifying frontend template display  
**Command:** `/update-template`

1. Modify template files in `inc/templates/components/`
2. Update related template pages in `inc/templates/pages/` if needed
3. Test frontend rendering
4. Commit with `feat:` or `fix:` prefix

**Structure:**
```
inc/templates/
├── components/
│   ├── courseCard.php
│   └── productGrid.php
└── pages/
    ├── courseSingle.php
    └── courseArchive.php
```

### Dependency Update
**Trigger:** When PHP dependencies need updating  
**Command:** `/update-deps`

1. Run `composer update` to update dependencies
2. Review `composer.lock` changes
3. Test for any breaking changes
4. Commit updated `composer.lock` file with `chore:` prefix

## Testing Patterns

- Test files follow the pattern: `*.test.*`
- Testing framework not explicitly configured (likely Jest/PHPUnit based on structure)
- Example test file naming:
  ```
  courseApi.test.ts
  ProductAdmin.test.tsx
  template.test.php
  ```

## Commands

| Command | Purpose |
|---------|---------|
| `/release` | Release a new plugin version by updating package.json and plugin.php |
| `/update-docs` | Update project documentation across .claude, .github, and README files |
| `/product-feature` | Add/modify product admin features (PHP API + React components) |
| `/update-template` | Modify frontend template components and pages |
| `/update-deps` | Update PHP dependencies via composer |