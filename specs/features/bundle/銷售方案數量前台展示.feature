@readmodel
Feature: 銷售方案數量前台展示

  前台課程銷售頁的銷售方案卡片，在商品數量 > 1 時顯示 ×N 標記，
  數量 = 1 時不特別標示，保持簡潔。

  Background:
    Given 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 系統中有以下商品：
      | productId | name          | status  |
      | 200       | Python 講義   | publish |
      | 201       | React T-shirt | publish |

  # ========== 數量 > 1 顯示 ×N ==========

  Rule: 商品數量 > 1 時，在商品名稱後方顯示 "×N"

    Example: 數量為 3 的商品顯示 ×3
      Given 系統中有銷售方案 "超值學習包"，包含：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案 "超值學習包" 中：
        | 商品名稱     | 數量顯示 |
        | PHP 基礎課   | （無）   |
        | Python 講義  | ×3       |

    Example: 多個商品有不同數量
      Given 系統中有銷售方案 "豪華學習包"，包含：
        | product_id | qty |
        | 100        | 2   |
        | 200        | 3   |
        | 201        | 1   |
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案 "豪華學習包" 中：
        | 商品名稱      | 數量顯示 |
        | PHP 基礎課    | ×2       |
        | Python 講義   | ×3       |
        | React T-shirt | （無）   |

  # ========== 數量 = 1 不顯示 ==========

  Rule: 商品數量 = 1 時，不顯示數量標記

    Example: 所有商品數量為 1 時不顯示任何 ×N
      Given 系統中有銷售方案 "基本方案"，包含：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 1   |
      When 學員瀏覽課程 100 的銷售頁
      Then 銷售方案 "基本方案" 中所有商品不顯示 ×N 標記
