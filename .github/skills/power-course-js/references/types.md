# 型別系統詳細定義 — Power Course JS

> 此文件由 `SKILL.md` 引用 — 需要完整型別定義時載入。

## 型別系統詳細定義

### 2.1 三個平行型別命名空間

```
types/
├── wcRestApi/     → WC REST API 型別 (TProduct, TProductVariation, TMeta)
├── wcStoreApi/    → WC Store API 型別 (TProduct, TCart, TCartItem)
└── wpRestApi/     → WP REST API 型別 (TPost, TPostsArgs, TPagination)
```

#### wcRestApi (後台管理用)
- `TProduct`: 80+ 欄位的完整商品型別
- `TProductVariation`: 商品變體（繼承 TProduct 子集 + attributes）
- `TMeta`: `{ id: number; key: string; value: string }`
- `TAttribute`: 商品屬性（name, options, visible, variation）

#### wcStoreApi (前台商店用)
- `TProduct`: 精簡版商品型別（前台展示用）
- `TCart`: 購物車（items, totals, coupons, shipping_rates）
- `TCartItem`: 購物車項目
- `defaultProduct`: 預設空商品常數（用於初始化）

#### wpRestApi (WordPress 文章用)
- `TPost`: WordPress 文章型別
- `TPostsArgs`: 文章查詢參數
- `TPagination`: 分頁資訊（total, totalPages）
- `TImage`: 圖片型別（id, url）

### 2.2 共用型別

```typescript
// types/common.ts
type THttpMethods = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
type TOrderBy = 'date' | 'id' | 'include' | 'title' | 'slug' | 'price' | ...
type TOrder = 'asc' | 'desc'
type TDataProvider = 'wp-rest' | 'wc-rest' | 'wc-store'
```

### 2.3 業務型別 (pages/admin/Courses/List/types/)

```typescript
// 課程基礎型別（繼承 WC 商品）
type TCourseBaseRecord = TProductRecord & {
  teacher_ids: string[]
  course_schedule: string
  course_schedule_timestamp: number
  // ... 課程專屬欄位
}

// 章節型別
type TChapterRecord = {
  id: number
  name: string
  slug: string
  status: string
  chapter_video: TVideo
  chapter_length: number | string
  // ...
}

// 使用者型別
type TUserRecord = {
  id: number
  user_login: string
  user_email: string
  display_name: string
  avl_courses: TAVLCourse[]  // 可存取的課程列表
  is_teacher: boolean
}

// 到期日型別
type TExpireDate = {
  is_expired: boolean
  timestamp: number
  is_subscription: boolean
  subscription_id: number
}
```

### 2.4 全域型別宣告

```typescript
// types/global.d.ts
declare global {
  interface Window {
    appData: { env: Record<string, string> }
    wpApiSettings: { nonce: string; root: string }
  }
}
```

---
