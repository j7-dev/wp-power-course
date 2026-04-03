@ignore @command
Feature: 設定課程線性觀看

  站長可以在課程編輯頁面開啟或關閉「線性觀看」功能。
  開啟後，學員必須按照章節順序逐步完成，才能觀看後面的章節。
  meta key: enable_sequential_learning，預設值 'no'，儲存為 WooCommerce product meta。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  | enable_sequential_learning |
      | 100      | Python 入門課 | yes        | publish | no                         |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 必須為管理員角色

    Example: 非管理員無法修改線性觀看設定
      Given 系統中有以下用戶：
        | userId | name    | email            | role       |
        | 2      | Student | student@test.com | subscriber |
      When 用戶 "Student" 更新課程 100，參數如下：
        | enable_sequential_learning |
        | yes                        |
      Then 操作失敗，錯誤為「權限不足」

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 不存在的課程設定失敗
      When 管理員 "Admin" 更新課程 9999，參數如下：
        | enable_sequential_learning |
        | yes                        |
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_sequential_learning 值只接受 'yes' 或 'no'

    Example: 無效值被忽略或拒絕
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential_learning |
        | invalid                    |
      Then 操作失敗，錯誤訊息包含 "enable_sequential_learning"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看

    Example: 管理員開啟課程的線性觀看功能
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential_learning |
        | yes                        |
      Then 操作成功
      And 課程 100 的 enable_sequential_learning 應為 "yes"

  Rule: 後置（狀態）- 成功關閉線性觀看

    Example: 管理員關閉課程的線性觀看功能
      Given 課程 100 的 enable_sequential_learning 為 "yes"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential_learning |
        | no                         |
      Then 操作成功
      And 課程 100 的 enable_sequential_learning 應為 "no"

  Rule: 後置（狀態）- 預設值為 'no'

    Example: 新建課程的線性觀看預設為關閉
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 操作成功
      And 回應中 enable_sequential_learning 應為 "no"
