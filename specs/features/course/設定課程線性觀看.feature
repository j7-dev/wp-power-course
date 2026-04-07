@ignore @command
Feature: 設定課程線性觀看

  管理員可以在課程設定中啟用或關閉「線性觀看」模式（sequential_mode）。
  此設定儲存於課程商品的 postmeta，預設為 'no'（關閉）。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | sequential_mode |
      | 100      | PHP 基礎課 | yes        | publish | no              |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 課程必須存在且為課程商品

    Example: 課程不存在時設定失敗
      When 管理員 "Admin" 更新課程 9999，設定 sequential_mode 為 "yes"
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- sequential_mode 必須為 'yes' 或 'no'

    Example: sequential_mode 值不合法時設定失敗
      When 管理員 "Admin" 更新課程 100，設定 sequential_mode 為 "maybe"
      Then 操作失敗，錯誤訊息包含 "sequential_mode"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 啟用線性觀看

    Example: 成功啟用 sequential_mode
      When 管理員 "Admin" 更新課程 100，設定 sequential_mode 為 "yes"
      Then 操作成功
      And 課程 100 的 sequential_mode 應為 "yes"
      And 重新進入設定頁面，sequential_mode 開關顯示為「啟用」

  Rule: 後置（狀態）- 關閉線性觀看

    Example: 成功關閉 sequential_mode
      Given 課程 100 的 sequential_mode 為 "yes"
      When 管理員 "Admin" 更新課程 100，設定 sequential_mode 為 "no"
      Then 操作成功
      And 課程 100 的 sequential_mode 應為 "no"

  Rule: 後置（狀態）- 啟用後既有學員的已完成進度不受影響

    Example: 啟用線性觀看不會清除學員已完成的章節紀錄
      Given 用戶 "Alice" 已被加入課程 100，expire_date 0
      And 課程 100 有以下章節：
        | chapterId | post_title | post_parent |
        | 200       | 第一章     | 100         |
        | 201       | 第二章     | 100         |
        | 202       | 第三章     | 100         |
      And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
      And 用戶 "Alice" 在章節 202 的 finished_at 為 "2025-06-01 12:00:00"
      When 管理員 "Admin" 更新課程 100，設定 sequential_mode 為 "yes"
      Then 操作成功
      And 用戶 "Alice" 在章節 200 的 finished_at 應不為空
      And 用戶 "Alice" 在章節 202 的 finished_at 應不為空
      And 章節 201 對用戶 "Alice" 為可存取（因前面的 200 已完成）
      And 章節 202 對用戶 "Alice" 為可存取（因本身已完成）

  Rule: 後置（狀態）- sequential_mode 預設為 'no'

    Example: 新建課程的 sequential_mode 預設為 no
      When 管理員 "Admin" 建立一門新課程
      Then 新課程的 sequential_mode 應為 "no"
