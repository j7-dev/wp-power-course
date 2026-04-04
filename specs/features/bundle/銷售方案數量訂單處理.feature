@event
Feature: 銷售方案數量訂單處理

  學員購買銷售方案後，訂單明細中的商品名稱含 ×N 標記（N>1 時），
  庫存扣減依據 購買份數 × 方案內數量 計算。

  Background:
    Given 系統中有以下課程：
      | courseId | name       | _is_course | status  | stock_quantity |
      | 100      | PHP 基礎課 | yes        | publish | 100            |
    And 系統中有以下商品：
      | productId | name          | status  | stock_quantity | manage_stock |
      | 200       | Python 講義   | publish | 100            | yes          |
      | 201       | React T-shirt | publish | 50             | yes          |
    And 系統中有銷售方案 "超值學習包"，價格 2999，包含：
      | product_id | qty |
      | 100        | 1   |
      | 200        | 3   |
      | 201        | 2   |
    And 系統中有學員 "小明"

  # ========== 訂單品項名稱格式 ==========

  Rule: 訂單品項名稱格式為 "方案名稱 - 商品名稱 ×N"（N>1 時顯示，N=1 時不顯示 ×N）

    Example: 購買包含多數量商品的銷售方案後，訂單品項名稱正確
      When 學員 "小明" 購買 1 份 "超值學習包"
      Then 訂單中包含以下品項：
        | name                          | qty | total |
        | 超值學習包                    | 1   | 2999  |
        | 超值學習包 - PHP 基礎課       | 1   | 0     |
        | 超值學習包 - Python 講義 ×3   | 1   | 0     |
        | 超值學習包 - React T-shirt ×2 | 1   | 0     |

    Example: 購買多份銷售方案後，品項 qty 反映購買份數
      When 學員 "小明" 購買 2 份 "超值學習包"
      Then 訂單中包含以下品項：
        | name                          | qty | total |
        | 超值學習包                    | 2   | 5998  |
        | 超值學習包 - PHP 基礎課       | 2   | 0     |
        | 超值學習包 - Python 講義 ×3   | 2   | 0     |
        | 超值學習包 - React T-shirt ×2 | 2   | 0     |

  # ========== 庫存扣減 ==========

  Rule: 庫存扣減 = 購買份數 × 方案內商品數量

    Example: 購買 1 份銷售方案，庫存依方案數量扣減
      Given PHP 基礎課庫存為 100，Python 講義庫存為 100，React T-shirt 庫存為 50
      When 學員 "小明" 購買 1 份 "超值學習包"
      And 訂單完成
      Then PHP 基礎課庫存變為 99
      And Python 講義庫存變為 97
      And React T-shirt 庫存變為 48
      # 100-1=99, 100-3=97, 50-2=48

    Example: 購買 2 份銷售方案，庫存 = 購買份數 × 方案數量
      Given PHP 基礎課庫存為 100，Python 講義庫存為 100，React T-shirt 庫存為 50
      When 學員 "小明" 購買 2 份 "超值學習包"
      And 訂單完成
      Then PHP 基礎課庫存變為 98
      And Python 講義庫存變為 94
      And React T-shirt 庫存變為 46
      # 100-(1×2)=98, 100-(3×2)=94, 50-(2×2)=46

  # ========== 庫存扣減實作機制 ==========

  Rule: 訂單品項使用 WC qty = 購買份數，透過 order item meta 記錄方案內數量，自訂庫存扣減邏輯

    Example: 訂單品項 meta 記錄方案內數量
      When 學員 "小明" 購買 1 份 "超值學習包"
      Then "超值學習包 - Python 講義 ×3" 的 order item meta "_pbp_qty" 值為 3
      And "超值學習包 - React T-shirt ×2" 的 order item meta "_pbp_qty" 值為 2
      And "超值學習包 - PHP 基礎課" 的 order item meta "_pbp_qty" 值為 1

  # ========== 數量為 1 的品項 ==========

  Rule: 方案內數量為 1 的商品，品項名稱不帶 ×1

    Example: 數量為 1 的商品名稱不帶 ×1
      Given 系統中有銷售方案 "基本方案"，價格 1999，包含：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 1   |
      When 學員 "小明" 購買 1 份 "基本方案"
      Then 訂單中包含以下品項：
        | name                     | qty | total |
        | 基本方案                 | 1   | 1999  |
        | 基本方案 - PHP 基礎課    | 1   | 0     |
        | 基本方案 - Python 講義   | 1   | 0     |

  # ========== 課程授權不受數量影響 ==========

  Rule: 課程授權邏輯不因數量改變，數量只影響庫存

    Example: 課程數量為 2 但授權仍為購買者本人
      Given 系統中有銷售方案 "雙份方案"，包含：
        | product_id | qty |
        | 100        | 2   |
      When 學員 "小明" 購買 1 份 "雙份方案"
      And 訂單完成
      Then 學員 "小明" 獲得 "PHP 基礎課" 的課程授權（僅本人）
      And PHP 基礎課庫存減少 2
