@ignore @command
Feature: 設定線性觀看模式

  管理員可以針對每門課程個別設定是否啟用「線性觀看」模式。
  啟用後，學員必須按照章節排列順序依序完成，才能解鎖觀看下一個章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |
    And 課程 100 有以下章節：
      | chapterId | post_title | post_parent | menu_order |
      | 200       | 第一章     | 100         | 1          |
      | 201       | 1-1        | 200         | 1          |
      | 202       | 1-2        | 200         | 2          |
      | 203       | 第二章     | 100         | 2          |
      | 204       | 2-1        | 203         | 1          |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 操作者必須具有 manage_woocommerce 權限

    Example: 無管理權限時操作失敗
      When 用戶 "Alice" 設定課程 100 的 enable_linear_viewing 為 "yes"
      Then 操作失敗，錯誤為「權限不足」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- course_id 必須存在且為課程商品

    Example: 課程不存在時操作失敗
      When 用戶 "Admin" 設定課程 9999 的 enable_linear_viewing 為 "yes"
      Then 操作失敗，錯誤為「課程不存在」

  Rule: 前置（參數）- enable_linear_viewing 值必須為 "yes" 或 "no"

    Example: 無效值時操作失敗
      When 用戶 "Admin" 設定課程 100 的 enable_linear_viewing 為 "invalid"
      Then 操作失敗，錯誤為「參數格式錯誤」

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 啟用線性觀看後，課程 meta 儲存設定值

    Example: 成功啟用線性觀看
      Given 課程 100 的 enable_linear_viewing 為 "no"
      When 用戶 "Admin" 設定課程 100 的 enable_linear_viewing 為 "yes"
      Then 操作成功
      And 課程 100 的 postmeta enable_linear_viewing 應為 "yes"

    Example: 成功關閉線性觀看
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      When 用戶 "Admin" 設定課程 100 的 enable_linear_viewing 為 "no"
      Then 操作成功
      And 課程 100 的 postmeta enable_linear_viewing 應為 "no"

  Rule: 後置（狀態）- 啟用/關閉線性觀看不影響學員已有的完成紀錄

    Example: 啟用後學員完成紀錄保留
      Given 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 用戶 "Alice" 在章節 201 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 11:00:00"
      When 用戶 "Admin" 設定課程 100 的 enable_linear_viewing 為 "yes"
      Then 操作成功
      And 章節 201 對用戶 "Alice" 的 chaptermeta finished_at 應不為空
      And 章節 202 對用戶 "Alice" 的 chaptermeta finished_at 應不為空

  Rule: 後置（狀態）- 預設值為 "no"（新課程不啟用線性觀看）

    Example: 新建課程預設不啟用線性觀看
      Given 系統中有以下課程：
        | courseId | name     | _is_course | status  |
        | 101      | 新課程   | yes        | publish |
      When 查詢課程 101 的 enable_linear_viewing
      Then 值應為 "no"
