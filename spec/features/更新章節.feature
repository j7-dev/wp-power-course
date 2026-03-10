@ignore
Feature: 更新章節

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 系統中有以下章節：
      | chapterId | post_title      | post_type  | post_parent | parent_course_id | chapter_video_type | chapter_length | enable_comment | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100         | 100              | none               | 0              | yes            | 0          |
      | 202       | 第二章：語法基礎 | pc_chapter | 100         | 100              | youtube            | 1800           | no             | 1          |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- 指定 id 對應的章節必須存在

    Example: 更新不存在的章節時操作失敗
      When 管理員 "Admin" 更新章節 9999，參數如下：
        | post_title   |
        | 不存在的章節 |
      Then 操作失敗，錯誤訊息包含 "找不到章節"

  Rule: 前置（狀態）- 指定章節的 post_type 必須為 pc_chapter

    Example: 更新非 pc_chapter 文章時操作失敗
      Given 系統中有一個 post id 500，post_type 為 "post"
      When 管理員 "Admin" 更新章節 500，參數如下：
        | post_title |
        | 一般文章   |
      Then 操作失敗，錯誤訊息包含 "post_type"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- id 不可為空

    Example: 未提供章節 id 時操作失敗
      When 管理員 "Admin" 更新章節（未指定 id），參數如下：
        | post_title |
        | 更新後標題 |
      Then 操作失敗

  Rule: 前置（參數）- chapter_video.type 若有設定必須為合法的影片類型

    Scenario Outline: 設定不合法的 chapter_video.type 時操作失敗
      When 管理員 "Admin" 更新章節 201，參數如下：
        | chapter_video_type |
        | <type>             |
      Then 操作失敗

      Examples:
        | type    |
        | rtmp    |
        | flash   |
        | unknown |

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 更新章節標題

    Example: 成功更新章節 post_title
      When 管理員 "Admin" 更新章節 201，參數如下：
        | post_title          |
        | 第一章：環境設定（修訂版）|
      Then 操作成功
      And 章節 201 的 post_title 應為 "第一章：環境設定（修訂版）"

  Rule: 後置（狀態）- 更新章節影片資料

    Example: 成功更新章節影片類型與 ID
      When 管理員 "Admin" 更新章節 201，參數如下：
        | chapter_video_type | chapter_video_id | chapter_length |
        | bunny              | vid-001-hls      | 1200           |
      Then 操作成功
      And 章節 201 的 chapter_video meta 應包含 type "bunny"
      And 章節 201 的 chapter_video meta 應包含 id "vid-001-hls"
      And 章節 201 的 chapter_length meta 應為 1200

  Rule: 後置（狀態）- 更新評論開關設定

    Example: 成功關閉章節評論
      When 管理員 "Admin" 更新章節 201，參數如下：
        | enable_comment |
        | no             |
      Then 操作成功
      And 章節 201 的 enable_comment meta 應為 "no"

  Rule: 後置（狀態）- 僅更新有傳入的欄位，其他欄位保持不變

    Example: 只更新標題時其他 meta 不受影響
      When 管理員 "Admin" 更新章節 202，參數如下：
        | post_title       |
        | 第二章：語法基礎（v2）|
      Then 操作成功
      And 章節 202 的 chapter_video_type meta 應保持 "youtube"
      And 章節 202 的 chapter_length meta 應保持 1800
      And 章節 202 的 enable_comment meta 應保持 "no"
