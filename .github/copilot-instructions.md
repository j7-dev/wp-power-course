# GitHub Copilot Instructions for Power Course

## Project Overview

Power Course is a WordPress LMS (Learning Management System) plugin with a modern architecture using frontend-backend separation. It's a **submodule within the Powerrepo monorepo** and depends on the Powerhouse plugin.

**Tech Stack:**
- **Frontend**: React + TypeScript + Ant Design + Vite (port 5174)
- **Backend**: PHP 8.0+ + WordPress + WooCommerce + Powerhouse plugin
- **Package Managers**: pnpm (frontend), Composer (backend)
- **Dependencies**: J7\WpUtils library, ActionScheduler, antd-toolkit

## Essential Build & Validation Commands

**Always run commands from the project root directory.**

### Environment Setup
```bash
# CRITICAL: Always run bootstrap first after cloning
pnpm run bootstrap          # composer install --no-interaction
```

### Development Commands
```bash
pnpm run dev               # Start dev server (port 5174)
pnpm run build            # Production build  
pnpm run build:wp         # WordPress-specific build config
```

### Code Quality & Validation
```bash
# ALWAYS run these before committing changes:
pnpm run lint:php         # PHP: phpcbf + phpcs + phpstan (REQUIRED)
pnpm run lint:ts          # TypeScript ESLint checks (REQUIRED)
pnpm run format           # Prettier formatting for TS/React

# Individual PHP checks:
composer run phpstan      # PHP static analysis only
```

**WARNING**: Both `lint:php` and `lint:ts` must pass without errors before any commit.

### Release Commands
```bash
pnpm run release          # Patch version release
pnpm run release:minor    # Minor version release
pnpm run release:major    # Major version release
pnpm run zip             # Create plugin ZIP package
```

## Critical Code Standards

### PHP Requirements (Strictly Enforced)
- **MANDATORY**: All PHP files must start with `declare(strict_types=1);`
- **Namespace**: All classes use `J7\PowerCourse` PSR-4 autoloading
- **Comments**: All classes and functions require **Traditional Chinese comments**
- **Type annotations**: Use PHPStan type annotations for all parameters/returns
- **Inheritance**: Database operations must extend `AbstractTable.php`
- **Dependencies priority**: 
  1. Existing project code patterns
  2. WordPress/WooCommerce functions
  3. Powerhouse plugin & J7\WpUtils utilities
  4. Custom implementations (last resort)

### TypeScript Requirements
- **Strict mode**: Avoid `any` types - use proper TypeScript typing
- **Path aliases**: Use `@/` for `js/src/` imports
- **API types**: All API responses must have defined TypeScript interfaces
- **Validation**: Prefer Zod schemas for runtime type validation
- **Hooks**: Use custom hooks in `hooks/` directory for API calls

### Code Style
- **PHP**: WordPress Coding Standards, tabs, PSR-4
- **TypeScript**: ESLint + Prettier, tabs, single quotes, no semicolons

## Project Architecture

### Directory Structure
```
/inc/classes/           # PHP backend (PSR-4: J7\PowerCourse)
├── AbstractTable.php   # Base class for DB operations
├── Api/               # REST API endpoints
├── Resources/         # Core business logic
├── Admin/             # WordPress admin interface
├── PowerEmail/        # Email system
└── Utils/             # Utility classes

/js/src/               # React frontend
├── components/        # Reusable React components
├── pages/             # Page-level components
├── hooks/             # Custom React hooks for API
├── types/             # TypeScript type definitions
└── utils/             # Frontend utilities

/inc/templates/        # PHP template files
/release/              # Release automation scripts
```

### Key Configuration Files
- `phpcs.xml` - PHP CodeSniffer rules (WordPress standards)
- `phpstan.neon` - PHP static analysis config (level 9)
- `.eslintrc.cjs` - TypeScript linting (extends @power/eslint-config)
- `vite.config.ts` - Frontend build (dev server)
- `vite.config-for-wp.ts` - WordPress-specific build
- `composer.json` - PHP dependencies
- `package.json` - Node.js dependencies

### Monorepo Dependencies
This project relies on workspace packages:
- `@power/eslint-config` - Shared ESLint rules
- `@power/tailwind-config` - Tailwind CSS config
- `@power/typescript-config` - TypeScript config
- `antd-toolkit` - Custom Ant Design components

**Access parent monorepo**: `cd ../../` from project root.

## Common Validation Pitfalls

### Build Failures
1. **Missing bootstrap**: Always run `pnpm run bootstrap` first
2. **Network issues**: Composer requires internet for wpackagist.org
3. **Node version**: Requires Node.js compatible with pnpm 10.14.0+
4. **PHP version**: Requires PHP 8.0+ for strict typing

### PHP Linting Failures
1. **Missing strict types**: Add `declare(strict_types=1);` to all PHP files
2. **Type annotations**: Use PHPStan comments like `/** @var string $variable */`
3. **Chinese comments**: All classes/functions need Traditional Chinese descriptions
4. **WordPress standards**: Follow phpcs.xml rules - many exclusions already configured

### TypeScript Failures
1. **Any types**: Replace with proper interfaces/types
2. **Missing imports**: Use `@/` alias for internal imports
3. **API types**: Define interfaces for all API responses in `types/`

## Dependencies & External Systems

### WordPress Plugin Dependencies
- **WooCommerce**: Required for e-commerce functionality
- **Powerhouse**: Core dependency (`../powerhouse/` - sibling directory)
- **J7\WpUtils**: Utility library (`../powerhouse/vendor/j7-dev/wp-utils/`)

### Database
- Custom tables: `pc_avl_coursemeta`, `pc_avl_chaptermeta`, `pc_email_records`, `pc_student_logs`
- WordPress tables: Standard WP + WooCommerce tables

### External APIs
- Bunny CDN for video streaming
- Vimeo/YouTube integration
- WordPress REST API endpoints

## Testing & Quality Assurance

**No automated tests currently exist** - all testing is manual. Focus on:
1. **Static analysis**: phpstan level 9 compliance
2. **Code standards**: phpcs WordPress rules compliance  
3. **Type safety**: TypeScript strict mode compliance
4. **Manual testing**: Verify functionality in WordPress admin

## Trust These Instructions

These instructions are comprehensive and tested. **Only search for additional information if:**
- Instructions are incomplete for your specific task
- You encounter errors not covered in the pitfalls section
- You need to understand code patterns not documented here

Always prioritize following these patterns over creating new approaches.