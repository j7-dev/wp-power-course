# 章節（Chapter）系統詳細參考

## CPT 規格

```php
// post_type = 'pc_chapter'
// rewrite slug = 'classroom'
// 生產環境隱藏（Plugin::$is_local 必須為 true 才顯示於 WP Admin）
```

## 層級結構

```
課程 (WC Product)
  └── 頂層章節 (pc_chapter, post_parent=0)
        └── 子章節 (pc_chapter, post_parent={parent_id})
```

- 頂層章節 ↔ 課程的關係：透過 `parent_course_id` post meta（**不是** `post_parent`）
- `ChapterUtils::get_course_id($chapter_id)` — 先找頂層 post，再取 `parent_course_id` meta

## MetaCRUD

```php
// Chapter\Utils\MetaCRUD 對應 {prefix}_pc_avl_chaptermeta
// 欄位：meta_id, post_id, user_id, meta_key, meta_value, created_at, updated_at
// 常用 meta_key：first_visit_at, finished_at
```

## 排序

```php
// sort_chapters() 使用 CASE WHEN SQL + Transaction 批量更新
// 更新：menu_order, post_parent, parent_course_id
$wpdb->query(
    "UPDATE {$wpdb->posts} SET menu_order = CASE id
        WHEN {$id1} THEN 0
        WHEN {$id2} THEN 1
    END
    WHERE id IN ({$ids_string})"
);
```

## Chapter 圖示狀態（前端）

| 狀態 | 圖示 |
|------|------|
| 未觀看 | video icon |
| 觀看中 | outline check |
| 已完成 | filled check |

## ChapterUtils 常用方法

```php
ChapterUtils::get_course_id( $chapter_id );      // 取得章節所屬課程 ID
ChapterUtils::get_top_chapter( $chapter_id );    // 取得頂層章節
ChapterUtils::is_free( $chapter_id );            // 是否免費試看
ChapterUtils::get_chapter_progress( $product, $user_id ); // 取得進度
```

## 相關 Action Hooks

```php
'power_course_visit_chapter'       // ($chapter_post, $course_product)
'power_course_chapter_finished'    // ($chapter_id, $course_id, $user_id)
'power_course_chapter_unfinished'  // ($chapter_id, $course_id, $user_id)
'power_course_before_classroom_render' // ()
```
