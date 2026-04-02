@ignore @command
Feature: 設定課程線性觀看

  管理員可以在課程設定中開啟或關閉「線性觀看」功能。
  開啟後，學員必須按章節排序逐一完成才能存取後續章節。
  預設為關閉，不影響現有課程行為。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name           | _is_course | status  |
      | 100      | Python 從零到一 | yes        | publish |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_linear_viewing 值必須為 yes 或 no

    Example: 傳入不合法的值時更新失敗
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_viewing |
        | maybe                 |
      Then 操作失敗，錯誤訊息包含 "enable_linear_viewing"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 預設值為 no

    Example: 新建課程的線性觀看預設為關閉
      When 管理員 "Admin" 建立課程，參數如下：
        | name         | status  | price | limit_type |
        | 新建測試課程 | publish | 1000  | unlimited  |
      Then 操作成功
      And 新建課程的 enable_linear_viewing 應為 "no"

  Rule: 後置（狀態）- 管理員可開啟線性觀看

    Example: 成功開啟課程線性觀看
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_viewing |
        | yes                   |
      Then 操作成功
      And 課程 100 的 enable_linear_viewing 應為 "yes"

  Rule: 後置（狀態）- 管理員可關閉線性觀看

    Example: 成功關閉課程線性觀看
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_viewing |
        | no                    |
      Then 操作成功
      And 課程 100 的 enable_linear_viewing 應為 "no"

  Rule: 後置（狀態）- 開關切換立即生效，不影響既有完成紀錄

    Example: 關閉線性觀看不會刪除學員的完成紀錄
      Given 課程 100 的 enable_linear_viewing 為 "yes"
      And 系統中有以下用戶：
        | userId | name  | email          |
        | 2      | Alice | alice@test.com |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent |
        | 200       | 第一章     | 100         |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_viewing |
        | no                    |
      Then 操作成功
      And 章節 200 對用戶 "Alice" 的 chaptermeta finished_at 應不為空
