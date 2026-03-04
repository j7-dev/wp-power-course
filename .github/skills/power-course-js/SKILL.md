---
name: power-course-js
description: >-
  Power Course 前端開發技能 — 涵蓋 React + TypeScript + Refine.dev + Ant Design 的 WordPress LMS 外掛前端架構、元件模式、API 整合慣例與編碼規範。此技能來自對 js/src/ 內全部 223 個 TypeScript/TSX 檔案的完整分析。
---

# Power Course 前端開發技能

> 本技能文件涵蓋 `js/src/` 目錄內所有 223 個 `.ts` / `.tsx` 檔案的完整分析。
> 適用場景：新增功能、修改現有元件、建立新頁面、整合 API、撰寫表單邏輯。

---
> **支援參考檔案**: [`references/types.md`](references/types.md) — 完整型別定義 ｜ [`references/file-list.md`](references/file-list.md) — 全 223 檔案清單

## 1. 專案架構總覽

### 1.1 雙應用入口

專案在 `main.tsx` 中掛載兩個獨立 React 應用：

| 應用 | 選擇器 | 用途 | 框架 |
|------|---------|------|------|
| **App1** | `APP1_SELECTOR` (多個) | 管理後台 SPA | Refine.dev + HashRouter |
| **App2** | `APP2_SELECTOR` (多個) | 影片播放器 | VidStack + 獨立狀態 |

```tsx
// main.tsx 掛載模式
document.querySelectorAll(APP1_SELECTOR).forEach(el => {
  createRoot(el).render(<App1 />)
})
document.querySelectorAll(APP2_SELECTOR).forEach(el => {
  createRoot(el).render(<App2 {...dataset} />)
})
```

### 1.2 Provider 堆疊 (App1)

```
QueryClientProvider
  └─ StyleProvider (antd cssinjs hashPriority="high")
      └─ EnvProvider (antd-toolkit)
          └─ BunnyProvider (antd-toolkit)
              └─ ConfigProvider (antd theme)
                  └─ Refine (dataProvider, resources, routerProvider)
                      └─ ThemedLayoutV2
                          └─ <Outlet /> (路由頁面)
```

### 1.3 QueryClient 設定

```tsx
{
  refetchOnWindowFocus: false,
  retry: 0,
  staleTime: 1000 * 60 * 10,  // 10 分鐘
  cacheTime: 1000 * 60 * 10,
}
```

### 1.4 七個 Data Provider

| 名稱 | 用途 | base URL |
|------|------|----------|
| `default` (powerhouse) | Powerhouse API | `/wp-json/powerhouse/` |
| `power-course` | 課程核心 API | `/wp-json/power-course/` |
| `power-email` | 郵件 API | `/wp-json/power-email/` |
| `wc-analytics` | WC 分析 API | `/wp-json/wc-analytics/` |
| `wp-rest` | WordPress REST | `/wp-json/wp/v2/` |
| `wc-rest` | WooCommerce REST | `/wp-json/wc/v3/` |
| `wc-store` | WC Store API | `/wp-json/wc/store/v1/` |
| `bunny-stream` | Bunny CDN | 外部 API |

### 1.5 路由結構 (HashRouter)

所有頁面使用 `React.lazy()` + `<Suspense fallback={<PageLoading />}>` 延遲載入：

```
#/courses          → CourseList
#/courses/edit/:id → CoursesEdit (tabs: 描述/價格/學生/銷售方案/分析/QA/公告/其他)
#/teachers         → Teachers
#/students         → Students
#/products         → Products
#/shortcodes       → Shortcodes
#/settings         → Settings
#/analytics        → Analytics
#/emails           → EmailsList
#/emails/edit/:id  → EmailEdit
#/media-library    → MediaLibraryPage
#/bunny-media-library → BunnyMediaLibraryPage
```

---

## 2. 型別系統

> 完整型別定義詳見 [`references/types.md`](references/types.md)

### 三個平行型別命名空間

| 命名空間 | 用途 | 主要型別 |
|----------|------|---------|
| `types/wcRestApi/` | WC REST API 後台管理 | `TProduct`, `TProductVariation`, `TMeta`, `TAttribute` |
| `types/wcStoreApi/` | WC Store API 前台商店 | `TProduct`, `TCart`, `TCartItem` |
| `types/wpRestApi/` | WP REST API 文章 | `TPost`, `TPostsArgs`, `TPagination`, `TImage` |

- **共用型別**: `THttpMethods`, `TOrderBy`, `TOrder`, `TDataProvider`
- **業務型別** (`pages/admin/Courses/List/types/`): `TCourseBaseRecord`, `TChapterRecord`, `TUserRecord`, `TExpireDate`
- **全域型別** (`types/global.d.ts`): `Window.appData.env`, `Window.wpApiSettings`

---

## 3. Hooks 慣例

### 3.1 環境變數 Hook

```tsx
// hooks/useEnv.tsx — 永遠用 useEnv() 存取環境變數，禁止直接讀 window
import { useEnv as useAntdToolkitEnv } from 'antd-toolkit'

export const useEnv = () => {
  const env = useAntdToolkitEnv()
  return {
    ...env,
    APP1_SELECTOR: env?.APP1_SELECTOR,
    ELEMENTOR_ENABLED: env?.ELEMENTOR_ENABLED,
    AXIOS_INSTANCE: env?.AXIOS_INSTANCE,
    // ... Power Course 專屬變數
  }
}
```

**重要**: 環境變數在 `utils/env.tsx` 中由 `simpleDecrypt(window.power_course_data.env)` 解密取得。

### 3.2 CRUD Drawer Hook

```tsx
// hooks/useUserFormDrawer.tsx — 表單 Drawer 完整 CRUD 模式
const { drawerProps, formProps, saveButtonProps, show, close } = useUserFormDrawer({
  action: 'create' | 'edit',
  resource: 'users',
})
// 特點：
// - multipart/form-data 上傳 (toFormData())
// - 未儲存變更偵測 (isEqual from lodash-es)
// - Popconfirm 關閉防護
// - 自動 invalidate 快取
```

### 3.3 Select Hook 模式

```tsx
// hooks/useProductSelect.tsx & hooks/useCourseSelect.tsx
const { selectProps } = useProductSelect()  // 或 useCourseSelect()
// 特點：
// - Refine useSelect 包裝
// - 伺服器端搜尋 (debounce 500ms)
// - dataProviderName: 'power-course'
// - 整合 antd-toolkit 的 defaultSelectProps
```

### 3.4 交集項目 Hook

```tsx
// hooks/useGCDItems.tsx — 計算多選項目的交集
const { GCDItems, renderGCDItems } = useGCDItems<TCourseRecord>({
  items: selectedUsers.map(u => u.avl_courses),
  rowKey: 'id',
})
// 用途：當選取多個使用者時，找出他們共同擁有的課程
```

### 3.5 編輯器 Drawer Hook

```tsx
// hooks/useEditorDrawer.tsx — Notion 風格編輯器 Drawer 狀態管理
const { editorDrawerProps } = useEditorDrawer()
```

---

## 4. 工具函數

### 4.1 常數定義 (utils/constants.ts)

所有 UI 標籤使用**繁體中文**：

```typescript
export const productTypes = [
  { value: 'simple', label: '簡單商品' },
  { value: 'variable', label: '可變商品' },
  { value: 'subscription', label: '簡易訂閱' },
  { value: 'variable-subscription', label: '可變訂閱' },
]

export const postStatus = [
  { value: 'publish', label: '發佈' },
  { value: 'draft', label: '草稿' },
  // ...
]
```

### 4.2 狀態映射器 (utils/functions/)

```typescript
// 所有 mapper 回傳 { label: string, color: string } 供 Ant Design Tag 使用
getOrderStatus(status)  // WC 訂單狀態 → Tag 屬性
getPostStatus(status)   // WP 文章狀態 → Tag 屬性
getASStatus(status)     // Action Scheduler 狀態 → Tag 屬性
```

### 4.3 日期處理 (utils/functions/dayjs.ts)

```typescript
// 專用 dayjs — 處理 10 位 (秒) 和 13 位 (毫秒) 時間戳
parseDatePickerValue(value)  // 將各種日期格式轉為 dayjs
getDateRangeProps()          // 取得日期範圍選擇器的 disabled 規則
```

### 4.4 影片 URL 解析

```typescript
getYoutubeVideoId(url)  // 支援 youtu.be 和 youtube.com 格式
getVimeoVideoId(url)    // 支援標準 Vimeo URL
```

### 4.5 Refine 輔助工具

```typescript
getInitialFilters(params: Record<string, any>): CrudFilters
// 將 key-value 物件轉為 Refine CrudFilters 格式
```

### 4.6 幣值

預設幣值為 **NT$（新台幣）**，在顯示價格時使用。

---

## 5. 元件設計模式

### 5.1 元件分類架構

```
components/
├── general/      → 通用 UI 元件（與業務無關）
├── formItem/     → 表單元素包裝器（Form.Item 整合）
├── layout/       → 版面元件（Header, Sider, Layout）
├── course/       → 課程管理元件（章節排序、編輯）
├── chapters/     → 章節編輯元件
├── product/      → 商品展示元件（價格、庫存、分類）
├── user/         → 使用者管理元件（表格、存取權限）
├── emails/       → 郵件發送條件設定
└── post/         → 文章上傳元件
```

### 5.2 Barrel Export 模式

每個目錄都有 `index.tsx` 作為 barrel export：

```tsx
// components/general/index.tsx
export * from './Gallery'
export * from './ToggleContent'
export * from './Upload'
// ... 所有子元件
```

### 5.3 Memoization 模式

效能敏感的元件使用 `React.memo()`：

```tsx
export const MyComponent = memo(({ record }: TProps) => {
  // ...
})
```

常見使用場景：表格列渲染器、篩選器、大量資料展示元件。

### 5.4 通用元件模式

#### Gallery — 圖片畫廊

```tsx
<Gallery images={['url1', 'url2']} selectedImage="url1" />
// 主圖 + 縮圖選擇器，預設圖片 fallback
```

#### PopconfirmDelete — 刪除確認

```tsx
<PopconfirmDelete
  popconfirmProps={{ onConfirm: handleDelete }}
  type="icon" | "button"
/>
// 預設中文: '確認刪除嗎?', '確認', '取消'
```

#### PageLoading — 頁面載入

```tsx
<PageLoading type="empty" />  // 空片段（避免重複動畫）
<PageLoading type="general" /> // 置中 Spin + "LOADING..."
```

#### DuplicateButton — 複製按鈕

```tsx
<DuplicateButton id={recordId} invalidateProps={{ resource: 'courses' }} />
// POST /duplicate/{id}，成功後 invalidate 快取
```

#### WaterMark — 浮水印

```tsx
<WaterMark qty={5} text="使用者名稱" interval={30} isPlaying={true} />
// 隨機位置浮水印，每 interval 秒更新位置
```

#### ListSelect — 可搜尋多選列表

```tsx
<ListSelect
  listSelectProps={useListSelect({ resource: 'users', searchField: 's' })}
  rowName="display_name"
  rowUrl="user_avatar_url"
/>
// 泛型元件，支援 Refine BaseRecord
// 搜尋用中文提示: "請輸入關鍵字後按下 ENTER 搜尋，每次最多返回 20 筆資料"
```

### 5.5 表單元素包裝器模式 (formItem/)

所有表單元素都是 `Form.Item` 的包裝器，提供值轉換：

```tsx
// FiSwitch — boolean ↔ 'yes'/'no' 轉換
<FiSwitch formItemProps={{ name: 'is_active', label: '啟用' }} />

// DatePicker — 自動處理 unix timestamp
<DatePicker formItemProps={{ name: 'expire_date' }} />

// VideoInput — 多平台影片輸入（youtube/vimeo/bunny/code）
<VideoInput />  // 根據選擇的類型渲染對應的子元件

// VideoLength — 秒數 ↔ 時分秒轉換
<VideoLength />  // 三個 InputNumber: 時/分/秒

// WatchLimit — 觀看期限設定
<WatchLimit />  // 四種模式: unlimited/fixed/assigned/follow_subscription
```

#### VideoInput 子元件架構

```
VideoInput/
├── index.tsx       → 主元件（Select 選擇類型）
├── Iframe.tsx      → 抽象 iframe 元件（URL 解析 + 預覽）
├── Youtube.tsx     → YouTube 包裝器（extends Iframe）
├── Vimeo.tsx       → Vimeo 包裝器（extends Iframe）
├── Bunny.tsx       → Bunny CDN 整合（MediaLibraryModal）
├── Code.tsx        → 自訂 HTML/iframe 代碼輸入
├── NoLibraryId.tsx → Bunny 未設定提示
└── types/index.ts  → TVideoType, TVideo
```

### 5.6 版面元件 (layout/)

- **ThemedLayoutV2**: 主版面包裝器（min-height: 100vh）
- **ThemedHeaderV2**: 固定頂部 header（顯示使用者資訊）
- **ThemedSiderV2**: 367 行 — 遞迴選單渲染 (`renderTreeView`)、手機 Drawer 模式、可收合側邊欄
- **ThemedTitleV2**: 品牌標題（收合時只顯示圖標）

### 5.7 課程章節元件

#### SortableChapters — 可排序章節樹

```tsx
<SortableChapters />
// 特點：
// - 拖曳排序（dnd-kit SortableTree）
// - 最大深度 MAX_DEPTH = 2
// - 批次刪除（checkbox 多選含子節點）
// - 儲存至 /chapters/sort API
// - 保留展開/收合狀態
```

#### ChapterEdit — 章節編輯表單

```tsx
<ChapterEdit record={chapter} />
// Refine useForm，欄位：名稱、slug、描述（Drawer）、影片輸入、時長、留言開關
// 自訂 onFinish 將資料轉為 FormData
```

### 5.8 商品元件模式

| 元件 | 用途 | 關鍵模式 |
|------|------|----------|
| `ProductName` | 顯示商品圖片+名稱+ID+SKU | 泛型 `<T extends TBaseRecord>` |
| `ProductPrice` | 渲染 price_html | `renderHTML()` |
| `ProductType` | 商品類型標籤+特色星號+虛擬/可下載圖標 | 條件渲染 |
| `ProductStock` | 庫存狀態（色碼圖標） | `getTagProps()` 映射 |
| `ProductTotalSales` | 銷售等級徽章 (tier 1-5) | 相對百分比計算 |
| `ProductCat` | 分類藍標籤+標籤灰文字 | Ant Design Tag |
| `ProductAction` | 操作按鈕：複製/教室/銷售頁/可見性 | 使用 useEnv() |
| `BindCourses` | 綁定課程到商品 | POST /products/bind-courses |
| `UnbindCourses` | 解綁課程 | PopconfirmDelete 包裝 |
| `UpdateBoundCourses` | 更新綁定課程觀看期限 | TCoursesLimit 表單 |
| `ProductBoundCourses` | 顯示已綁定課程列表 | Tag 渲染 |
| `ProductVariationsSelector` | 商品變體屬性選擇器 | 屬性按鈕 + 匹配邏輯 |

### 5.9 使用者元件模式

#### UserTable — 使用者管理表格（251 行）

```tsx
<UserTable />
// 特點：
// - Jotai atoms: selectedUserIdsAtom, historyDrawerAtom
// - 跨分頁多選持久化
// - GCD 交集運算（共同課程）
// - CSV 上傳批次匯入
// - 學習歷程 Drawer (Timeline)
```

#### HistoryDrawer — 學習歷程時間軸

```tsx
// TimelineItemAdapter 類別映射 10 種日誌類型：
// COURSE_GRANTED, COURSE_FINISH, COURSE_LAUNCH, CHAPTER_ENTER,
// CHAPTER_FINISH, ORDER_CREATED, CHAPTER_UNFINISHED, COURSE_REMOVED, UPDATE_STUDENT
// 每種類型有對應的顏色和圖標
```

#### 存取權限操作

```tsx
<GrantCourseAccess user_ids={ids} />     // POST /courses/add-students
<RemoveCourseAccess user_ids={ids} course_ids={cids} />  // POST /courses/remove-students
<ModifyCourseExpireDate user_ids={ids} course_ids={cids} /> // POST /courses/update-students
```

### 5.10 郵件元件

#### SendCondition — 發信條件設定

```tsx
<SendCondition email_ids={ids} />
// 兩個 Tab：
// 1. Condition — 觸發條件 (COURSE_GRANTED/FINISH/LAUNCH, CHAPTER_FINISH/ENTER)
//    - 觸發條件: EACH/ALL/QUANTITY_GREATER_THAN
//    - 發送方式: NOW/LATER (可設延遲天數)
// 2. Specific — 手動發送給指定使用者（Modal 選人 + 排程）
```

#### 郵件列舉型別

```typescript
enum TriggerAt {
  COURSE_GRANTED, COURSE_FINISH, COURSE_LAUNCH,
  CHAPTER_FINISH, CHAPTER_ENTER, ORDER_CREATED,
  CHAPTER_UNFINISHED, COURSE_REMOVED, UPDATE_STUDENT
}
enum TriggerCondition { EACH, ALL, QUANTITY_GREATER_THAN }
enum SendingType { NOW, LATER }
enum SendingUnit { DAY, HOUR, MINUTE }
```

### 5.11 上傳元件雙模式

| 元件 | 模式 | 用途 |
|------|------|------|
| `post/OnChangeUpload` | 即時上傳 | 選擇後立即上傳到伺服器，ImgCrop 16:9 |
| `post/FileUpload` | 本地暫存 | 選擇後 base64 預覽，批次提交時才上傳 |

---

## 6. 頁面層模式

### 6.1 課程列表頁 (pages/admin/Courses/List/)

```tsx
// Table 使用 Refine useTable，整合篩選與批次刪除
// DeleteButton: 確認 Modal 需輸入文字驗證（防誤刪）
// useColumns: 定義 Ant Design Table columns 含自訂渲染器
// useValueLabelMapper: 篩選標籤顯示映射
```

### 6.2 課程編輯頁 (pages/admin/Courses/Edit/)

使用 Tab 切換的多面板編輯介面：

| Tab | 元件 | 功能 |
|-----|------|------|
| 課程描述 | `CourseDescription` | 名稱、slug、分類、媒體、講師 (ListSelect) |
| 價格設定 | `CoursePrice` | 定價、特價、庫存、觀看期限 |
| 學生管理 | `CourseStudents` | 新增/移除/更新學生、匯出 CSV |
| 銷售方案 | `CourseBundles` | Bundle 商品管理（可排序列表） |
| 分析報表 | `CourseAnalysis` | 包裝 Analytics 頁面 |
| QA 管理 | `CourseQA` | 可排序 Q&A 列表 (Collapse UI) |
| 公告 | `CourseAnnouncement` | 待實作 (stub) |
| 其他設定 | `CourseOther` | 顯示/可見性/評價/評論/UI 選項 |

#### 狀態管理模式

```tsx
// React Context — 課程資料
const RecordContext = createContext<TCourseRecord | undefined>(undefined)
export const useRecord = () => useContext(RecordContext)

// React Context — 表單資料轉換
const ParseDataContext = createContext<TParseDataFn>(defaultFn)
export const useParseData = () => useContext(ParseDataContext)

// Jotai Atom — MediaLibrary Modal 狀態
export const mediaLibraryAtom = atom<TMediaLibraryAtomProps>(defaultProps)
```

### 6.3 銷售方案編輯 (CourseBundles/Edit/)

```tsx
// 複雜的 Bundle 商品編輯表單
// - Jotai atoms: selectedProductsAtom, courseAtom, bundleProductAtom
// - BundleForm (392 行): 商品搜尋選擇、價格計算、圖片畫廊
// - Gallery (176 行): 圖片上傳/管理含 MediaLibrary Modal
// - ProductPriceFields: 簡單/訂閱定價欄位
// - utils: BUNDLE_TYPE_OPTIONS, getPrice() 價格計算
```

### 6.4 Analytics 分析頁面

```tsx
// Context-driven 設計：
// RevenueContext → useRevenue() → 圖表元件
//
// 視覺化：
// - DefaultView: 多指標折線圖 + 趨勢卡片 + 年度比較
// - AreaView: 堆疊面積圖
// - LoadingCard: 骨架屏
//
// 篩選器：
// - 日期範圍 + 預設快捷鍵
// - 商品選擇 (Tag 點擊)
// - 時間間隔 (日/週/月)
// - 年度比較開關
```

### 6.5 郵件頁面

```tsx
// Edit: MJML 郵件編輯器整合 (j7-easy-email-editor)
// - 延遲載入 (React.lazy)
// - 圖片上傳、區塊管理
// - 狀態切換 (publish/draft)
//
// List: Tab 切換
// - 郵件模板表格 (批次刪除、複製)
// - Action Scheduler 日誌表格
```

### 6.6 設定頁面

```tsx
// Settings: Tab 結構
// - General: 課程存取觸發、浮水印 (影片/PDF)、永久連結
// - Appearance: 手機 UI 修正、我的帳戶顯示、商店篩選
//
// 模式：useSave() 自訂 mutation → 成功後 page reload
```

### 6.7 Shortcodes 短代碼頁面

```tsx
// 即時短代碼產生器 + 預覽
// [pc_courses] — 課程列表短代碼 (220 行，含即時 API 預覽)
// [pc_my_courses] — 我的課程短代碼
// [pc_simple_card] — 簡單商品卡片短代碼
// [pc_bundle_card] — 組合商品卡片短代碼
//
// 模式：表單驅動屬性建構 + 複製到剪貼簿
```

### 6.8 商品、學生、講師頁面

```tsx
// Products: ProductTable 包裝 + Jotai atom + GCD 課程綁定
// Students: UserTable 包裝 + GrantCourseAccess
// Teachers: TeacherTable + UserSelector (批次新增) + Drawer 表單
```

### 6.9 媒體庫頁面

```tsx
// MediaLibraryPage: WordPress 媒體庫 (TAttachment, TImage)
// BunnyMediaLibraryPage: Bunny CDN 影片庫 (TBunnyVideo)
// 共同模式：狀態管理選取 + 無限選取
```

---

## 7. App2: VidStack 影片播放器

```tsx
// App2/Player.tsx — VidStack 播放器
// 特點：
// - 浮水印覆蓋 (WaterMark 元件)
// - 自動播放/靜音邏輯
// - 章節自動前進 (Ended 元件)
// - HLS 串流支援
// - 播放事件追蹤

// App2/Ended.tsx — 自動前進倒數
// - 10 秒倒數覆蓋
// - 導航至下一章節
// - 可取消
```

---

## 8. 狀態管理策略

### 8.1 Jotai (全域跨元件狀態)

```tsx
// 使用場景：跨多個不相關元件的狀態共享
import { atom, useAtom } from 'jotai'

// 已使用的 atoms：
selectedUserIdsAtom     // 使用者多選狀態
historyDrawerAtom       // 學習歷程 Drawer 開關
mediaLibraryAtom        // 媒體庫 Modal 狀態
selectedProductsAtom    // Bundle 商品選取
courseAtom              // Bundle 編輯的課程
bundleProductAtom       // Bundle 編輯的商品
```

### 8.2 React Context (頁面/元件樹狀態)

```tsx
// 使用場景：父子元件間的資料傳遞
RecordContext       // 課程記錄
ParseDataContext    // 表單資料轉換函數
RevenueContext      // 分析資料
```

### 8.3 Refine Hooks (伺服器狀態)

```tsx
// 資料讀取
useList()        // 列表查詢
useOne()         // 單筆查詢
useCustom()      // 自訂查詢
useSelect()      // 下拉選項查詢

// 資料變更
useCreate()      // 新增
useUpdate()      // 更新
useDelete()      // 刪除
useDeleteMany()  // 批次刪除
useCustomMutation() // 自訂變更

// 快取管理
useInvalidate()  // 手動 invalidate 快取
```

---

## 9. 編碼慣例與規範

### 9.1 檔案命名

- 元件檔案：PascalCase 目錄 + `index.tsx`（如 `Gallery/index.tsx`）
- Hook 檔案：camelCase（如 `useColumns.tsx`）
- 型別檔案：`types/index.ts`
- 工具函數：camelCase（如 `common.tsx`）
- Enum 檔案：camelCase（如 `enum.ts`）
- Adapter 類別：PascalCase（如 `TimelineItemAdapter.tsx`）

### 9.2 Import 路徑

```tsx
import { xxx } from '@/'       // 路徑別名 → js/src/
import { xxx } from 'antd-toolkit'  // antd-toolkit UI 工具
import { xxx } from '@refinedev/core'  // Refine.dev hooks
import { xxx } from 'antd'     // Ant Design 元件
import { xxx } from 'jotai'    // Jotai 狀態管理
import { xxx } from 'lodash-es' // Lodash 工具
import dayjs from 'dayjs'      // 日期處理
```

### 9.3 格式化規範

- **縮排**: Tabs
- **引號**: 單引號
- **分號**: 不使用
- **ESLint + Prettier**: 統一格式化

### 9.4 語言

- 所有 UI 標籤、提示訊息、表單文字：**繁體中文**
- 程式碼註解：繁體中文
- 變數命名：英文
- API 端點：英文

### 9.5 Refine API 呼叫慣例

```tsx
// 讀取資料 — 永遠指定 dataProviderName
const { data } = useList({
  resource: 'courses',
  dataProviderName: 'power-course',  // ← 必要
})

// 變更資料
const { mutate } = useCreate()
mutate({
  resource: 'chapters',
  values: formData,
  dataProviderName: 'power-course',
})

// 自訂端點
const apiUrl = useApiUrl('power-course')
const { mutate } = useCustomMutation()
mutate({
  url: `${apiUrl}/courses/add-students`,
  method: 'post',
  values: { user_ids, course_ids },
})
```

### 9.6 表單資料提交模式

```tsx
// 簡單 JSON 提交
onFinish(values)

// multipart/form-data 提交（含檔案上傳）
import { toFormData } from 'antd-toolkit'
onFinish(toFormData(values))
```

### 9.7 Select 元件基礎 Props

```tsx
import { defaultSelectProps } from 'antd-toolkit'
<Select {...defaultSelectProps} {...selectProps} />
```

### 9.8 表格篩選模式

```tsx
// 1. 定義篩選型別 TFilterProps
// 2. onSearch() 函數轉換為 CrudFilters
// 3. Filter 元件使用 Form + Grid 布局
// 4. 響應式：< 810px 使用 Drawer，否則 inline
// 5. useValueLabelMapper 映射標籤顯示
```

### 9.9 批次刪除模式

```tsx
// DeleteButton 元件：
// 1. 確認 Modal 彈出
// 2. 使用者輸入驗證文字
// 3. useDeleteMany mutation
// 4. 成功後清除選取狀態
```

### 9.10 頁面結構模式

```tsx
// 列表頁模板：
export const ListPage = () => {
  const { tableProps, searchFormProps } = useTable({ ... })
  return (
    <>
      <Filter searchFormProps={searchFormProps} />
      <Table {...tableProps} columns={columns} />
    </>
  )
}

// 編輯頁模板：
export const EditPage = () => {
  const { formProps, saveButtonProps } = useForm({ ... })
  return (
    <Edit saveButtonProps={saveButtonProps}>
      <Form {...formProps}>
        <Tabs items={tabItems} />
      </Form>
    </Edit>
  )
}
```

---

## 10. 第三方依賴

| 套件 | 用途 |
|------|------|
| `@refinedev/core` | 資料管理框架 |
| `@refinedev/antd` | Refine + Ant Design 整合 |
| `antd` | UI 元件庫 |
| `antd-toolkit` | 自訂 Ant Design 工具 (useEnv, toFormData, renderHTML, cn, defaultSelectProps, MediaLibraryModal, DateTime, FilterTags) |
| `@tanstack/react-query` | 伺服器狀態管理 |
| `jotai` | 原子化狀態管理 |
| `lodash-es` | 工具函數 (isEqual, differenceBy, get) |
| `dayjs` | 日期處理 |
| `react-icons` | 圖標套件 |
| `@uidotdev/usehooks` | React hooks 工具 (useWindowSize) |
| `@dnd-kit/core` | 拖曳排序 |
| `vidstack` | 影片播放器 (App2) |
| `hls.js` | HLS 串流 |
| `recharts` | 圖表 (Analytics) |
| `j7-easy-email-editor` | 郵件編輯器 |
| `mjml` | 郵件模板轉換 |
| `nanoid` | 唯一 ID 產生 |
| `react-router-dom` | 路由 (HashRouter) |

---

## 11. 常見開發任務指南

### 新增一個管理頁面

1. 在 `pages/admin/` 建立目錄
2. 建立 `index.tsx` 主元件
3. 在 `App1.tsx` 加入 `React.lazy()` 路由
4. 在 `resources/index.tsx` 加入 Refine resource 定義
5. 如需篩選：建立 `Filter/`、`hooks/useColumns.tsx`、`types/index.ts`

### 新增一個表單元素包裝器

1. 在 `components/formItem/` 建立目錄
2. 元件需包裝 `Form.Item`，處理 `getValueProps` 和 `normalize` 值轉換
3. 在 `formItem/index.tsx` 加入 re-export

### 新增一個業務元件

1. 在對應的 `components/` 子目錄建立
2. 使用 `memo()` 包裝效能敏感元件
3. Props 定義 TypeScript 介面
4. 在 barrel export (`index.tsx`) 加入 re-export
5. 使用 Refine hooks 進行 API 互動
6. 所有 UI 文字使用繁體中文

### 新增一個 Hook

1. 在 `hooks/` 或對應頁面的 `hooks/` 目錄建立
2. 命名慣例：`use{Feature}.tsx`
3. 回傳物件（props 形式，方便 spread 到元件）
4. 如與 Refine 整合，指定 `dataProviderName: 'power-course'`

---

## 12. 檔案清單（全 223 個檔案）

> 完整分類檔案清單詳見 [`references/file-list.md`](references/file-list.md)

涵蓋範圍（按目錄分類）：入口(6)、型別(18)、Hooks(7)、工具函數(15)、通用元件(18)、表單元素(14)、版面元件(5)、課程元件(6)、章節元件(2)、商品元件(24)、使用者元件(19)、郵件元件(7)、文章元件(3)、課程頁面(34)、分析頁面(11)、郵件頁面(9)、商品頁面(5)、學生&講師頁面(5)、設定頁面(6)、短代碼頁面(7)、媒體庫頁面(2)

---

*此技能文件由對 js/src/ 目錄內全部 223 個 TypeScript/TSX 檔案的完整分析產生。*
