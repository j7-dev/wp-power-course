@ignore @command
Feature: 設定課程線性觀看

  管理員可以在課程「其他設定」分頁中開啟或關閉「線性觀看」(is_sequential) 功能。
  開啟後學員必須按順序完成章節。預設為關閉。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | is_sequential |
      | 100      | PHP 基礎課 | yes        | publish | no            |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 不存在的課程更新失敗
      When 管理員 "Admin" 更新課程 9999 的 is_sequential 為 true
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- is_sequential 必須為布林值

    Example: is_sequential 為非布林值時操作失敗
      When 管理員 "Admin" 更新課程 100 的 is_sequential 為 "invalid"
      Then 操作失敗

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看

    Example: 管理員開啟課程線性觀看
      When 管理員 "Admin" 更新課程 100 的 is_sequential 為 true
      Then 操作成功
      And 課程 100 的 is_sequential 應為 true

  Rule: 後置（狀態）- 成功關閉線性觀看

    Example: 管理員關閉課程線性觀看
      Given 課程 100 的 is_sequential 為 true
      When 管理員 "Admin" 更新課程 100 的 is_sequential 為 false
      Then 操作成功
      And 課程 100 的 is_sequential 應為 false

  Rule: 後置（狀態）- 新課程的 is_sequential 預設為 false

    Example: 新建課程預設不啟用線性觀看
      When 管理員 "Admin" 建立新課程 "React 實戰"
      Then 操作成功
      And 新課程的 is_sequential 應為 false

  Rule: 後置（狀態）- 關閉線性觀看不影響學員既有進度

    Example: 關閉線性觀看後學員的完成紀錄保持不變
      Given 課程 100 的 is_sequential 為 true
      And 系統中有以下用戶：
        | userId | name  | email          |
        | 2      | Alice | alice@test.com |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 用戶 "Alice" 已完成章節 [200, 201]
      When 管理員 "Admin" 更新課程 100 的 is_sequential 為 false
      Then 操作成功
      And 用戶 "Alice" 在章節 200 的 finished_at 應不為空
      And 用戶 "Alice" 在章節 201 的 finished_at 應不為空
