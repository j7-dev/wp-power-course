@ignore @frontend
Feature: 前台展示外部課程

  外部課程在前台課程列表中的卡片樣式與站內課程一致，
  但有細微外部標記（↗ 圖示）。點擊卡片進入站內銷售頁，
  銷售頁顯示「前往課程」按鈕（管理員可自訂文字），
  點擊以新視窗開啟外部連結。

  Background:
    Given 系統中有以下課程：
      | courseId | name            | product_type | status  | price | external_url                   | button_text |
      | 100      | PHP 基礎課      | simple       | publish | 1200  |                                |             |
      | 200      | Python 資料科學 | external     | publish | 2400  | https://hahow.in/courses/12345 | 前往 Hahow  |
      | 201      | UX 設計入門     | external     | publish |       | https://pressplay.cc/courses/1 | 前往課程    |

  # ========== 課程列表卡片 ==========

  Rule: 課程列表 - 外部課程卡片樣式與站內課程一致

    Example: 外部課程卡片顯示名稱、封面圖、價格
      When 訪客瀏覽課程列表頁
      Then 課程 "Python 資料科學" 的卡片應顯示課程名稱
      And 課程 "Python 資料科學" 的卡片應顯示封面圖
      And 課程 "Python 資料科學" 的卡片應顯示價格 "NT$ 2,400"

  Rule: 課程列表 - 外部課程卡片有細微外部標記

    Example: 外部課程卡片右上角顯示外部連結圖示
      When 訪客瀏覽課程列表頁
      Then 課程 "Python 資料科學" 的卡片應顯示外部連結圖示 "↗"
      And 課程 "PHP 基礎課" 的卡片不應顯示外部連結圖示

  Rule: 課程列表 - 點擊外部課程卡片進入站內銷售頁

    Example: 點擊外部課程卡片連到站內頁面
      When 訪客點擊課程 "Python 資料科學" 的卡片
      Then 瀏覽器應導航至課程 200 的站內銷售頁
      And 不應開啟新視窗

  # ========== 銷售頁 ==========

  Rule: 銷售頁 - 外部課程顯示管理員自訂的 CTA 按鈕文字

    Example: 銷售頁顯示自訂按鈕文字
      When 訪客瀏覽課程 200 的銷售頁
      Then 頁面應顯示 CTA 按鈕文字 "前往 Hahow"
      And 頁面不應顯示「加入購物車」按鈕
      And 頁面不應顯示「立即報名」按鈕

    Example: 未自訂按鈕文字的外部課程顯示預設文字
      When 訪客瀏覽課程 201 的銷售頁
      Then 頁面應顯示 CTA 按鈕文字 "前往課程"

  Rule: 銷售頁 - CTA 按鈕以新視窗開啟外部連結

    Example: 點擊 CTA 按鈕開啟新視窗
      When 訪客瀏覽課程 200 的銷售頁
      Then CTA 按鈕的 href 應為 "https://hahow.in/courses/12345"
      And CTA 按鈕應有 target="_blank" 屬性
      And CTA 按鈕應有 rel="noopener noreferrer" 屬性

  Rule: 銷售頁 - 外部課程不顯示銷售方案區塊

    Example: 外部課程銷售頁隱藏銷售方案
      When 訪客瀏覽課程 200 的銷售頁
      Then 頁面不應顯示銷售方案區塊

  Rule: 銷售頁 - 外部課程顯示課程介紹

    Example: 外部課程銷售頁正常顯示介紹內容
      When 訪客瀏覽課程 200 的銷售頁
      Then 頁面應顯示課程介紹
      And 頁面應顯示封面圖

  Rule: 銷售頁 - 外部課程不顯示章節列表

    Example: 外部課程銷售頁隱藏章節資訊
      When 訪客瀏覽課程 200 的銷售頁
      Then 頁面不應顯示章節列表區塊
