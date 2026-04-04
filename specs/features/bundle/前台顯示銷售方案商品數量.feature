@ignore @query
Feature: 前台顯示銷售��案商品數量

  前台銷售方案卡片中，數量 > 1 的商品在名稱後顯示灰色 "×N" 文字。
  數量 = 1 的商品不顯示數量標示（保持現有外觀）。
  無 pbp_product_quantities meta 時所有商品視為數量 1（不顯示標���）。

  Background:
    Given 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100     | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下商品：
      | productId | name       | type   | status  | regular_price |
      | 200       | T-shirt    | simple | publish | 200           |
      | 300       | React 課程 | simple | publish | 5000          |

  # ========== 數量標示 ==========

  Rule: 前台顯示 - 數量 > 1 的商品在名稱後顯示 "×N" 灰色文字

    Example: 數量 > 1 的商品顯示數量標示
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price | status  |
        | 500      | 超值套餐 | bundle      | 100            | 6000  | publish |
      And 銷售方案 500 包含以下商品及數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案卡片中 "PHP 基礎課" 後方不顯示數量標示
      And 銷售方案卡片中 "T-shirt" 後方顯示灰色文字 "×3"
      And 銷售���案卡片中 "React 課程" 後方不顯示數量標示

  Rule: 前台顯示 - 數量 = 1 的商品不顯示 "×1"（保持現有外觀）

    Example: 所有商品數量為 1 時無任何數量標示
      Given 課程 100 有以下銷售方案��
        | bundleId | name     | bundle_type | link_course_id | price | status  |
        | 501      | 基礎方案 | bundle      | 100            | 3000  | publish |
      And 銷售方案 501 包含以下商品及數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 1        |
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案卡片中所有商品名稱後方不顯示任何數量標示

  # ========== 向下相容 ==========

  Rule: 前台顯示 - 無 pbp_product_quantities 的舊方案不顯示數量標示

    Example: 舊銷售方案前台顯示與升級前一致
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price | status  |
        | 502      | 舊方���   | bundle      | 100            | 2000  | publish |
      And 銷售方案 502 包含以下商品：
        | productId |
        | 100       |
        | 200       |
      And 銷售方案 502 沒有 pbp_product_quantities meta
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案卡片中所有商品名稱後方不顯示任何數量標示

  # ========== 數量標示格式 ==========

  Rule: 前台顯示 - 數量標示格式為商品名稱緊接灰色 "×N"

    Example: 數量標示的 HTML 結構
      Given 課程 100 有以下銷售方案：
        | bundleId | name     | bundle_type | link_course_id | price | status  |
        | 500      | 超值套餐 | bundle      | 100            | 6000  | publish |
      And 銷售��案 500 包含以下商品及數量：
        | productId | quantity |
        | 200       | 3        |
      When 學員瀏覽課程 100 的銷售頁
      Then T-shirt 的顯示格式為：
        """
        T-shirt <span class="text-gray-400 text-xs ml-1">×3</span>
        """
