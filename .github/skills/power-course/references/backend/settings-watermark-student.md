# Settings、水印與 Student 系統詳細參考

## Settings（外掛設定）

```php
// 存取方式（DTO 模式，非真正 Singleton）
$settings = \J7\PowerCourse\Resources\Settings\Model\Settings::instance();
$settings->course_access_trigger;   // 'completed' — 觸發開通課程的訂單狀態
$settings->hide_myaccount_courses;  // 'yes'|'no'
$settings->pc_watermark_qty;        // int — 0 禁用水印
$settings->pc_watermark_text;       // '{display_name} {post_title} IP:{ip}'
$settings->pc_pdf_watermark_text;   // PDF 水印文字

// WP option key: 'power_course_settings'
```

Bunny 串流設定（library_id, cdn_hostname, stream_api_key）存放在 Powerhouse 的 `powerhouse_settings`：
```php
\J7\Powerhouse\Settings\Model\Settings::instance()->bunny_library_id;
```

---

## 水印系統

### 水印文字佔位符

| 佔位符 | 替換內容 |
|--------|----------|
| `{display_name}` | 用戶顯示名稱 |
| `{email}` | 用戶 Email |
| `{ip}` | 用戶 IP |
| `{username}` | 用戶帳號 |
| `{post_title}` | 文章/課程標題 |

### 兩種水印

- **視頻水印**：使用 `pc_watermark_text`，`pc_watermark_qty` 控制數量（0 = 關閉）
- **PDF 水印**：使用 `pc_pdf_watermark_text`

---

## Student / StudentLog 系統

### Student 查詢

```php
// 支援反向查詢：查找「沒有此課程」的用戶
// course_id 前綴加 ! 代表反查，例：查詢 course_id != 123 的用戶
// 使用原生 SQL JOIN，不走 WP_User_Query
```

### Teacher 反查

```php
// is_teacher = '!yes' → NOT EXISTS + != 的 OR 邏輯
```

### StudentLog（行為日誌）

```php
// StudentLog（DTO）+ CRUD（Singleton）雙類別模式
// log_type 使用 AtHelper::$allowed_slugs 驗證
// 允許的 log_type：course_granted, course_finish, course_launch,
//                  chapter_enter, chapter_finish, order_created
// 對應資料表：{prefix}_pc_student_logs
```

### ExportCSV

```php
// Student\Service\ExportCSV 提供匯出功能
// 輸出 HTTP header: Content-Type: text/csv; charset=UTF-8
```
