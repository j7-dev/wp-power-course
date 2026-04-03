@ignore @command
Feature: 設定課程線性觀看模式

  管理員可以為每門課程個別開啟或關閉「線性觀看」功能。
  開啟後學員必須按照章節順序逐步完成，才能觀看下一章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 操作者必須具備 manage_woocommerce 權限

    Example: 無管理權限的用戶無法修改設定
      Given 系統中有以下用戶：
        | userId | name    | email             | role       |
        | 2      | Student | student@test.com  | subscriber |
      When 用戶 "Student" 更新課程 100 的線性觀看模式為 "yes"
      Then 操作失敗，錯誤為「權限不足」

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 不存在的課程更新失敗
      When 管理員 "Admin" 更新課程 9999 的線性觀看模式為 "yes"
      Then 操作失敗，錯誤為「課程不存在」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- linear_chapter_mode 值必須為 "yes" 或 "no"

    Example: 無效值時更新失敗
      When 管理員 "Admin" 更新課程 100 的線性觀看模式為 "invalid"
      Then 操作失敗，錯誤訊息包含 "linear_chapter_mode"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看模式

    Example: 管理員開啟線性觀看
      Given 課程 100 的 linear_chapter_mode 為 "no"
      When 管理員 "Admin" 更新課程 100 的線性觀看模式為 "yes"
      Then 操作成功
      And 課程 100 的 postmeta linear_chapter_mode 應為 "yes"

  Rule: 後置（狀態）- 成功關閉線性觀看模式

    Example: 管理員關閉線性觀看
      Given 課程 100 的 linear_chapter_mode 為 "yes"
      When 管理員 "Admin" 更新課程 100 的線性觀看模式為 "no"
      Then 操作成功
      And 課程 100 的 postmeta linear_chapter_mode 應為 "no"

  Rule: 後置（狀態）- 預設值為 "no"（關閉）

    Example: 新課程預設不開啟線性觀看
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  |
        | 200      | 新課程     | yes        | publish |
      When 查詢課程 200 的 linear_chapter_mode
      Then 結果應為 "no"

  Rule: 後置（狀態）- 關閉線性觀看不影響已記錄的完成進度

    Example: 關閉線性觀看後完成紀錄保留
      Given 課程 100 的 linear_chapter_mode 為 "yes"
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent |
        | 200       | 1-1        | 100         |
        | 201       | 1-2        | 100         |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 管理員 "Admin" 更新課程 100 的線性觀看模式為 "no"
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應不為空
