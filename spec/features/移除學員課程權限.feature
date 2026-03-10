@ignore
Feature: 移除學員課程權限

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          |
      | 1      | Admin | admin@test.com |
      | 2      | Alice | alice@test.com |
      | 3      | Bob   | bob@test.com   |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
    And 以下學員已有課程存取權：
      | userId | courseId | expire_date |
      | 2      | 100      | 0           |
      | 2      | 101      | 0           |
      | 3      | 100      | 0           |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 學員必須已有該課程的存取權

    Example: 移除沒有存取權的學員時操作失敗
      When 管理員 "Admin" 移除 userId 3 對課程 101 的存取權
      Then 操作失敗，錯誤訊息包含 "未擁有課程存取權"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- user_ids 不可為空陣列

    Example: 未提供 user_ids 時操作失敗
      When 管理員 "Admin" 移除 user_ids [] 對課程 100 的存取權
      Then 操作失敗，錯誤訊息包含 "user_ids"

  Rule: 前置（參數）- course_ids 不可為空陣列

    Example: 未提供 course_ids 時操作失敗
      When 管理員 "Admin" 移除 userId 2 對課程 [] 的存取權
      Then 操作失敗，錯誤訊息包含 "course_ids"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 刪除 avl_course_ids user meta 中對應的 course_id

    Example: 成功移除學員的單一課程存取權
      When 管理員 "Admin" 移除 userId 2 對課程 100 的存取權
      Then 操作成功
      And 用戶 "Alice" 的 avl_course_ids 應不包含課程 100

  Rule: 後置（狀態）- 移除指定課程後其他課程存取權不受影響

    Example: 移除課程 100 存取權後課程 101 仍存在
      When 管理員 "Admin" 移除 userId 2 對課程 100 的存取權
      Then 操作成功
      And 用戶 "Alice" 的 avl_course_ids 應仍包含課程 101

  Rule: 後置（狀態）- 觸發 power_course_after_remove_student_from_course action

    Example: 移除學員後觸發 action hook
      When 管理員 "Admin" 移除 userId 2 對課程 100 的存取權
      Then 操作成功
      And action "power_course_after_remove_student_from_course" 應以參數 (2, 100) 被觸發

  Rule: 後置（狀態）- 學員的章節進度記錄不自動清除

    Example: 移除存取權後 pc_avl_chaptermeta 仍然保留
      Given 學員 userId 2 在課程 100 的章節 201 有完成紀錄
      When 管理員 "Admin" 移除 userId 2 對課程 100 的存取權
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應仍然存在
