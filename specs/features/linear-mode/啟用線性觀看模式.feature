@ignore @command
Feature: 啟用/關閉課程線性觀看模式

  管理員可在課程設定中開啟或關閉「線性觀看」模式。
  開啟後，學員必須按照 get_flatten_post_ids() 的扁平順序逐一完成章節，才能解鎖後續章節。
  Meta key: enable_linear_mode（值為 yes/no，預設 no）

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  |
      | 100      | PHP 基礎課 | yes        | publish |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 只有 manage_woocommerce 權限的用戶可設定

    Example: 非管理員無法設定線性觀看模式
      Given 系統中有以下用戶：
        | userId | name    | email            | role       |
        | 2      | Student | student@test.com | subscriber |
      When 用戶 "Student" 更新課程 100，參數如下：
        | enable_linear_mode |
        | yes                |
      Then 操作失敗，錯誤為「權限不足」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_linear_mode 只接受 yes 或 no

    Example: 傳入無效值時忽略
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | invalid            |
      Then 操作成功
      And 課程 100 的 meta enable_linear_mode 應為 "no"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看模式

    Example: 管理員開啟線性觀看
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | yes                |
      Then 操作成功
      And 課程 100 的 meta enable_linear_mode 應為 "yes"

  Rule: 後置（狀態）- 成功關閉線性觀看模式

    Example: 管理員關閉線性觀看
      Given 課程 100 的 meta enable_linear_mode 為 "yes"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_linear_mode |
        | no                 |
      Then 操作成功
      And 課程 100 的 meta enable_linear_mode 應為 "no"

  Rule: 後置（狀態）- 預設值為 no（未設定時不影響現有課程）

    Example: 新建課程預設不啟用線性觀看
      When 管理員 "Admin" 建立課程，參數如下：
        | name       | _is_course | status  |
        | Node.js 課 | yes        | publish |
      Then 操作成功
      And 新課程的 meta enable_linear_mode 應為 "no"
