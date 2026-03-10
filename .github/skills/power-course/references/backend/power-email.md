# PowerEmail 子系統詳細參考

Namespace 根：`J7\PowerCourse\PowerEmail\`

## Email CPT

```php
// post_type = 'pe_email'
// 生產環境隱藏（Plugin::$is_local = true 才顯示）
// post_content = MJML HTML（渲染用）
// post_excerpt = easy-email-editor JSON（編輯器用）
// REST namespace = 'power-email'（不是 'power-course'）
```

## 觸發時機點（AtHelper::$allowed_slugs）

| Slug | 觸發事件 | AS Hook 名稱 |
|------|----------|--------------|
| `course_granted` | 課程開通 | `power_email_send_course_granted` |
| `course_finish` | 課程完成 | `power_email_send_course_finish` |
| `course_launch` | 課程開課 | `power_email_send_course_launch` |
| `chapter_enter` | 進入章節 | `power_email_send_chapter_enter` |
| `chapter_finish` | 完成章節 | `power_email_send_chapter_finish` |
| `order_created` | 訂單建立 | `power_email_send_order_created` |

> `chapter_unfinished` 尚未實作 trigger。

## 發信時機

```php
// send_now: 立即寄送
// send_later: 延遲 N 天/時/分（設定在 Email meta）
$email->get_sending_timestamp(); // 根據 send_type 計算排程時間
```

## 發信條件（Condition.php）

| trigger_condition | 意義 |
|-------------------|------|
| `each` | 任一條件達成即可 |
| `all` | 全部條件都要達成 |
| `qty_greater_than` | 達成數量超過設定值 |

## 防重複寄信

```php
// identifier = Email::get_identifier($user_id, $course_id, $email_id, $trigger_at)
// 寄送前查詢 pc_email_records 是否已有相同 identifier
// Action Scheduler 也查詢 as_get_scheduled_actions() 防重複排程
```

## Replace 變數替換系統

三個 Replace 子類（均為 `abstract class` 繼承 `ReplaceBase`）：

```php
// Replace\User    — prefix: ''（無前綴）
// 可用變數：{display_name}, {user_email}, {ID}, {user_login}

// Replace\Course  — prefix: 'course_'
// 可用變數：{course_name}, {course_id}, {course_regular_price},
//           {course_sale_price}, {course_slug}, {course_image_url}, {course_permalink}

// Replace\Chapter — prefix: 'chapter_'
// 可用變數：{chapter_title}
```

使用 filter 替換：
```php
\add_filter( 'power_email_course_subject', [ Replace\User::class,    'replace_string' ] );
\add_filter( 'power_email_course_subject', [ Replace\Course::class,  'replace_string' ] );
\add_filter( 'power_email_course_subject', [ Replace\Chapter::class, 'replace_string' ] );
\add_filter( 'power_email_course_html',    [ Replace\User::class,    'replace_string' ] );
// ...同上
```

`replace_string( $html, $user_id, $course_id, $chapter_id )` — filter callback 簽名。

## 批次發信

```php
// 使用 PowerhouseUtils::batch_process()
// batch_size = 20, pause = 750ms
```

## EmailRecord CRUD

```php
// 對 {prefix}_pc_email_records 操作
EmailRecord\CRUD::get( $where );
EmailRecord\CRUD::add( $post_id, $user_id, $email_id, $subject, $trigger_at, $identifier, $unique=true );
EmailRecord\CRUD::update( $where, $data );
EmailRecord\CRUD::delete( $id );
// $unique=true 時：存在則 update，不存在則 insert（防重複記錄）
```
