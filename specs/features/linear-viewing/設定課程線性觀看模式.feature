@ignore @command
Feature: 設定課程線性觀看模式

  管理員可在每個課程上獨立設定是否開啟「線性觀看」模式。
  開啟後，學員必須按照章節順序（依 flatten menu_order）逐一完成，才能解鎖下一個章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | no                 |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 操作者必須具有 manage_woocommerce 能力

    Example: 無管理權限時操作失敗
      Given 系統中有以下用戶：
        | userId | name | email         | role       |
        | 2      | Bob  | bob@test.com  | subscriber |
      When 用戶 "Bob" 更新課程 100 的 enable_linear_mode 為 "yes"
      Then 操作失敗，錯誤為「權限不足」

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 課程不存在時操作失敗
      When 管理員 "Admin" 更新課程 9999 的 enable_linear_mode 為 "yes"
      Then 操作失敗，錯誤為「課程不存在」

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_linear_mode 值必須為 "yes" 或 "no"

    Example: 非法值時操作失敗
      When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "maybe"
      Then 操作失敗

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看模式

    Example: 開啟線性觀看
      When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "yes"
      Then 操作成功
      And 課程 100 的 product meta "enable_linear_mode" 應為 "yes"

  Rule: 後置（狀態）- 成功關閉線性觀看模式

    Example: 關閉線性觀看
      Given 課程 100 的 enable_linear_mode 為 "yes"
      When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "no"
      Then 操作成功
      And 課程 100 的 product meta "enable_linear_mode" 應為 "no"

  Rule: 後置（狀態）- 預設值為 "no"（關閉）

    Example: 未設定時預設為關閉
      When 查詢課程 100 的 enable_linear_mode
      Then 值應為 "no"

  Rule: 後置（狀態）- 關閉後所有學員立即恢復自由觀看

    Example: 關閉線性觀看後鎖定解除
      Given 課程 100 的 enable_linear_mode 為 "yes"
      And 課程 100 有以下章節（扁平 menu_order 排序）：
        | chapterId | post_title | menu_order |
        | 201       | 1-1        | 10         |
        | 202       | 1-2        | 20         |
        | 203       | 1-3        | 30         |
      And 用戶 "Alice" 已被加入課程 100
      And 用戶 "Alice" 僅完成章節 201
      When 管理員 "Admin" 更新課程 100 的 enable_linear_mode 為 "no"
      Then 操作成功
      And 用戶 "Alice" 可存取章節 201, 202, 203（全部解鎖）
