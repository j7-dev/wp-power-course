@ignore @constraint
Feature: 外部課程購物車阻擋

  外部課程使用 WooCommerce external 產品類型，
  天然不可購買（is_purchasable = false）。
  即使透過 URL 操作嘗試加入購物車，系統也應阻擋。

  Background:
    Given 系統中有以下外部課程：
      | courseId | name            | product_type | status  | external_url                   |
      | 200      | Python 資料科學 | external     | publish | https://hahow.in/courses/12345 |

  # ========== 購物車阻擋 ==========

  Rule: 外部課程不可被加入購物車（WC_Product_External::is_purchasable = false）

    Example: 透過 WC AJAX 加入購物車被阻擋
      When 訪客嘗試以 AJAX 方式加入課程 200 到購物車
      Then 操作失敗
      And 課程 200 不在購物車中

    Example: 透過 URL 參數加入購物車被阻擋
      When 訪客嘗試以 URL 參數 "?add-to-cart=200" 加入購物車
      Then 課程 200 不在購物車中
      And WooCommerce 應顯示無法購買提示

  Rule: 外部課程不出現在 WooCommerce 結帳流程

    Example: 購物車中不會有外部課程
      Given 訪客購物車中有站內課程 100
      When 訪客瀏覽結帳頁
      Then 結帳頁不應包含課程 200
