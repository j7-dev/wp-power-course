@ignore
Feature: 刪除課程

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
      | 101      | React 實戰課 | yes        | publish |
      | 102      | Vue 完整課程 | yes        | draft   |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 所有 ids 對應的課程必須存在

    Example: 刪除清單中包含不存在的課程時操作失敗
      When 管理員 "Admin" 刪除課程，ids 為 [100, 9999]
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- ids 不可為空陣列

    Example: 提供空陣列時操作失敗
      When 管理員 "Admin" 刪除課程，ids 為 []
      Then 操作失敗，錯誤訊息包含 "ids"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 課程商品被刪除（wp_delete_post）

    Example: 成功刪除單一課程
      When 管理員 "Admin" 刪除課程，ids 為 [100]
      Then 操作成功
      And 課程 100 在資料庫中應不再存在

  Rule: 後置（狀態）- 支援批量刪除多個課程

    Example: 成功批量刪除多個課程
      When 管理員 "Admin" 刪除課程，ids 為 [101, 102]
      Then 操作成功
      And 課程 101 在資料庫中應不再存在
      And 課程 102 在資料庫中應不再存在

  Rule: 後置（狀態）- 關聯的章節記錄不自動刪除

    Example: 刪除課程後其章節記錄仍存在
      Given 課程 100 下有章節：
        | chapterId | post_title | post_type  |
        | 201       | 第一章     | pc_chapter |
      When 管理員 "Admin" 刪除課程，ids 為 [100]
      Then 操作成功
      And 章節 201 在資料庫中應仍然存在

  Rule: 後置（狀態）- 關聯的學員選課記錄不自動刪除

    Example: 刪除課程後學員的 avl_course_ids meta 仍然存在
      Given 學員 userId 5 有 avl_course_ids meta 值為 100
      When 管理員 "Admin" 刪除課程，ids 為 [100]
      Then 操作成功
      And 學員 userId 5 的 avl_course_ids meta 應仍然包含 100
