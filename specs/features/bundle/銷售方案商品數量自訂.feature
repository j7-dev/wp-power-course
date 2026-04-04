@command
Feature: 銷售方案商品數量自訂

  管理員可以在銷售方案編輯畫面中，為每個包含的商品設定數量（1~999），
  數量影響原價計算、前台顯示、訂單明細與庫存扣減。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100      | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下商品：
      | productId | name          | status  | regular_price | stock_quantity |
      | 200       | Python 講義   | publish | 500           | 100            |
      | 201       | React T-shirt | publish | 800           | 50             |

  # ========== 建立銷售方案含自訂數量 ==========

  Rule: 建立銷售方案時可為每個商品設定數量，數量以 pbp_product_quantities JSON meta 儲存

    Example: 成功建立含自訂數量的銷售方案
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | price | bundle_type   |
        | 超值學習包 | 100            | 2999  | single_course |
      And 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
        | 201        | 2   |
      Then 操作成功
      And 銷售方案的 pbp_product_ids 應包含 [100, 200, 201]
      And 銷售方案的 pbp_product_quantities 應為 {"100": 1, "200": 3, "201": 2}

    Example: 未指定數量時預設為 1
      When 管理員 "Admin" 建立銷售方案，參數如下：
        | name       | link_course_id | price | bundle_type   |
        | 基本方案   | 100            | 1999  | single_course |
      And 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        |     |
        | 200        |     |
      Then 操作成功
      And 銷售方案的 pbp_product_quantities 應為 {"100": 1, "200": 1}

  # ========== 修改數量 ==========

  Rule: 修改銷售方案時可更新商品數量

    Example: 成功修改商品數量
      Given 系統中有銷售方案 "超值學習包"，包含：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      When 管理員 "Admin" 更新銷售方案 "超值學習包" 的商品數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 5   |
      Then 操作成功
      And 銷售方案的 pbp_product_quantities 應為 {"100": 1, "200": 5}

    Example: 重新開啟編輯畫面，數量正確回顯
      Given 系統中有銷售方案 "超值學習包"，包含：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      When 管理員 "Admin" 查看銷售方案 "超值學習包" 的編輯畫面
      Then 商品 200 的數量輸入框顯示 "3"
      And 商品 100 的數量輸入框顯示 "1"

  # ========== 數量輸入驗證 ==========

  Rule: 數量必須為 1~999 的正整數，不合法值自動修正為 1

    Scenario Outline: 不合法數量 <input> 自動修正為 1
      When 管理員 "Admin" 將商品數量設定為 "<input>"
      Then 數量輸入框顯示 "1"

      Examples:
        | input | 說明         |
        | 0     | 零           |
        | -1    | 負數         |
        | 1.5   | 小數         |
        |       | 空白         |
        | abc   | 非數字       |

    Example: 數量上限為 999
      When 管理員 "Admin" 將商品數量設定為 "1000"
      Then 數量輸入框顯示 "999"

    Example: 數量最小為 1
      When 管理員 "Admin" 將商品數量設定為 "1"
      Then 數量輸入框顯示 "1"

  # ========== 向下相容 ==========

  Rule: 既有銷售方案升級後，所有商品數量預設為 1

    Example: 舊銷售方案無 pbp_product_quantities 時，數量預設為 1
      Given 系統中有舊銷售方案 "舊方案"，只有 pbp_product_ids 無 pbp_product_quantities：
        | product_id |
        | 100        |
        | 200        |
      When 管理員 "Admin" 查看銷售方案 "舊方案" 的編輯畫面
      Then 商品 100 的數量輸入框顯示 "1"
      And 商品 200 的數量輸入框顯示 "1"

    Example: 舊銷售方案前台展示數量為 1（不顯示 ×1）
      Given 系統中有舊銷售方案 "舊方案"，只有 pbp_product_ids 無 pbp_product_quantities
      When 學員瀏覽前台課程銷售頁
      Then 銷售方案 "舊方案" 中的商品不顯示 "×1" 標記
