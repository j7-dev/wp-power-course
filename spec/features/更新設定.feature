@ignore
Feature: 更新設定

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統目前的 power_course_settings 設定如下：
      | key                          | value     |
      | course_access_trigger        | completed |
      | hide_myaccount_courses       | no        |
      | fix_video_and_tabs_mobile    | no        |
      | pc_header_offset             | 0         |
      | hide_courses_in_main_query   | no        |
      | hide_courses_in_search_result| no        |
      | pc_watermark_qty             | 0         |
      | pc_watermark_text            | {display_name} {post_title} IP:{ip} |
      | pc_pdf_watermark_qty         | 0         |
      | pc_pdf_watermark_text        |           |

  # ========== 前置（參數）==========

  Rule: 前置（參數）- course_access_trigger 必須是有效的 WooCommerce 訂單狀態

    Scenario Outline: 設定不合法的訂單狀態時操作失敗
      When 管理員 "Admin" 更新設定，參數如下：
        | course_access_trigger |
        | <trigger>             |
      Then 操作失敗，錯誤訊息包含 "course_access_trigger"

      Examples:
        | trigger    |
        | done       |
        | finish     |
        | paid       |

  Rule: 前置（參數）- pc_watermark_qty 必須為非負整數

    Scenario Outline: 設定不合法的浮水印數量時操作失敗
      When 管理員 "Admin" 更新設定，參數如下：
        | pc_watermark_qty |
        | <qty>            |
      Then 操作失敗，錯誤訊息包含 "pc_watermark_qty"

      Examples:
        | qty  |
        | -1   |
        | -5   |
        | abc  |

  Rule: 前置（參數）- pc_pdf_watermark_qty 必須為非負整數

    Example: 設定負數 pdf 浮水印數量時操作失敗
      When 管理員 "Admin" 更新設定，參數如下：
        | pc_pdf_watermark_qty |
        | -3                   |
      Then 操作失敗，錯誤訊息包含 "pc_pdf_watermark_qty"

  Rule: 前置（參數）- 未知屬性會被忽略而不導致失敗

    Example: 傳入未知屬性時操作仍成功，未知屬性不被儲存
      When 管理員 "Admin" 更新設定，參數如下：
        | hide_myaccount_courses | unknown_key |
        | yes                    | some_value  |
      Then 操作成功
      And 設定 hide_myaccount_courses 應為 "yes"

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 成功更新 course_access_trigger

    Example: 將開通觸發狀態改為 processing
      When 管理員 "Admin" 更新設定，參數如下：
        | course_access_trigger |
        | processing            |
      Then 操作成功
      And power_course_settings 中 course_access_trigger 應為 "processing"

  Rule: 後置（狀態）- 成功更新影片浮水印設定

    Example: 開啟影片浮水印功能並設定文字模板
      When 管理員 "Admin" 更新設定，參數如下：
        | pc_watermark_qty | pc_watermark_text                     |
        | 3                | {display_name} {email} IP:{ip}        |
      Then 操作成功
      And power_course_settings 中 pc_watermark_qty 應為 3
      And power_course_settings 中 pc_watermark_text 應為 "{display_name} {email} IP:{ip}"

  Rule: 後置（狀態）- 成功更新課程隱藏相關設定

    Example: 開啟隱藏主查詢與搜尋結果
      When 管理員 "Admin" 更新設定，參數如下：
        | hide_courses_in_main_query | hide_courses_in_search_result |
        | yes                        | yes                           |
      Then 操作成功
      And power_course_settings 中 hide_courses_in_main_query 應為 "yes"
      And power_course_settings 中 hide_courses_in_search_result 應為 "yes"

  Rule: 後置（狀態）- 未傳入的設定欄位保持原值不變

    Example: 只更新一個設定時其他設定保持不變
      When 管理員 "Admin" 更新設定，參數如下：
        | fix_video_and_tabs_mobile |
        | yes                       |
      Then 操作成功
      And power_course_settings 中 fix_video_and_tabs_mobile 應為 "yes"
      And power_course_settings 中 course_access_trigger 應保持 "completed"
      And power_course_settings 中 pc_watermark_qty 應保持 0
