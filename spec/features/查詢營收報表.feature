@ignore
Feature: 查詢營收報表

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 系統中有以下 WooCommerce 訂單（已完成）：
      | orderId | courseId | amount | order_date          |
      | 1001    | 100      | 1200   | 2024-03-01 10:00:00 |
      | 1002    | 100      | 1200   | 2024-03-15 14:00:00 |
      | 1003    | 101      | 2000   | 2024-03-20 09:00:00 |
      | 1004    | 100      | 1200   | 2024-04-05 11:00:00 |
      | 1005    | 101      | 2000   | 2024-04-10 16:00:00 |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- start_date 若有設定必須符合 ISO 8601 格式

    Example: 設定不合法的 start_date 格式時操作失敗
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | start_date |
        | 2024/01/01 |
      Then 操作失敗，錯誤訊息包含 "start_date"

  Rule: 前置（參數）- end_date 若有設定必須符合 ISO 8601 格式

    Example: 設定不合法的 end_date 格式時操作失敗
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | end_date |
        | 20240401 |
      Then 操作失敗，錯誤訊息包含 "end_date"

  Rule: 前置（參數）- view_type 只允許 day、week、month

    Scenario Outline: 設定不合法的 view_type 時操作失敗
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | view_type   |
        | <view_type> |
      Then 操作失敗

      Examples:
        | view_type |
        | hour      |
        | quarter   |
        | year      |

  # ========== 後置（回應）==========

  Rule: 後置（回應）- 不帶篩選條件時回傳所有課程的總營收

    Example: 查詢全部營收時回傳正確總額
      When 管理員 "Admin" 查詢營收報表，不帶任何參數
      Then 操作成功
      And 回應中 total_revenue 應為 7600
      And 回應中 total_orders 應為 5

  Rule: 後置（回應）- 支援依日期區間篩選

    Example: 只查詢 2024-03 的營收
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | start_date | end_date   |
        | 2024-03-01 | 2024-03-31 |
      Then 操作成功
      And 回應中 total_revenue 應為 4400
      And 回應中 total_orders 應為 3

  Rule: 後置（回應）- 支援依 course_id 篩選單一課程營收

    Example: 查詢課程 100 的營收時不包含課程 101 的訂單
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | course_id |
        | 100       |
      Then 操作成功
      And 回應中 total_revenue 應為 3600
      And 回應中 total_orders 應為 3

  Rule: 後置（回應）- 回傳 revenue_by_course（各課程分項營收）

    Example: 全部查詢結果包含各課程分項統計
      When 管理員 "Admin" 查詢營收報表，不帶任何參數
      Then 操作成功
      And 回應中 revenue_by_course 應包含課程 100 的統計，revenue 為 3600
      And 回應中 revenue_by_course 應包含課程 101 的統計，revenue 為 4000

  Rule: 後置（回應）- 回傳 revenue_by_period（時間軸資料）

    Example: 依 month 粒度查詢時回傳每月分組資料
      When 管理員 "Admin" 查詢營收報表，參數如下：
        | view_type |
        | month     |
      Then 操作成功
      And 回應中 revenue_by_period 應包含 2024-03 的統計
      And 回應中 revenue_by_period 應包含 2024-04 的統計
