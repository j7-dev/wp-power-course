@ignore
Feature: 排序章節

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 系統中有以下章節：
      | chapterId | post_title      | post_type  | post_parent | parent_course_id | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100         | 100              | 0          |
      | 202       | 第二章：語法基礎 | pc_chapter | 100         | 100              | 1          |
      | 203       | 第三章：函數    | pc_chapter | 100         | 100              | 2          |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- chapters 中每個 id 對應的章節必須存在

    Example: 排序清單中包含不存在的章節 id 時操作失敗
      When 管理員 "Admin" 排序章節，chapters 如下：
        | id   | menu_order |
        | 201  | 0          |
        | 9999 | 1          |
      Then 操作失敗，錯誤訊息包含 "找不到章節"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- chapters 不可為空陣列

    Example: 提供空陣列時操作失敗
      When 管理員 "Admin" 排序章節，chapters 為空陣列
      Then 操作失敗，錯誤訊息包含 "chapters"

  Rule: 前置（參數）- 每項必須包含有效的 id

    Example: 某項缺少 id 時操作失敗
      When 管理員 "Admin" 排序章節，chapters 如下：
        | id  | menu_order |
        |     | 0          |
        | 202 | 1          |
      Then 操作失敗

  Rule: 前置（參數）- 每項必須包含有效的 menu_order

    Example: 某項缺少 menu_order 時操作失敗
      When 管理員 "Admin" 排序章節，chapters 如下：
        | id  | menu_order |
        | 201 |            |
        | 202 | 1          |
      Then 操作失敗

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 批量更新所有章節的 menu_order

    Example: 成功交換兩個章節的排序
      When 管理員 "Admin" 排序章節，chapters 如下：
        | id  | menu_order |
        | 201 | 2          |
        | 202 | 1          |
        | 203 | 0          |
      Then 操作成功
      And 章節 201 的 menu_order 應為 2
      And 章節 202 的 menu_order 應為 1
      And 章節 203 的 menu_order 應為 0

  Rule: 後置（狀態）- 未包含在 chapters 中的章節 menu_order 不受影響

    Example: 只更新部分章節時其他章節排序不變
      When 管理員 "Admin" 排序章節，chapters 如下：
        | id  | menu_order |
        | 201 | 5          |
        | 202 | 6          |
      Then 操作成功
      And 章節 201 的 menu_order 應為 5
      And 章節 202 的 menu_order 應為 6
      And 章節 203 的 menu_order 應保持 2
