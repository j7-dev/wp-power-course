@ignore @query
Feature: 查詢外部課程列表

  課程列表 API 應能混合回傳站內課程與外部課程，
  並支援依 product_type 篩選。
  外部課程在列表回應中包含 external_url 與 button_text 欄位。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name            | _is_course | product_type | status  | price | external_url                   | button_text |
      | 100      | PHP 基礎課      | yes        | simple       | publish | 1200  |                                |             |
      | 101      | React 實戰課    | yes        | simple       | publish | 2000  |                                |             |
      | 200      | Python 資料科學 | yes        | external     | publish | 2400  | https://hahow.in/courses/12345 | 前往 Hahow  |
      | 201      | UX 設計入門     | yes        | external     | draft   |       | https://pressplay.cc/courses/1 | 前往課程    |

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 預設查詢回傳所有課程（含外部課程）

    Example: 預設查詢混合回傳站內與外部課程
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | posts_per_page |
        | 10             |
      Then 操作成功
      And 回應課程數量為 4
      And 回應中應包含課程 "PHP 基礎課"
      And 回應中應包含課程 "Python 資料科學"

  Rule: 後置（回應）- 支援依 product_type 篩選外部課程

    Example: 篩選僅外部課程
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | product_type |
        | external     |
      Then 操作成功
      And 回應課程數量為 2
      And 回應中應包含課程 "Python 資料科學"
      And 回應中應包含課程 "UX 設計入門"

    Example: 篩選僅站內課程
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | product_type |
        | simple       |
      Then 操作成功
      And 回應課程數量為 2
      And 回應中應包含課程 "PHP 基礎課"
      And 回應中應包含課程 "React 實戰課"

  Rule: 後置（回應）- 外部課程回應包含 external_url 與 button_text

    Example: 外部課程列表回應含外部連結欄位
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | product_type |
        | external     |
      Then 操作成功
      And 回應中課程 "Python 資料科學" 的 external_url 應為 "https://hahow.in/courses/12345"
      And 回應中課程 "Python 資料科學" 的 button_text 應為 "前往 Hahow"

  Rule: 後置（回應）- 站內課程回應不包含 external_url 欄位（或為空）

    Example: 站內課程無外部連結欄位
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | product_type |
        | simple       |
      Then 操作成功
      And 回應中課程 "PHP 基礎課" 的 external_url 應為空

  Rule: 後置（回應）- 支援跨類型排序

    Example: 依建立日期排序時站內與外部課程混合排列
      When 管理員 "Admin" 查詢課程列表，參數如下：
        | orderby | order |
        | date    | DESC  |
      Then 操作成功
      And 回應課程數量為 4
