# 線性觀看功能 — Activity Diagram

## Activity 1: 管理員開啟/關閉線性觀看

```
[管理員] 進入課程編輯頁面
    │
    ▼
[系統] 載入課程設定，顯示「線性觀看」開關
    │
    ▼
[管理員] 切換「線性觀看」開關
    │
    ▼
[管理員] 點擊儲存
    │
    ▼
[前端] POST /wp-json/power-course/courses/{id}
       body: { enable_linear_mode: 'yes' | 'no' }
    │
    ▼
[後端] update_post_meta($product_id, 'enable_linear_mode', $value)
    │
    ▼
[系統] 回傳成功訊息
    │
    ▼
[前端] 顯示「設定已儲存」Toast
```

## Activity 2: 學員存取教室頁面（線性模式）

```
[學員] 進入教室頁面 URL
    │
    ▼
[PHP 模板] 載入 classroom template
    │
    ├─── 檢查課程 enable_linear_mode meta
    │         │
    │         ├── 'no' 或不存在 → [正常渲染] 無鎖定邏輯
    │         │
    │         └── 'yes' → 繼續鎖定檢查
    │
    ▼
[PHP] 取得 flatten_post_ids（全部章節扁平排序）
    │
    ▼
[PHP] 對當前章節進行鎖定判斷:
    │
    ├── current_user_can('manage_woocommerce') → [繞過] 正常渲染
    │
    ├── 章節 index == 0（第一個） → [解鎖] 正常渲染
    │
    ├── 章節 finished_at 已存在 → [解鎖] 正常渲染（已完成章節可回看）
    │
    ├── 前一章節 finished_at 已存在 → [解鎖] 正常渲染
    │
    └── 前一章節未完成 → [鎖定]
              │
              ▼
        [PHP] 渲染鎖定提示頁面
              - 不載入影片和章節內容
              - 顯示「請先完成「{前一章節名稱}」才能觀看此內容」
              - 提供前一章節的連結
```

## Activity 3: 教室 Sidebar 章節列表渲染

```
[PHP] get_children_posts_html_uncached() 渲染章節列表
    │
    ▼
[PHP] 對每個章節:
    │
    ├── enable_linear_mode != 'yes' → [正常圖示] video/check-outline/check
    │
    └── enable_linear_mode == 'yes':
          │
          ├── 管理員 → [正常圖示] 不加鎖
          │
          ├── 第一個章節 → [正常圖示] 不加鎖
          │
          ├── finished_at 存在 → [已完成圖示] 不加鎖
          │
          ├── 前一章節 finished_at 存在 → [正常圖示] 不加鎖（已解鎖）
          │
          └── 前一章節未完成 → [鎖定圖示] 🔒
                │
                ▼
              <li> 加上 data-locked="true"
              圖示替換為鎖頭 SVG
              點擊時顯示鎖定提示，不跳轉
```

## Activity 4: 學員完成章節（JS 局部解鎖）

```
[學員] 點擊「標示為已完成」按鈕
    │
    ▼
[JS] POST /wp-json/power-course/toggle-finish-chapters/{chapter_id}
     body: { course_id }
    │
    ▼
[PHP API] 檢查: 線性模式 && 章節已完成 && 嘗試取消完成?
    │
    ├── 是 → 回傳 403「線性觀看模式下，已完成的章節無法取消完成」
    │
    └── 否 → 正常處理完成邏輯
          │
          ▼
    [PHP] AVLChapterMeta::add(finished_at)
          │
          ▼
    [PHP] 計算 next_chapter_unlocked 資訊
          │
          ▼
    [PHP] 回傳 200 + data:
          {
            chapter_id, course_id,
            is_this_chapter_finished: true,
            progress, icon_html,
            next_chapter_unlocked: {
              chapter_id, chapter_title, icon_html
            }
          }
    │
    ▼
[JS] 收到成功回應:
    │
    ├── 更新當前章節圖示為已完成
    │
    ├── 更新進度條
    │
    ├── 隱藏「標示為已完成」按鈕（線性模式下不可逆）
    │
    ├── 解鎖下一章節:
    │     - 移除下一章節的 data-locked 屬性
    │     - 替換鎖頭圖示為正常圖示
    │     - 啟用下一章節的點擊跳轉
    │
    └── 顯示 Dialog:「已完成！下一章節已解鎖」
```

## Activity 5: 學員點擊鎖定章節（前端攔截）

```
[學員] 點擊 sidebar 中被鎖定的章節 <li>
    │
    ▼
[JS] 檢查 li[data-locked="true"]
    │
    ├── data-locked != "true" → [正常跳轉] window.location.href = href
    │
    └── data-locked == "true" → [攔截]
          │
          ▼
        [JS] 阻止頁面跳轉
              │
              ▼
        [JS] 顯示 Dialog:
              標題：「章節已鎖定」
              訊息：「請先完成「{前一章節名稱}」才能觀看此內容」
              按鈕：「關閉」
```
