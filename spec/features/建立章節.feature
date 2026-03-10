@ignore
Feature: 建立章節

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 系統中有以下章節：
      | chapterId | post_title | post_type  | post_parent | parent_course_id | menu_order |
      | 201       | 第一章：環境設定 | pc_chapter | 100     | 100              | 0          |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- parent_course_id 對應的課程必須存在

    Example: parent_course_id 對應不存在的課程時建立失敗
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        | 第一章     | 9999             | 9999        |
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  Rule: 前置（狀態）- parent_course_id 對應的課程 _is_course 必須為 yes

    Example: parent_course_id 對應非課程商品時建立失敗
      Given 系統中有一個 WooCommerce 商品 id 300，_is_course 為 "no"
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        | 第一章     | 300              | 300         |
      Then 操作失敗

  Rule: 前置（狀態）- 若 post_parent 為章節 ID 則該章節必須存在

    Example: post_parent 指向不存在的章節時建立失敗
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        | 第一節     | 100              | 9998        |
      Then 操作失敗，錯誤訊息包含 "找不到章節"

  Rule: 前置（狀態）- 若 post_parent 為章節 ID 則該章節 post_type 必須為 pc_chapter

    Example: post_parent 指向非章節文章時建立失敗
      Given 系統中有一個 post id 500，post_type 為 "post"
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        | 子章節     | 100              | 500         |
      Then 操作失敗

  # ========== 前置（參數）==========

  Rule: 前置（參數）- post_title 不可為空

    Example: 未提供章節名稱時建立失敗
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        |            | 100              | 100         |
      Then 操作失敗，錯誤訊息包含 "post_title"

  Rule: 前置（參數）- parent_course_id 不可為空

    Example: 未提供 parent_course_id 時建立失敗
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent |
        | 新章節     |                  | 100         |
      Then 操作失敗，錯誤訊息包含 "parent_course_id"

  Rule: 前置（參數）- chapter_video.type 必須為合法的影片類型

    Scenario Outline: 設定不合法的 chapter_video.type 時建立失敗
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent | chapter_video_type |
        | 影片章節   | 100              | 100         | <type>             |
      Then 操作失敗

      Examples:
        | type    |
        | mp4     |
        | hls     |
        | unknown |

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 建立 post_type 為 pc_chapter 的文章

    Example: 成功建立頂層章節（直屬課程）
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title   | parent_course_id | post_parent | chapter_video_type | chapter_length | enable_comment | menu_order |
        | 第一章：基礎概念 | 100           | 100         | none               | 0              | yes            | 1          |
      Then 操作成功
      And 新建章節的 post_type 應為 "pc_chapter"
      And 新建章節的 post_parent 應為 100
      And 新建章節的 post_title 應為 "第一章：基礎概念"

  Rule: 後置（狀態）- parent_course_id 儲存至 post_meta

    Example: 建立子章節時 parent_course_id 正確儲存至 meta
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title | parent_course_id | post_parent | chapter_video_type | menu_order |
        | 第一節     | 100              | 201         | youtube            | 0          |
      Then 操作成功
      And 新建章節的 parent_course_id meta 應為 100
      And 新建章節的 post_parent 應為 201

  Rule: 後置（狀態）- chapter_video 資料正確儲存

    Example: 建立帶有 Bunny 影片的章節
      When 管理員 "Admin" 建立章節，參數如下：
        | post_title    | parent_course_id | post_parent | chapter_video_type | chapter_video_id | chapter_length |
        | 環境安裝教學   | 100              | 100         | bunny              | abc-video-123    | 720            |
      Then 操作成功
      And 新建章節的 chapter_video meta 應包含 type "bunny"
      And 新建章節的 chapter_video meta 應包含 id "abc-video-123"
      And 新建章節的 chapter_length meta 應為 720
