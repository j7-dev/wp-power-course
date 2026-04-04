@readmodel
Feature: 銷售方案數量原價計算

  銷售方案的原價（regular_price）自動反映各商品 (單價 × 數量) 的加總，
  當商品或數量變更時即時更新。

  Background:
    Given 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price | sale_price |
      | 100      | PHP 基礎課 | yes        | publish | 3000          | 2500       |
    And 系統中有以下商品：
      | productId | name          | status  | regular_price | sale_price |
      | 200       | Python 講義   | publish | 500           | 400        |
      | 201       | React T-shirt | publish | 800           |            |

  # ========== 原價計算 ==========

  Rule: 原價 = 各商品 (regular_price × qty) 的加總

    Example: 含多數量商品的原價計算
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      Then 原價參考值應為 4500
      # 3000×1 + 500×3 = 4500

    Example: 所有商品數量為 1 時等同簡單加總
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 1   |
      Then 原價參考值應為 3500
      # 3000×1 + 500×1 = 3500

    Example: 不含當前課程時僅計算其他商品
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 200        | 3   |
        | 201        | 2   |
      Then 原價參考值應為 3100
      # 500×3 + 800×2 = 3100

  # ========== 特價計算 ==========

  Rule: 特價參考值 = 各商品 (sale_price 或 regular_price) × qty 的加總

    Example: 含特價商品的特價計算
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      Then 特價參考值應為 3700
      # 2500×1 + 400×3 = 3700（使用 sale_price）

    Example: 無特價商品 fallback 使用 regular_price
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 201        | 2   |
      Then 特價參考值應為 4100
      # 2500×1 + 800×2 = 4100（201 無 sale_price，fallback regular_price）

  # ========== 即時更新 ==========

  Rule: 調整數量後原價即時更新

    Example: 數量變更觸發原價重新計算
      Given 銷售方案包含以下商品與數量：
        | product_id | qty |
        | 100        | 1   |
        | 200        | 3   |
      And 原價參考值為 4500
      When 管理員將商品 200 的數量從 3 改為 5
      Then 原價參考值應為 5500
      # 3000×1 + 500×5 = 5500
