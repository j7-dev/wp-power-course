# 完整檔案清單 — Power Course JS（全 223 個檔案）

> 此文件由 `SKILL.md` 引用 — 需要查詢特定檔案路徑時載入。

## 完整檔案清單

以下為分析過的完整檔案清單，按目錄分類：

### 入口與設定 (6)
`main.tsx`, `App1.tsx`, `App2/index.tsx`, `App2/Player.tsx`, `App2/Ended.tsx`, `resources/index.tsx`

### 型別 (18)
`types/index.ts`, `types/index.d.ts`, `types/common.ts`, `types/dataProvider.ts`, `types/env.d.ts`, `types/global.d.ts`, `types/svg.d.ts`, `types/wcRestApi/index.ts`, `types/wcRestApi/product.ts`, `types/wcRestApi/common.ts`, `types/wcStoreApi/index.ts`, `types/wcStoreApi/product.ts`, `types/wcStoreApi/cart.ts`, `types/wpRestApi/index.ts`, `types/wpRestApi/post.ts`, `types/wpRestApi/common.ts`, `types/wpRestApi/taxonomy.ts`, `types/wpRestApi/image.ts`

### Hooks (7)
`hooks/index.tsx`, `hooks/useEnv.tsx`, `hooks/useUserFormDrawer.tsx`, `hooks/useProductSelect.tsx`, `hooks/useCourseSelect.tsx`, `hooks/useGCDItems.tsx`, `hooks/useEditorDrawer.tsx`

### 工具函數 (15)
`utils/index.tsx`, `utils/env.tsx`, `utils/constants.ts`, `utils/api/index.tsx`, `utils/functions/index.tsx`, `utils/functions/common.tsx`, `utils/functions/dayjs.ts`, `utils/functions/product.tsx`, `utils/functions/post.tsx`, `utils/functions/order.tsx`, `utils/functions/user.tsx`, `utils/functions/refine.tsx`, `utils/functions/actionScheduler.tsx`, `utils/wcStoreApi/index.tsx`, `utils/wcStoreApi/product.ts`

### 通用元件 (18)
`components/general/index.tsx`, `general/Gallery/index.tsx`, `general/ToggleContent/index.tsx`, `general/ToggleContent/useToggleContent.tsx`, `general/Upload/index.tsx`, `general/PopconfirmDelete/index.tsx`, `general/SaleRange/index.tsx`, `general/Heading/index.tsx`, `general/ListSelect/index.tsx`, `general/ListSelect/useListSelect.tsx`, `general/SecondToStr/index.tsx`, `general/SimpleImage/index.tsx`, `general/FileUpload/index.tsx`, `general/WatchStatusTag/index.tsx`, `general/Logo/index.tsx`, `general/WaterMark/index.tsx`, `general/DuplicateButton/index.tsx`, `general/PageLoading/index.tsx`

### 表單元素 (14)
`components/formItem/index.tsx`, `formItem/DatePicker/index.tsx`, `formItem/FiSwitch/index.tsx`, `formItem/RangePicker/index.tsx`, `formItem/VideoLength/index.tsx`, `formItem/WatchLimit/index.tsx`, `formItem/VideoInput/index.tsx`, `formItem/VideoInput/Bunny.tsx`, `formItem/VideoInput/Code.tsx`, `formItem/VideoInput/Iframe.tsx`, `formItem/VideoInput/Vimeo.tsx`, `formItem/VideoInput/Youtube.tsx`, `formItem/VideoInput/NoLibraryId.tsx`, `formItem/VideoInput/types/index.ts`

### 版面元件 (5)
`components/layout/index.tsx`, `layout/header.tsx`, `layout/layout.tsx`, `layout/sider.tsx`, `layout/title.tsx`

### 課程元件 (6)
`components/course/index.tsx`, `course/ChapterName/index.tsx`, `course/SortableChapters/index.tsx`, `course/SortableChapters/utils/index.tsx`, `course/SortableChapters/AddChapters/index.tsx`, `course/SortableChapters/NodeRender/index.tsx`

### 章節元件 (2)
`components/chapters/index.tsx`, `chapters/Edit.tsx`

### 商品元件 (24)
`components/product/index.tsx`, `product/ProductName/index.tsx`, `product/ProductPrice/index.tsx`, `product/ProductType/index.tsx`, `product/ProductTotalSales/index.tsx`, `product/ProductCat/index.tsx`, `product/ProductStock/index.tsx`, `product/ProductAction/index.tsx`, `product/ProductAction/ToggleVisibility/index.tsx`, `product/ProductVariationsSelector/index.tsx`, `product/ProductVariationsSelector/useProductVariationsSelector.tsx`, `product/BindCourses/index.tsx`, `product/UnbindCourses/index.tsx`, `product/UpdateBoundCourses/index.tsx`, `product/ProductBoundCourses/index.tsx`, `product/ProductTable/types/index.ts`, `product/ProductTable/utils/index.ts`, `product/ProductTable/utils/onSearch.ts`, `product/ProductTable/hooks/useOptions.tsx`, `product/ProductTable/hooks/useValueLabelMapper.tsx`, `product/ProductTable/Filter/index.tsx`, `product/ProductTable/Filter/FullFilter/index.tsx`, `product/ProductTable/Filter/MobileFilter/index.tsx`, `product/ProductTable/Filter/TermSelector/index.tsx`

### 使用者元件 (19)
`components/user/index.tsx`, `user/UserName/index.tsx`, `user/UserWatchLimit/index.tsx`, `user/UserDrawer/index.tsx`, `user/UserAvatarUpload/index.tsx`, `user/GrantCourseAccess/index.tsx`, `user/RemoveCourseAccess/index.tsx`, `user/ModifyCourseExpireDate/index.tsx`, `user/UserTable/index.tsx`, `user/UserTable/atom.tsx`, `user/UserTable/hooks/useColumns.tsx`, `user/UserTable/utils/index.tsx`, `user/UserTable/SelectedUser/index.tsx`, `user/UserTable/Filter/index.tsx`, `user/UserTable/CsvUpload/index.tsx`, `user/UserTable/HistoryDrawer/index.tsx`, `user/UserTable/HistoryDrawer/types.ts`, `user/UserTable/HistoryDrawer/adapter/index.tsx`, `user/UserTable/HistoryDrawer/adapter/TimelineItemAdapter.tsx`

### 郵件元件 (7)
`components/emails/index.tsx`, `emails/SendCondition/index.tsx`, `emails/SendCondition/Condition.tsx`, `emails/SendCondition/Specific.tsx`, `emails/SendCondition/Variables.tsx`, `emails/SendCondition/enum.ts`, `emails/SendCondition/hooks/index.tsx`

### 文章元件 (3)
`components/post/index.tsx`, `post/OnChangeUpload/index.tsx`, `post/FileUpload/index.tsx`

### 課程頁面 (34)
`pages/admin/Courses/atom/index.tsx`, `Courses/List/index.tsx`, `Courses/List/types/index.ts`, `Courses/List/types/user.ts`, `Courses/List/hooks/useColumns.tsx`, `Courses/List/hooks/useValueLabelMapper.tsx`, `Courses/List/Table/index.tsx`, `Courses/List/Table/DeleteButton/index.tsx`, `Courses/Edit/index.tsx`, `Courses/Edit/hooks/index.tsx`, `Courses/Edit/hooks/useCourse.tsx`, `Courses/Edit/hooks/useParseData.tsx`, `Courses/Edit/tabs/index.tsx`, `Courses/Edit/tabs/CourseDescription/index.tsx`, `Courses/Edit/tabs/CoursePrice/index.tsx`, `Courses/Edit/tabs/CoursePrice/ProductPriceFields/Simple.tsx`, `Courses/Edit/tabs/CoursePrice/ProductPriceFields/Subscription.tsx`, `Courses/Edit/tabs/CoursePrice/StockFields/index.tsx`, `Courses/Edit/tabs/CourseStudents/index.tsx`, `Courses/Edit/tabs/CourseStudents/UserSelector/index.tsx`, `Courses/Edit/tabs/CourseStudents/StudentTable/index.tsx`, `Courses/Edit/tabs/CourseStudents/AddOtherCourse/index.tsx`, `Courses/Edit/tabs/CourseBundles/index.tsx`, `Courses/Edit/tabs/CourseBundles/ListItem/index.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/index.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/atom.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/BundleForm.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/Gallery.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/ProductPriceFields/index.tsx`, `Courses/Edit/tabs/CourseBundles/Edit/utils/index.tsx`, `Courses/Edit/tabs/CourseAnalysis/index.tsx`, `Courses/Edit/tabs/CourseQA/index.tsx`, `Courses/Edit/tabs/CourseAnnouncement/index.tsx`, `Courses/Edit/tabs/CourseOther/index.tsx`

### 分析頁面 (11)
`pages/admin/Analytics/index.tsx`, `Analytics/Filter/index.tsx`, `Analytics/Filter/Tags.tsx`, `Analytics/hooks/index.tsx`, `Analytics/hooks/useRevenue.tsx`, `Analytics/hooks/useRevenueContext.tsx`, `Analytics/types/index.tsx`, `Analytics/utils/index.tsx`, `Analytics/ViewType/AreaView.tsx`, `Analytics/ViewType/DefaultView.tsx`, `Analytics/ViewType/LoadingCard.tsx`

### 郵件頁面 (9)
`pages/admin/Emails/types/index.ts`, `Emails/Edit/index.tsx`, `Emails/Edit/EmailEditor/index.tsx`, `Emails/List/index.tsx`, `Emails/List/hooks/useColumns.tsx`, `Emails/List/hooks/useAsColumns.tsx`, `Emails/List/Table/index.tsx`, `Emails/List/Table/DeleteButton/index.tsx`, `Emails/List/AsTable/index.tsx`

### 商品頁面 (5)
`pages/admin/Products/index.tsx`, `Products/ProductTable/index.tsx`, `Products/ProductTable/Table/index.tsx`, `Products/ProductTable/hooks/useColumns.tsx`, `Products/ProductTable/hooks/useValueLabelMapper.tsx`

### 學生 & 講師頁面 (5)
`pages/admin/Students/index.tsx`, `Teachers/index.tsx`, `Teachers/UserSelector/index.tsx`, `Teachers/TeacherTable/index.tsx`, `Teachers/TeacherTable/hooks/useColumns.tsx`

### 設定頁面 (6)
`pages/admin/Settings/index.tsx`, `Settings/types/index.ts`, `Settings/hooks/useSave.tsx`, `Settings/hooks/useSettings.tsx`, `Settings/General/index.tsx`, `Settings/Appearance/index.tsx`

### 短代碼頁面 (7)
`pages/admin/Shortcodes/index.tsx`, `Shortcodes/General/index.tsx`, `Shortcodes/General/Courses.tsx`, `Shortcodes/General/MyCourses.tsx`, `Shortcodes/Cart/index.tsx`, `Shortcodes/Cart/Simple.tsx`, `Shortcodes/Cart/Bundle.tsx`

### 媒體庫頁面 (2)
`pages/admin/MediaLibraryPage/index.tsx`, `pages/admin/BunnyMediaLibraryPage/index.tsx`
