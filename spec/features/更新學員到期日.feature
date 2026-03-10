@ignore
Feature: 更新學員到期日

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 1      | Admin | admin@test.com |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date |
      | 2      | 100      | 1735689600  |
      | 3      | 100      | 0           |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 學員必須已有該課程的存取權

    Example: 更新沒有存取權的學員到期日時操作失敗
      Given 系統中有一個 userId 5 的用戶，未有任何課程存取權
      When 管理員 "Admin" 更新 userId 5 在課程 100 的到期日為 1893456000
      Then 操作失敗，錯誤訊息包含 "未擁有課程存取權"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- user_ids 不可為空

    Example: 未提供 user_ids 時操作失敗
      When 管理員 "Admin" 更新 user_ids [] 在課程 100 的到期日為 1893456000
      Then 操作失敗，錯誤訊息包含 "user_ids"

  Rule: 前置（參數）- course_ids 不可為空

    Example: 未提供 course_ids 時操作失敗
      When 管理員 "Admin" 更新 userId 2 在課程 [] 的到期日為 1893456000
      Then 操作失敗，錯誤訊息包含 "course_ids"

  Rule: 前置（參數）- timestamp 必須為 0 或 10 位 unix timestamp

    Scenario Outline: 不合法的 timestamp 格式導致操作失敗
      When 管理員 "Admin" 更新 userId 2 在課程 100 的到期日為 <timestamp>
      Then 操作失敗

      Examples:
        | 說明             | timestamp   |
        | 位數不足         | 12345       |
        | 負數             | -1735689600 |
        | 非數字字串       | never       |

  Rule: 前置（參數）- timestamp 為 0 時代表永久存取（合法值）

    Example: timestamp 為 0 時操作成功
      When 管理員 "Admin" 更新 userId 2 在課程 100 的到期日為 0
      Then 操作成功

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 更新 coursemeta 中的 expire_date

    Example: 成功將到期日延長至未來時間
      When 管理員 "Admin" 更新 userId 2 在課程 100 的到期日為 1893456000
      Then 操作成功
      And 課程 100 對用戶 "Alice" 的 coursemeta expire_date 應為 "1893456000"

  Rule: 後置（狀態）- 成功將到期日改為永久（0）

    Example: 將有限期學員改為永久存取
      When 管理員 "Admin" 更新 userId 2 在課程 100 的到期日為 0
      Then 操作成功
      And 課程 100 對用戶 "Alice" 的 coursemeta expire_date 應為 "0"

  Rule: 後置（狀態）- 觸發 power_course_after_update_student_from_course action

    Example: 更新到期日後觸發 action hook
      When 管理員 "Admin" 更新 userId 2 在課程 100 的到期日為 1893456000
      Then 操作成功
      And action "power_course_after_update_student_from_course" 應以參數 (2, 100, 1893456000) 被觸發
