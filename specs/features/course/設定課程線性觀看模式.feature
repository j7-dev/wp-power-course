@command
Feature: 設定課程線性觀看模式

  管理員可在每門課程的「其他設定」中，開啟或關閉「線性觀看」模式（enable_linear_mode）。
  開啟後，學員必須按照章節排序依序完成學習。預設為關閉（'no'），不影響現有課程行為。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 必須具備 manage_woocommerce 權限

    Example: 非管理員嘗試設定線性觀看 — 操作失敗
      Given 系統中有以下用戶：
        | userId | name | email         | role       |
        | 2      | Bob  | bob@test.com  | subscriber |
      When 用戶 "Bob" 更新課程 100，參數如下：
        | enable_linear_mode |
        | yes                |
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_linear_mode 值只接受 'yes' 或 'no'

    Example: 傳入無效值時操作失敗
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | maybe              |
      Then 操作失敗，錯誤訊息包含 "enable_linear_mode"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功更新 enable_linear_mode meta

    Example: 開啟線性觀看模式
      Given 課程 100 的 enable_linear_mode 為 "no"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | yes                |
      Then 操作成功
      And 課程 100 的 enable_linear_mode 應為 "yes"

    Example: 關閉線性觀看模式
      Given 課程 100 的 enable_linear_mode 為 "yes"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | no                 |
      Then 操作成功
      And 課程 100 的 enable_linear_mode 應為 "no"

  Rule: 後置（狀態）- 預設值為 'no'

    Example: 未設定過 enable_linear_mode 時預設為 no
      When 管理員 "Admin" 查詢課程 100 的詳情
      Then 課程 100 的 enable_linear_mode 應為 "no"

  Rule: 後置（狀態）- 設定變更即時生效，不需額外操作

    Example: 開啟後學員端立即套用線性觀看限制
      Given 系統中有以下用戶：
        | userId | name  | email          | role       |
        | 2      | Alice | alice@test.com | subscriber |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 第一章     | 100         | 10         |
        | 201       | 第二章     | 100         | 20         |
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | yes                |
      Then 操作成功
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 章節 200（第一章）的鎖定狀態為 unlocked
      And 章節 201（第二章）的鎖定狀態為 locked

    Example: 關閉後學員端立即解除所有鎖定
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 系統中有以下用戶：
        | userId | name  | email          | role       |
        | 2      | Alice | alice@test.com | subscriber |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 第一章     | 100         | 10         |
        | 201       | 第二章     | 100         | 20         |
      And 用戶 "Alice" 在章節 200 無 finished_at
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | no                 |
      Then 操作成功
      When 用戶 "Alice" 查詢課程 100 的章節鎖定狀態
      Then 所有章節的鎖定狀態均為 unlocked
