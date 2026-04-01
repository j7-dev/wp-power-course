# 實作計劃：外部課程功能（External Course）

## 概述

在 Power Course 外掛中新增「外部課程」功能，讓講師可展示在外部平台（Hahow、PressPlay 等）開設的課程。技術方案是利用 WooCommerce 內建 `external`（`WC_Product_External`）產品類型搭配 `_is_course = 'yes'` meta flag，前台卡片樣式與站內課程一致但有外部標記，銷售頁 CTA 按鈕以新視窗開啟外部連結，且外部課程不參與章節管理、學員管理、銷售方案、自動郵件、自動授權、營收報表等站內功能。

## 範圍模式

**HOLD SCOPE（維持）** -- 需求已在 7 個 Feature Specs 中明確定義，本計劃聚焦於防彈架構與邊界情況處理。

## 需求重述

1. **資料模型**：外部課程 = `WC_Product_External` + `_is_course = 'yes'`，使用 WC 原生 `_product_url` 和 `_button_text` 欄位
2. **建立/更新 API**：REST API `POST /courses` 支援 `product_type=external` 參數；`external_url` 必填且為 http/https URL；`button_text` 選填（預設「前往課程」）
3. **查詢 API**：`GET /courses` 新增 `product_type` 篩選參數，回應包含 `external_url` / `button_text` 欄位
4. **前台展示**：課程卡片右上角顯示 ↗ 外部標記；點擊卡片進入站內銷售頁；銷售頁 CTA 按鈕 `target="_blank" rel="noopener noreferrer"` 開啟外部連結
5. **功能隔離**：外部課程不出現在學員管理、自動郵件、自動授權、銷售方案、章節管理、營收報表中
6. **購物車阻擋**：`WC_Product_External::is_purchasable()` 天然回傳 `false`，不需額外程式碼
7. **類型不可轉換**：建立後不可改變 `product_type`

## 已知風險（來自研究）

- **風險**：`WC_Product_External` 的 `is_purchasable()` 在某些 WC 版本或外掛 filter 下可能被覆寫 -- 緩解措施：在功能隔離測試中明確驗證 `is_purchasable()` 回傳 `false`
- **風險**：現有程式碼中 `separator()` 方法預設建立 `WC_Product_Simple`，新增 external 類型時需確保不破壞現有流程 -- 緩解措施：在 `separator()` 中根據 `product_type` 參數決定產品類型，僅在建立新產品時生效
- **風險**：前台模板中多處假設產品為 simple 類型（如 `add-to-cart.php`、`single-product-sale.php`），直接渲染購物車按鈕 -- 緩解措施：在模板中加入 external 類型判斷分支
- **風險**：`Admin\Product::add_product_type_options()` 的 `wrapper_class` 為 `show_if_simple`，WC 傳統商品編輯頁不會對 external 類型顯示課程 checkbox -- 緩解措施：擴展 `wrapper_class` 支援 `show_if_external`

## 架構變更

| # | 檔案路徑 | 變更類型 | 說明 |
|---|----------|----------|------|
| 1 | `inc/classes/Api/Course.php` | 修改 | `separator()` 支援 external 類型；`post_courses_callback()` 驗證 external_url；`handle_save_course_meta_data()` 處理 external 類型 meta；`format_course_base_records()` 回傳 external_url / button_text；`get_courses_callback()` 支援 product_type 篩選 |
| 2 | `inc/classes/Admin/Product.php` | 修改 | `wrapper_class` 加入 `show_if_external`；`custom_display_post_states()` 標記外部課程 |
| 3 | `inc/templates/components/card/pricing.php` | 修改 | 加入外部課程 ↗ 標記 |
| 4 | `inc/templates/components/card/single-product.php` | 修改 | 外部課程路由到新的 external-product card 模板 |
| 5 | `inc/templates/components/card/single-product-external.php` | 新增 | 外部課程銷售頁側欄卡片（顯示價格 + CTA 按鈕 target=_blank） |
| 6 | `inc/templates/pages/course-product/body.php` | 修改 | 外部課程隱藏章節相關資訊（章節數量、開課時間、觀看時間等） |
| 7 | `inc/templates/pages/course-product/sider.php` | 修改 | 外部課程隱藏銷售方案區塊 |
| 8 | `inc/templates/course-product-entry.php` | 修改 | 外部課程隱藏「已購買」對話框 |
| 9 | `inc/classes/Api/Course/UserTrait.php` | 修改 | `add-students` / `remove-students` 端點拒絕外部課程 |
| 10 | `inc/classes/Resources/Chapter/Core/Api.php` | 修改 | 建立章節時拒絕外部課程 |
| 11 | `inc/classes/Resources/Course/AutoGrant.php` | 修改 | `grant_courses_on_register()` 跳過 external 類型課程 |
| 12 | `js/src/pages/admin/Courses/List/types/index.ts` | 修改 | `TCourseBaseRecord` 新增 `external_url` / `button_text` / `product_type` 欄位 |
| 13 | `js/src/pages/admin/Courses/List/hooks/useColumns.tsx` | 修改 | 課程列表加入外部課程類型標記 Tag |
| 14 | `js/src/pages/admin/Courses/List/Table/index.tsx` | 修改 | 新增課程按鈕支援選擇課程類型（站內/外部） |
| 15 | `js/src/pages/admin/Courses/Edit/index.tsx` | 修改 | 外部課程隱藏不適用的 Tab（章節管理、銷售方案、學員管理、分析）；顯示外部連結設定欄位 |
| 16 | `js/src/pages/admin/Courses/Edit/tabs/CoursePrice/index.tsx` | 修改 | 外部課程只顯示展示用價格欄位，隱藏庫存/訂閱相關欄位 |

## 資料流分析

### 建立外部課程

```
POST /courses (product_type=external)
     |
     v
SEPARATOR ──────────────── VALIDATE ──────────────── PERSIST ──────────── RESPONSE
  |                           |                         |                    |
  v                           v                         v                    v
product_type=external?   external_url 非空?      WC_Product_External     {id, product_type,
  -> new WC_Product_     是合法 http/https URL?     ->set_product_url()    external_url,
     External()          button_text 預設?           ->set_button_text()    button_text}
  |                      price >= 0?                  ->save()
  v                           |                    wp_set_object_terms
[nil?] product_type      [invalid?] URL            ('external')
  未傳 -> simple(預設)     格式不對 -> 400
[empty?] N/A              [empty?] external_url
                            空 -> 400
                          [error?] save 失敗
                            -> 500
```

### 前台展示外部課程

```
VISITOR ──▶ 課程列表頁 ──▶ 課程卡片 ──▶ 點擊卡片 ──▶ 站內銷售頁 ──▶ 點擊 CTA ──▶ 外部平台
  |              |              |             |              |               |
  v              v              v             v              v               v
[nil?]     [empty?]      external 類型?   同站導航     CTA 按鈕顯示     target=_blank
 未登入      無課程       是 -> 顯示↗     (非新視窗)   button_text      rel=noopener
 仍可瀏覽    顯示空狀態   否 -> 無標記                  預設「前往課程」
```

## 錯誤處理登記表

| 方法/路徑 | 可能失敗原因 | 錯誤類型 | 處理方式 | 使用者可見? |
|-----------|-------------|----------|----------|------------|
| `POST /courses` (external) | external_url 為空 | 400 Bad Request | 回傳錯誤訊息含 "external_url" | 是，API 錯誤回應 |
| `POST /courses` (external) | external_url 非合法 URL | 400 Bad Request | 回傳錯誤訊息含 "external_url" | 是，API 錯誤回應 |
| `POST /courses` (external) | external_url 非 http/https | 400 Bad Request | 回傳錯誤訊息含 "external_url" | 是，API 錯誤回應 |
| `POST /courses` (external) | price 為負數 | 400 Bad Request | 回傳錯誤訊息含 "price" | 是，API 錯誤回應 |
| `POST /courses` (external) | name 為空 | 400 Bad Request | 回傳錯誤訊息含 "name" | 是，API 錯誤回應 |
| `POST /courses/{id}` (external) | 更新時清空 external_url | 400 Bad Request | 回傳錯誤訊息含 "external_url" | 是，API 錯誤回應 |
| `POST /courses/add-students` | 目標為外部課程 | 400 Bad Request | 回傳錯誤訊息含 "external" | 是，API 錯誤回應 |
| 建立章節 `POST /chapters` | parent 為外部課程 | 400 Bad Request | 回傳錯誤訊息含 "external" | 是，API 錯誤回應 |
| 前台 add-to-cart | 外部課程加入購物車 | WC 原生阻擋 | `is_purchasable()` = false | 是，WC 提示 |
| AutoGrant | auto_grant_course_ids 包含外部課程 | 靜默跳過 | `grant_courses_on_register()` 跳過 | 否，靜默 |

## 失敗模式登記表

| 程式碼路徑 | 失敗模式 | 已處理? | 有測試? | 使用者可見? | 恢復路徑 |
|-----------|---------|---------|---------|------------|---------|
| `separator()` 建立產品 | product_type 值非 simple/external | 否 -> 本次實作 | 待建立 | 是 (400) | 回傳驗證錯誤 |
| `handle_save_course_meta_data()` | external 課程嘗試設 subscription | 否 -> 本次實作 | 待建立 | 是 (400) | 回傳類型衝突錯誤 |
| `format_course_base_records()` | WC_Product_External 無 get_product_url() | 已有 (WC 原生) | 待建立 | 否 | 回傳空字串 |
| 銷售頁模板 | 外部課程無 bundle_products | 否 -> 本次實作 | 待建立 | 否 (UI 隱藏) | 跳過渲染 |
| 課程列表卡片 | 外部課程無章節資料 | 部分 (count=0) | 待建立 | 否 | 顯示 0 或隱藏 |
| AutoGrant 設定寫入 | external course_id 通過驗證 | 否 -> 本次實作 | 待建立 | 是 (400) | 回傳驗證錯誤 |

## 實作步驟

### 第一階段：後端 PHP -- API 核心（建立 / 更新 / 查詢）

> 執行 Agent：`@wp-workflows:wordpress-master`

#### 步驟 1-1：擴展 `separator()` 支援 external 產品類型

**檔案**：`inc/classes/Api/Course.php` -- `separator()` 方法（約 L521-L554）

**行動**：
- 從 `$body_params` 取得 `product_type` 參數（預設 `'simple'`）
- 當 `product_type === 'external'` 時，建立 `new \WC_Product_External()` 而非 `new \WC_Product_Simple()`
- 當 `$id` 存在（更新場景）時，不改變產品類型（`wc_get_product()` 已回傳正確類型）
- 驗證 `product_type` 只能是 `'simple'`、`'subscription'` 或 `'external'`，否則回傳 400

**原因**：這是整個功能的基礎。`separator()` 負責建立產品物件，必須根據類型建立正確的 WC_Product 子類別。

**依賴**：無

**風險**：中 -- 此方法為所有課程 CRUD 的核心入口，修改需確保不影響現有 simple/subscription 類型

**關鍵程式碼變更**：
```php
// 現有：
$product = \wc_get_product( $id );
if (!$product) {
    $product = new \WC_Product_Simple();
}

// 改為：
$product = \wc_get_product( $id );
if (!$product) {
    $product_type = $body_params['product_type'] ?? 'simple';
    $product = match ($product_type) {
        'external' => new \WC_Product_External(),
        'simple', 'subscription' => new \WC_Product_Simple(),
        default => null, // 回傳 400 錯誤
    };
}
```

---

#### 步驟 1-2：新增 external 類型驗證邏輯

**檔案**：`inc/classes/Api/Course.php` -- `post_courses_callback()` 方法（約 L486-L510）

**行動**：
- 在呼叫 `handle_save_course_data()` 之前，判斷產品是否為 `WC_Product_External`
- 若是 external 類型：
  - 驗證 `external_url` 存在且非空
  - 驗證 `external_url` 為合法 URL（`filter_var(FILTER_VALIDATE_URL)`）
  - 驗證 `external_url` 以 `http://` 或 `https://` 開頭
  - 若 `button_text` 未提供，設定預設值「前往課程」
  - 若 `price` 存在且為負數，回傳 400
- 對 `WC_Product_External` 呼叫 `set_product_url()` 和 `set_button_text()`
- `post_courses_with_id_callback()` 同理需加入 external_url 驗證（更新時不可清空）

**原因**：spec 明確要求 external_url 為必填且為合法 http/https URL，button_text 預設「前往課程」。

**依賴**：步驟 1-1

**風險**：低

---

#### 步驟 1-3：修改 `handle_save_course_meta_data()` 處理 external 類型

**檔案**：`inc/classes/Api/Course.php` -- `handle_save_course_meta_data()` 方法（約 L580-L643）

**行動**：
- 判斷產品類型：若為 `WC_Product_External`，不執行 subscription 相關邏輯
- 設定 `wp_set_object_terms($id, 'external', 'product_type')` 而非 `'simple'` 或 `'subscription'`
- 外部課程不需要處理 `limit_type` / `limit_value` / `limit_unit` 等存取期限 meta
- 外部課程跳過 `LifeCycle::BEFORE_UPDATE_PRODUCT_META_ACTION` 中不適用的 hook

**原因**：`handle_save_course_meta_data()` 最後一行是 `wp_set_object_terms($id, $is_subscription ? 'subscription' : 'simple', 'product_type')`，這會覆蓋 external 類型為 simple。

**依賴**：步驟 1-1

**風險**：高 -- 此方法邏輯複雜，修改需特別小心不影響現有 simple/subscription 流程

**關鍵程式碼變更**：
```php
// 現有 L635：
$result = \wp_set_object_terms($id, $is_subscription ? 'subscription' : 'simple', 'product_type');

// 改為根據實際產品類型判斷：
$type_term = match (true) {
    $product instanceof \WC_Product_External => 'external',
    $is_subscription                         => 'subscription',
    default                                  => 'simple',
};
$result = \wp_set_object_terms($id, $type_term, 'product_type');
```

---

#### 步驟 1-4：擴展 `format_course_base_records()` 回傳 external 欄位

**檔案**：`inc/classes/Api/Course.php` -- `format_course_base_records()` 方法（約 L332-L475）

**行動**：
- 在 `$base_array` 中新增以下欄位：
  - `'product_type' => $product->get_type()` -- 已有 `'type'` 欄位可複用，但依 spec 額外加入 `product_type` 鍵名便於語義清晰
  - `'external_url' => $product instanceof \WC_Product_External ? $product->get_product_url() : ''`
  - `'button_text' => $product instanceof \WC_Product_External ? $product->get_button_text() : ''`
- 外部課程的 `course_length` 固定為 0（無章節）
- 外部課程的 `classroom_link` 固定為空字串

**原因**：spec 查詢外部課程列表要求回應包含 `external_url` 和 `button_text` 欄位。

**依賴**：無

**風險**：低

---

#### 步驟 1-5：`get_courses_callback()` 支援 `product_type` 篩選

**檔案**：`inc/classes/Api/Course.php` -- `get_courses_callback()` 方法（約 L136-L187）

**行動**：
- 從 `$params` 取得 `product_type` 參數
- 若有值且為 `'external'` 或 `'simple'`，加入 `'type' => $product_type` 至 `$args`（WC `wc_get_products` 支援 `type` 參數篩選）
- 預設不傳 type 時回傳所有類型（含 external）

**原因**：spec 查詢外部課程列表要求支援 `product_type` 篩選。

**依賴**：無

**風險**：低

---

### 第二階段：後端 PHP -- 功能隔離

> 執行 Agent：`@wp-workflows:wordpress-master`

#### 步驟 2-1：學員管理 API 拒絕外部課程

**檔案**：`inc/classes/Api/Course/UserTrait.php`

**行動**：
- 在 `post_courses_add_students_callback()` 方法開頭，取得目標課程的 product，檢查是否為 `WC_Product_External`
- 若是，回傳 `WP_REST_Response` 400 錯誤：「外部課程不可新增學員」
- `post_courses_remove_students_callback()` 和 `post_courses_update_students_callback()` 同理

**原因**：spec 外部課程功能隔離要求外部課程不可新增/移除/更新學員。

**依賴**：步驟 1-1

**風險**：低

---

#### 步驟 2-2：章節 API 拒絕外部課程新增章節

**檔案**：`inc/classes/Resources/Chapter/Core/Api.php`

**行動**：
- 在建立章節的 callback 方法中，取得 `parent_course_id` 對應的產品
- 若產品為 `WC_Product_External`，回傳 400 錯誤：「外部課程不可新增章節」

**原因**：spec 外部課程功能隔離要求外部課程不可新增章節。

**依賴**：無

**風險**：低

---

#### 步驟 2-3：自動授權跳過外部課程

**檔案**：`inc/classes/Resources/Course/AutoGrant.php` -- `grant_courses_on_register()` 方法

**行動**：
- 在 foreach 迴圈內（約 L31-L50），取得 `$course_id` 對應的產品
- 若產品為 `WC_Product_External`，`continue` 跳過

**原因**：spec 外部課程功能隔離要求外部課程不參與自動授權。

**依賴**：無

**風險**：低

**關鍵程式碼變更**：
```php
// 在現有 is_course_product() 檢查之後加入：
$product = \wc_get_product( $course_id );
if ( $product instanceof \WC_Product_External ) {
    continue;
}
```

---

#### 步驟 2-4：設定 API 驗證 auto_grant_course_ids 不含外部課程

**檔案**：`inc/classes/Resources/Settings/Core/Api.php` 或 `inc/classes/Resources/Settings/Model/Settings.php`

**行動**：
- 在 `normalize_auto_grant_courses()` 或更新設定的驗證邏輯中
- 檢查每個 `course_id` 對應的產品類型
- 若為 `WC_Product_External`，回傳 400 錯誤或靜默過濾掉

**原因**：spec 外部課程功能隔離要求外部課程不可被加入自動授權課程清單。

**依賴**：無

**風險**：低

---

#### 步驟 2-5：WC 傳統商品編輯頁支援 external 類型的課程 checkbox

**檔案**：`inc/classes/Admin/Product.php` -- `add_product_type_options()` 方法

**行動**：
- 將 `wrapper_class` 從 `'show_if_simple'` 改為 `'show_if_simple show_if_external'`
- 這樣 WC 傳統商品編輯頁在選擇 external 產品類型時，也會顯示「課程」checkbox
- 更新 description 文字：加入「外聯商品」

**原因**：管理員也可能從 WC 傳統商品編輯頁建立外部課程，需要讓課程 checkbox 在 external 類型下可見。

**依賴**：無

**風險**：低

---

### 第三階段：前台 PHP 模板

> 執行 Agent：`@wp-workflows:wordpress-master`

#### 步驟 3-1：課程卡片加入外部標記 ↗

**檔案**：`inc/templates/components/card/pricing.php`

**行動**：
- 在卡片圖片區塊內（`pc-course-card__image-wrap` div），加入判斷
- 若 `$product instanceof \WC_Product_External`，在圖片右上角顯示 ↗ 圖示
- 使用已存在的 `inc/templates/components/icon/external-link.php` 模板
- 樣式：absolute 定位右上角，半透明背景，白色圖示

**原因**：spec 前台展示要求外部課程卡片有細微外部標記 ↗。

**依賴**：無

**風險**：低

**關鍵程式碼變更**：
```php
// 在 image-wrap div 內加入：
$is_external = $product instanceof \WC_Product_External;
$external_badge = $is_external
    ? '<div class="absolute top-2 right-2 bg-black/50 rounded-full p-1">'
      . Plugin::load_template('icon/external-link', ['class' => 'size-4 fill-white'], false)
      . '</div>'
    : '';
```

---

#### 步驟 3-2：新增外部課程銷售頁側欄卡片

**檔案**：`inc/templates/components/card/single-product-external.php`（新增）

**行動**：
- 建立新的卡片模板，參考 `single-product-sale.php` 的結構
- 顯示：課程名稱、展示價格（使用 `price` 元件）、課程備註
- CTA 按鈕：使用 `button` 模板，文字為 `$product->get_button_text()` 或「前往課程」
- 按鈕 href 為 `$product->get_product_url()`
- 加入 `target="_blank"` 和 `rel="noopener noreferrer"` 屬性
- 不顯示「加入購物車」按鈕、不顯示「立即報名」按鈕
- 不顯示庫存資訊、倒數計時

**原因**：外部課程銷售頁需要完全不同的 CTA 行為 -- 不是加入購物車，而是開啟外部連結。

**依賴**：無

**風險**：低

---

#### 步驟 3-3：修改 `single-product.php` 路由到外部課程卡片

**檔案**：`inc/templates/components/card/single-product.php`

**行動**：
- 在判斷 `$is_free` 之前，先判斷 `$product instanceof \WC_Product_External`
- 若是，載入 `card/single-product-external` 模板並 return
- 否則走原有邏輯（free / sale）

**原因**：銷售頁側欄需根據產品類型載入對應的卡片模板。

**依賴**：步驟 3-2

**風險**：低

**關鍵程式碼變更**：
```php
// 在 $is_free 判斷之前加入：
if ( $product instanceof \WC_Product_External ) {
    Plugin::load_template(
        'card/single-product-external',
        [
            'product' => $product,
        ]
    );
    return;
}
```

---

#### 步驟 3-4：銷售頁 body 隱藏外部課程不適用的資訊

**檔案**：`inc/templates/pages/course-product/body.php`

**行動**：
- 在 `$items` 陣列組裝之前，判斷 `$product instanceof \WC_Product_External`
- 若是外部課程：
  - 隱藏「開課時間」、「課程時長」、「章節數量」、「觀看時間」、「課程學員」等項目
  - 隱藏「已購買課程」提示
  - 隱藏 mobile fixed CTA（不需要「立即報名」）
  - Tabs 區塊中隱藏章節列表 tab
- 保留：課程介紹 tab

**原因**：外部課程無章節、無學員、無開課時間等站內功能資料。

**依賴**：無

**風險**：中 -- body.php 邏輯較複雜，需確保條件判斷正確

---

#### 步驟 3-5：銷售頁 sider 隱藏外部課程的銷售方案

**檔案**：`inc/templates/pages/course-product/sider.php`

**行動**：
- 在載入 `card/single-product` 之後的 `Helper::get_bundle_products()` 之前
- 判斷 `$product instanceof \WC_Product_External`
- 若是，跳過銷售方案迴圈（不渲染 bundle-product 卡片）

**原因**：spec 前台展示要求外部課程銷售頁隱藏銷售方案區塊。

**依賴**：無

**風險**：低

---

#### 步驟 3-6：銷售頁 entry 隱藏外部課程的已購買對話框

**檔案**：`inc/templates/course-product-entry.php`

**行動**：
- 在渲染 `pc-already-bought-modal` dialog 之前
- 判斷 `$product instanceof \WC_Product_External`
- 若是，跳過 dialog 渲染

**原因**：外部課程不可購買，不需要「已購買」提示。

**依賴**：無

**風險**：低

---

### 第四階段：前端 React/TypeScript -- Admin SPA

> 執行 Agent：`@wp-workflows:react-master`

#### 步驟 4-1：擴展 TypeScript 型別定義

**檔案**：`js/src/pages/admin/Courses/List/types/index.ts`

**行動**：
- 在 `TCourseBaseRecord` 中新增：
  - `product_type: 'simple' | 'external'`（注意：現有 `type` 欄位已有 `TProductType`，但 `product_type` 更語義化）
  - `external_url: string`
  - `button_text: string`

**原因**：API 回應新增了欄位，前端型別需同步。

**依賴**：步驟 1-4 完成後端 API

**風險**：低

---

#### 步驟 4-2：課程列表加入外部課程標記

**檔案**：`js/src/pages/admin/Courses/List/hooks/useColumns.tsx`

**行動**：
- 在「狀態」欄位旁新增「類型」欄位（或合併到狀態欄位中）
- 外部課程顯示 `<Tag color="blue">外部</Tag>` 標記
- 或在「商品名稱」欄位中，外部課程名稱旁加入小標記

**原因**：spec 後台列表標記要求後台課程列表可辨識外部課程。

**依賴**：步驟 4-1

**風險**：低

---

#### 步驟 4-3：新增課程按鈕支援選擇課程類型

**檔案**：`js/src/pages/admin/Courses/List/Table/index.tsx`

**行動**：
- 修改「新增課程」按鈕，改為 Dropdown 按鈕（或兩個按鈕）
- 選項一：「新增課程」（type: simple，原有行為）
- 選項二：「新增外部課程」（type: external）
- external 建立時傳送 `product_type: 'external'` 和預設 `external_url: 'https://'`（引導填寫）

**原因**：管理員需要能從後台建立外部課程。

**依賴**：步驟 4-1

**風險**：低

---

#### 步驟 4-4：外部課程編輯頁隱藏不適用的 Tab

**檔案**：`js/src/pages/admin/Courses/Edit/index.tsx`

**行動**：
- 從 `record` 取得 `product_type`（或 `type`）判斷是否為 external
- 若為 external，從 `items` 陣列中過濾掉以下 Tab：
  - `Chapters`（章節管理）
  - `CourseBundle`（銷售方案）
  - `CourseStudents`（學員管理）
  - `CourseAnalysis`（分析）
- 保留：`CourseDescription`、`CoursePrice`、`CourseQA`（選填）、`CourseOther`
- 隱藏「前往教室」按鈕（外部課程無教室）

**原因**：spec 外部課程功能隔離要求後台編輯頁隱藏不適用的 Tab。

**依賴**：步驟 4-1

**風險**：低

---

#### 步驟 4-5：外部課程編輯頁新增「外部連結」設定欄位

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CourseDescription/index.tsx`（或新增一個 Tab）

**行動**：
- 在課程描述 Tab 中（或新建「外部課程設定」區塊），當 `product_type === 'external'` 時顯示：
  - `external_url` 輸入欄位（Input，URL 類型，必填）
  - `button_text` 輸入欄位（Input，選填，placeholder 為「前往課程」）
- 表單送出時，`external_url` 和 `button_text` 作為 meta_data 傳送

**原因**：spec 後台編輯頁 UI 隔離要求外部課程編輯頁包含外部連結欄位。

**依賴**：步驟 4-4

**風險**：低

---

#### 步驟 4-6：外部課程價格 Tab 簡化

**檔案**：`js/src/pages/admin/Courses/Edit/tabs/CoursePrice/index.tsx`

**行動**：
- 當 `product_type === 'external'` 時：
  - 只顯示 `regular_price` 和 `price`（展示用）
  - 隱藏庫存管理欄位（StockFields）
  - 隱藏訂閱相關欄位
  - 隱藏存取期限（limit_type / limit_value / limit_unit）

**原因**：外部課程的價格為展示用途，不需要庫存和訂閱設定。

**依賴**：步驟 4-4

**風險**：低

---

### 第五階段：邊界情況與完善

> 執行 Agent：`@wp-workflows:wordpress-master`（PHP 部分）+ `@wp-workflows:react-master`（TS 部分）

#### 步驟 5-1：營收報表排除外部課程

**檔案**：`inc/classes/Api/Reports/Revenue/Api.php`

**行動**：
- 在營收報表查詢中，排除 `product_type = 'external'` 的課程
- 外部課程不可購買，理論上不會有訂單，但仍需防禦性排除

**原因**：spec 功能隔離要求外部課程不參與營收報表。

**依賴**：無

**風險**：低

---

#### 步驟 5-2：課程選項 API 排除外部課程（供學員管理/郵件模板使用）

**檔案**：`inc/classes/Api/Course.php` -- `get_courses_options_callback()` 方法

**行動**：
- 此 API 回傳課程選項給前端下拉選單（學員管理、郵件模板等使用）
- 新增參數或邏輯，排除 external 類型的課程
- 或在前端消費此 API 時過濾

**原因**：spec 功能隔離要求外部課程不出現在學員管理的課程篩選選項和郵件模板的課程選項中。

**依賴**：無

**風險**：低

---

#### 步驟 5-3：`format_course_records()`（單一課程詳情）加入 external 欄位

**檔案**：`inc/classes/Api/Course.php` -- `format_course_records()` 方法（約 L236-L321）

**行動**：
- 若產品為 `WC_Product_External`：
  - 跳過 chapters 查詢（外部課程無章節）
  - 跳過 bundle_ids 查詢（外部課程無銷售方案）
  - 跳過 limit 相關計算
  - 在 `$extra_array` 中加入 `external_url` 和 `button_text`

**原因**：避免不必要的資料庫查詢，同時確保單一課程詳情 API 也回傳 external 欄位。

**依賴**：步驟 1-4

**風險**：低

---

## 測試策略

> 此 section 供 tdd-coordinator 交給 test-creator 執行，planner 只需規劃「要測什麼」。

### PHP Integration Test

- **建立外部課程**：驗證 `POST /courses` 帶 `product_type=external` 正確建立 `WC_Product_External` 並設定 `_is_course=yes`
- **驗證 external_url 必填**：不帶 `external_url` 或帶非法 URL 應回傳 400
- **驗證 button_text 預設值**：不帶 `button_text` 時預設為「前往課程」
- **查詢篩選**：`GET /courses?product_type=external` 只回傳外部課程
- **功能隔離**：外部課程不可新增學員、不可新增章節、不可建立銷售方案
- **自動授權排除**：`auto_grant_course_ids` 包含外部課程 ID 時應被拒絕或跳過

### E2E Test

- **管理員建立外部課程**：從後台建立外部課程，驗證 Tab 隔離
- **前台課程列表**：外部課程卡片顯示 ↗ 標記
- **前台銷售頁**：外部課程 CTA 按鈕文字正確、target=_blank、不顯示加入購物車
- **購物車阻擋**：外部課程無法加入購物車

### 測試執行指令

```bash
composer run test                    # PHPUnit
pnpm run test:e2e:admin             # 管理端 E2E
pnpm run test:e2e:frontend          # 前台 E2E
```

### 關鍵邊界情況

- 建立外部課程後，嘗試透過 API 更新 `product_type` 為 `simple` -- 應被拒絕或忽略
- 建立外部課程後，external_url 更新為空字串 -- 應回傳 400
- 外部課程設定 `regular_price` > `price` 時的刪除線顯示
- 外部課程在 WC 傳統商品編輯頁的 checkbox 顯示
- 混合站內/外部課程的列表分頁正確性
- `auto_grant_courses` 中同時包含站內和外部課程 ID 時，只有站內課程被授權

## 風險與緩解措施

- **高**：`handle_save_course_meta_data()` 的 `wp_set_object_terms` 覆蓋問題 -- 緩解：步驟 1-3 中使用 `match` 根據產品實例判斷，加入完整的 PHPUnit 測試
- **中**：`separator()` 修改影響現有流程 -- 緩解：步驟 1-1 中只在 `!$product`（建立新產品）時才讀取 `product_type`，更新時不變
- **中**：前台模板多處需判斷產品類型 -- 緩解：統一使用 `$product instanceof \WC_Product_External` 判斷，不依賴 meta 值
- **低**：TanStack Query 快取 -- 緩解：新增欄位會自動包含在 query response 中，不需額外清除快取

## 成功標準

- [ ] 管理員可從後台建立外部課程，API 回傳正確的 `product_type=external`
- [ ] 外部課程 `external_url` 驗證通過（必填、合法 URL、http/https）
- [ ] `button_text` 未填時預設為「前往課程」
- [ ] 課程列表 API 支援 `product_type` 篩選
- [ ] 前台課程卡片外部課程顯示 ↗ 標記
- [ ] 前台銷售頁 CTA 按鈕以 `target="_blank" rel="noopener noreferrer"` 開啟外部連結
- [ ] 外部課程銷售頁不顯示章節列表、銷售方案
- [ ] 外部課程不可新增學員、章節、銷售方案
- [ ] 外部課程不出現在自動授權課程選項中
- [ ] 後台編輯頁外部課程隱藏章節管理、銷售方案、學員管理、分析 Tab
- [ ] 後台編輯頁顯示外部連結 / 按鈕文字輸入欄位
- [ ] 所有 Feature Spec 場景通過 E2E 測試
- [ ] 現有站內課程功能不受影響（回歸測試通過）
- [ ] `pnpm run lint:php` 和 `pnpm run lint:ts` 通過

## 限制條件

- 本計劃**不包含**外部課程的營收追蹤（外部課程不走站內購物流程）
- 本計劃**不包含**外部課程的學習進度追蹤（無教室/章節）
- 本計劃**不包含** product_type 轉換功能（建立後不可改變）
- 本計劃**不包含**外部課程的訂閱模式（`subscription` + `external` 組合不支援）
- 本計劃**不修改**自訂資料表 DDL（外部課程不使用 `pc_avl_coursemeta` 等表）

## 預估複雜度：中

主要複雜度集中在 `inc/classes/Api/Course.php` 的 `separator()` / `handle_save_course_meta_data()` 修改，以及前台模板的條件分支。前端 React 部分相對簡單，主要是 Tab 隔離和欄位新增。

## Agent 路由總結

| 階段 | 步驟 | 執行 Agent |
|------|------|-----------|
| 第一階段 | 1-1 ~ 1-5 | `@wp-workflows:wordpress-master` |
| 第二階段 | 2-1 ~ 2-5 | `@wp-workflows:wordpress-master` |
| 第三階段 | 3-1 ~ 3-6 | `@wp-workflows:wordpress-master` |
| 第四階段 | 4-1 ~ 4-6 | `@wp-workflows:react-master` |
| 第五階段 | 5-1 ~ 5-3 | 混合（PHP: `wordpress-master`, TS: `react-master`） |
