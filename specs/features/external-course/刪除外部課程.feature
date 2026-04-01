@ignore @command
Feature: 刪除外部課程

  管理員可以刪除外部課程。外部課程無章節、學員等關聯資料，刪除後直接移除。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下外部課程：
      | courseId | name            | _is_course | product_type | status  | external_url                   |
      | 200      | Python 資料科學 | yes        | external     | publish | https://hahow.in/courses/12345 |
      | 201      | UX 設計入門     | yes        | external     | draft   | https://pressplay.cc/courses/1 |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- ids 不可為空陣列

    Example: 未提供 ids 時操作失敗
      When 管理員 "Admin" 刪除課程 ids []
      Then 操作失敗，錯誤訊息包含 "ids"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 外部課程被刪除

    Example: 成功刪除單一外部課程
      When 管理員 "Admin" 刪除課程 ids [200]
      Then 操作成功
      And 課程 200 應不存在

    Example: 成功批量刪除多個外部課程
      When 管理員 "Admin" 刪除課程 ids [200, 201]
      Then 操作成功
      And 課程 200 應不存在
      And 課程 201 應不存在
