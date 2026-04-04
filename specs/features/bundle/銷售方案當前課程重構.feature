@command
Feature: 銷售方案當前課程重構

  將「當前課程」從特殊處理重構為與其他商品一致的邏輯和 UI。
  移除 exclude_main_course 開關，當前課程改為建立時自動添加（qty=1）、可移除、可重新搜尋添加。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | regular_price |
      | 100      | PHP 基礎課 | yes        | publish | 3000          |
    And 系統中有以下商品：
      | productId | name        | status  | regular_price |
      | 200       | Python 講義 | publish | 500           |

  # ========== 新建銷售方案時自動添加當前課程 ==========

  Rule: 建立銷售方案時，自動將當前課程添加到商品列表中，數量預設為 1

    Example: 新建銷售方案時當前課程自動出現在商品列表
      When 管理員 "Admin" 在課程 100 的編輯頁建立新銷售方案
      Then 商品列表中自動包含 "PHP 基礎課"，數量為 1
      And 商品列表中 "PHP 基礎課" 與其他商品有相同的 UI 行為（數量輸入框、刪除按鈕）

  # ========== 移除當前課程 ==========

  Rule: 當前課程可從銷售方案中移除

    Example: 管理員移除當前課程
      Given 管理員 "Admin" 在課程 100 的編輯頁建立新銷售方案
      And 商品列表中自動包含 "PHP 基礎課"
      When 管理員點擊 "PHP 基礎課" 的刪除按鈕
      Then 商品列表中不再包含 "PHP 基礎課"
      And 銷售方案的 pbp_product_ids 不包含 100

  # ========== 重新搜尋添加當前課程 ==========

  Rule: 移除當前課程後可透過搜尋重新添加

    Example: 移除後可重新搜尋到當前課程
      Given 管理員 "Admin" 在課程 100 的編輯頁建立新銷售方案
      And 管理員已移除 "PHP 基礎課"
      When 管理員在商品搜尋框搜尋 "PHP"
      Then 搜尋結果中包含 "PHP 基礎課"
      When 管理員點擊選取 "PHP 基礎課"
      Then 商品列表中重新包含 "PHP 基礎課"，數量為 1

  # ========== 當前課程數量可調整 ==========

  Rule: 當前課程的數量可自由調整，與其他商品一致

    Example: 調整當前課程數量為 2
      Given 管理員 "Admin" 在課程 100 的編輯頁建立新銷售方案
      And 商品列表中自動包含 "PHP 基礎課"，數量為 1
      When 管理員將 "PHP 基礎課" 的數量改為 2
      Then 商品 100 的數量輸入框顯示 "2"
      And 銷售方案的 pbp_product_quantities 中 100 的數量為 2

  # ========== 移除 exclude_main_course 開關 ==========

  Rule: exclude_main_course 開關不再存在於 UI 中

    Example: 編輯銷售方案時不顯示排除當前課程開關
      When 管理員 "Admin" 在課程 100 的編輯頁建立新銷售方案
      Then UI 中不存在 "排除目前課程" 開關

  # ========== 既有銷售方案遷移 ==========

  Rule: 既有銷售方案根據 exclude_main_course 狀態自動遷移

    Example: exclude_main_course=no 的舊方案，當前課程出現在商品列表中
      Given 系統中有舊銷售方案 "舊方案A"，連結課程 100：
        | pbp_product_ids | exclude_main_course |
        | [200]           | no                  |
      When 系統遷移後，管理員查看 "舊方案A" 的編輯畫面
      Then 商品列表中包含 "PHP 基礎課"（數量 1）和 "Python 講義"（數量 1）
      And pbp_product_ids 應包含 [100, 200]

    Example: exclude_main_course=yes 的舊方案，當前課程不在商品列表中
      Given 系統中有舊銷售方案 "舊方案B"，連結課程 100：
        | pbp_product_ids | exclude_main_course |
        | [200]           | yes                 |
      When 系統遷移後，管理員查看 "舊方案B" 的編輯畫面
      Then 商品列表中只包含 "Python 講義"（數量 1）
      And pbp_product_ids 應包含 [200]
      And pbp_product_ids 不應包含 100
