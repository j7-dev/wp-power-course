@ignore @command
Feature: 設定銷售方案商品數量

  管理員在銷售方案編輯介面中，可為每個加入方案的商品設定數量。
  數量存為 pbp_product_quantities postmeta（JSON 格式 {"商品ID": 數量}）。
  pbp_product_ids 維持現有格式不變，僅新增 pbp_product_quantities 作為輔助。
  無 pbp_product_quantities 時預設所有商品數量為 1（向下相容）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100     | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下商品：
      | productId | name       | type   | status  | regular_price |
      | 200       | T-shirt    | simple | publish | 200           |
      | 300       | React 課程 | simple | publish | 5000          |
    And 課程 100 有以下銷售方案：
      | bundleId | name       | bundle_type | link_course_id | regular_price |
      | 500      | 超值套餐   | bundle      | 100            | 6000          |
    And 銷售方案 500 包含以下商品：
      | productId |
      | 100       |
      | 200       |
      | 300       |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- 數量必須為 1 至 999 之間的正整數

    Example: 數量為 0 時儲存失敗
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 0        |
        | 300       | 1        |
      Then 操作失敗，錯誤訊息包含 "數量至少為 1"

    Example: 數量為負數時儲存失敗
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | -3       |
        | 300       | 1        |
      Then 操作失敗，錯誤訊息包含 "數量至少為 1"

    Example: 數量超過 999 時儲存失敗
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 1000     |
        | 300       | 1        |
      Then 操作失敗，錯誤訊息包含 "數量不可超過 999"

    Example: 數量為非整數時儲存失敗
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 2.5      |
        | 300       | 1        |
      Then 操作失敗，錯誤訊息包含 "數量必須為正整數"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 數量成功存入 pbp_product_quantities meta（JSON 格式）

    Example: 成功設定商品數量
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
      Then 操作成功
      And 銷售方案 500 的 pbp_product_quantities meta 為：
        """json
        {"100": 1, "200": 3, "300": 1}
        """

    Example: 重複更新數量覆蓋前一次設定
      Given 銷售方案 500 的 pbp_product_quantities 為 {"100": 1, "200": 3, "300": 1}
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 2        |
        | 200       | 5        |
        | 300       | 1        |
      Then 操作成功
      And 銷售方案 500 的 pbp_product_quantities meta 為：
        """json
        {"100": 2, "200": 5, "300": 1}
        """

  Rule: 後置（狀態）- pbp_product_ids 維持不變，數量資料獨立於商品列表

    Example: 更新數量不影響 pbp_product_ids
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 2        |
        | 200       | 3        |
        | 300       | 1        |
      Then 操作成功
      And 銷售方案 500 的 pbp_product_ids 仍為 [100, 200, 300]

  Rule: 後置（狀態）- 訂閱商品與一般商品統一支援數量設定

    Example: 訂閱商品也可以設定數量
      Given 系統中有以下商品：
        | productId | name     | type         | status  | regular_price |
        | 400       | 月費方案 | subscription | publish | 299           |
      And 銷售方案 500 包含商品 400
      When 管理員 "Admin" 更新銷售方案 500 的商品數量：
        | productId | quantity |
        | 100       | 1        |
        | 200       | 3        |
        | 300       | 1        |
        | 400       | 2        |
      Then 操作成功
      And 銷售方案 500 的 pbp_product_quantities meta 包含 {"400": 2}

  # ========== 後置（回應）==========

  Rule: 後置（回應）- API 回傳包含 pbp_product_quantities 欄位

    Example: 取得銷售方案時回傳數量資訊
      Given 銷售方案 500 的 pbp_product_quantities 為 {"100": 1, "200": 3, "300": 1}
      When 管理員 "Admin" 取得銷售方案 500 的詳情
      Then 回應包含 pbp_product_quantities：
        """json
        {"100": 1, "200": 3, "300": 1}
        """

  # ========== 後置（回應）- 向下相容 ==========

  Rule: 後置（回應）- 無 pbp_product_quantities 時預設所有商品數量為 1

    Example: 舊銷售方案無數量資料時回傳空物件
      Given 銷售方案 500 沒有 pbp_product_quantities meta
      When 管理員 "Admin" 取得銷售方案 500 的詳情
      Then 回應中 pbp_product_quantities 為 {}
      And 前端依照預設邏輯將所有商品數量顯示為 1

  # ========== 後置（狀態）- 原價加總包含數量因素 ==========

  Rule: 後置（狀態）- 原價加總計算：各商品 regular_price × quantity 之加總

    Example: 原價加總反映數量
      Given 銷售方案 500 的 pbp_product_quantities 為 {"100": 1, "200": 3, "300": 1}
      When 計算銷售方案 500 的原價加總
      Then 原價加總為 3000 × 1 + 200 × 3 + 5000 × 1 = 8600
