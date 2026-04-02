@ignore @command
Feature: 設定課程線性觀看

  管理員可以在課程的「其他設定」分頁中，開啟或關閉「線性觀看」功能。
  開啟後，學員必須依照章節的排序順序（menu_order）完成前一個章節，才能進入下一個章節。
  包含父章節（depth 0）在順序中，也需要被「完成」才能繼續。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_sequential |
      | 100      | PHP 基礎課 | yes        | publish | no                |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes

    Example: 不存在的課程更新失敗
      When 管理員 "Admin" 更新課程 9999 的線性觀看設定為 "yes"
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- enable_sequential 必須為 "yes" 或 "no"

    Example: 無效值時操作失敗
      When 管理員 "Admin" 更新課程 100 的線性觀看設定為 "invalid"
      Then 操作失敗

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功開啟線性觀看

    Example: 成功將 enable_sequential 設為 yes
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential |
        | yes               |
      Then 操作成功
      And 課程 100 的 enable_sequential 應為 "yes"

  Rule: 後置（狀態）- 成功關閉線性觀看

    Example: 成功將 enable_sequential 設為 no
      Given 課程 100 的 enable_sequential 為 "yes"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential |
        | no                |
      Then 操作成功
      And 課程 100 的 enable_sequential 應為 "no"

  Rule: 後置（狀態）- 關閉線性觀看不影響學員既有進度

    Example: 關閉後學員進度不變
      Given 課程 100 的 enable_sequential 為 "yes"
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent | menu_order |
        | 200       | 1-1        | 100         | 1          |
        | 201       | 1-2        | 100         | 2          |
      And 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      When 管理員 "Admin" 更新課程 100，參數如下：
        | enable_sequential |
        | no                |
      Then 操作成功
      And 用戶 "Alice" 在章節 200 的 finished_at 應不為空
      And 用戶 "Alice" 在課程 100 的進度應為 50

  Rule: 後置（狀態）- 新建課程時 enable_sequential 預設為 no

    Example: 新課程預設關閉線性觀看
      When 管理員 "Admin" 建立課程，參數如下：
        | name     | _is_course |
        | 新課程   | yes        |
      Then 操作成功
      And 新課程的 enable_sequential 應為 "no"
