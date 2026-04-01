@ignore @command
Feature: 設定課程線性觀看模式

  管理員可以針對每門課程個別設定是否啟用「線性觀看」模式。
  啟用後，學員必須按照章節排列順序依序完成，才能解鎖觀看下一個章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 操作者必須有 manage_woocommerce 權限

    Example: 非管理員無法設定線性觀看
      Given 系統中有以下用戶：
        | userId | name    | email            | role       |
        | 2      | Student | student@test.com | subscriber |
      When 用戶 "Student" 設定課程 100 的 enable_linear_viewing 為 "yes"
      Then 操作失敗，錯誤為「權限不足」

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 課程不存在時設定失敗
      When 管理員 "Admin" 設定課程 9999 的 enable_linear_viewing 為 "yes"
      Then 操作失敗，錯誤為「課程不存在」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_linear_viewing 值必須為 "yes" 或 "no"

    Example: 傳入非法值時設定失敗
      When 管理員 "Admin" 設定課程 100 的 enable_linear_viewing 為 "maybe"
      Then 操作失敗

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 啟用線性觀看後，課程 product meta 寫入 enable_linear_viewing = yes

    Example: 成功啟用線性觀看
      When 管理員 "Admin" 設定課程 100 的 enable_linear_viewing 為 "yes"
      Then 操作成功
      And 課程 100 的 product meta enable_linear_viewing 應為 "yes"

  Rule: 後置（狀態）- 關閉線性觀看後，課程 product meta 寫入 enable_linear_viewing = no

    Example: 成功關閉線性觀看
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      When 管理員 "Admin" 設定課程 100 的 enable_linear_viewing 為 "no"
      Then 操作成功
      And 課程 100 的 product meta enable_linear_viewing 應為 "no"

  Rule: 後置（狀態）- 預設值為 no（未啟用）

    Example: 未設定過的課程預設為關閉
      When 查詢課程 100 的 enable_linear_viewing
      Then 值應為 "no"

  Rule: 後置（狀態）- 關閉線性觀看不影響學員已有的完成紀錄

    Example: 關閉後學員的 finished_at 紀錄保留
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      And 系統中有以下用戶：
        | userId | name  | email          |
        | 2      | Alice | alice@test.com |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent |
        | 200       | 第一章     | 100         |
        | 201       | 第二章     | 100         |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 管理員 "Admin" 設定課程 100 的 enable_linear_viewing 為 "no"
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應為 "2025-06-01 10:00:00"
