@ignore @constraint
Feature: 外部課程功能隔離

  外部課程不參與站內教學相關功能：
  不出現在營收報表、學員管理、自動郵件、自動授權等流程中。
  後台編輯頁隱藏不適用的功能區塊。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下外部課程：
      | courseId | name            | product_type | status  | external_url                   |
      | 200      | Python 資料科學 | external     | publish | https://hahow.in/courses/12345 |
    And 系統中有以下站內課程：
      | courseId | name       | product_type | status  | price |
      | 100      | PHP 基礎課 | simple       | publish | 1200  |

  # ========== 學員管理隔離 ==========

  Rule: 外部課程不出現在學員管理的課程篩選選項中

    Example: 學員管理頁面課程篩選不含外部課程
      When 管理員 "Admin" 取得學員管理的課程選項列表
      Then 選項中應包含 "PHP 基礎課"
      And 選項中不應包含 "Python 資料科學"

  Rule: 外部課程不可新增學員

    Example: 嘗試為外部課程新增學員時失敗
      When 管理員 "Admin" 為課程 200 新增學員 user_ids [10]
      Then 操作失敗

  # ========== 自動郵件隔離 ==========

  Rule: 外部課程不可被選為自動郵件的目標課程

    Example: 建立郵件模板時課程選項不含外部課程
      When 管理員 "Admin" 取得郵件模板的課程選項列表
      Then 選項中應包含 "PHP 基礎課"
      And 選項中不應包含 "Python 資料科學"

  # ========== 自動授權隔離 ==========

  Rule: 外部課程不可被加入自動授權課程清單

    Example: 設定自動授權課程時不可選擇外部課程
      When 管理員 "Admin" 更新設定，auto_grant_course_ids 包含 [200]
      Then 操作失敗，錯誤訊息包含 "external"

  # ========== 章節管理隔離 ==========

  Rule: 外部課程不可新增章節

    Example: 嘗試為外部課程建立章節時失敗
      When 管理員 "Admin" 為課程 200 建立章節，參數如下：
        | post_title |
        | 第一章     |
      Then 操作失敗

  # ========== 銷售方案隔離 ==========

  Rule: 外部課程不可建立銷售方案

    Example: 嘗試為外部課程建立銷售方案時失敗
      When 管理員 "Admin" 為課程 200 建立銷售方案，參數如下：
        | name       | price |
        | 年度方案   | 3000  |
      Then 操作失敗

  # ========== 後台編輯頁 UI 隔離 ==========

  Rule: 後台 - 外部課程編輯頁隱藏不適用的 Tab

    Example: 外部課程編輯頁只顯示適用的 Tab
      When 管理員 "Admin" 進入外部課程 200 的編輯頁
      Then 應顯示以下 Tab：課程介紹、課程價格、其他設定
      And 不應顯示以下 Tab：章節管理、銷售方案、學員管理、課程分析

  Rule: 後台 - 外部課程編輯頁顯示外部連結設定欄位

    Example: 外部課程編輯頁包含外部連結欄位
      When 管理員 "Admin" 進入外部課程 200 的編輯頁
      Then 應顯示「外部連結」輸入欄位
      And 應顯示「按鈕文字」輸入欄位

  # ========== 後台列表標記 ==========

  Rule: 後台 - 課程列表中外部課程有明確識別標記

    Example: 後台課程列表可辨識外部課程
      When 管理員 "Admin" 查詢課程列表
      Then 課程 "Python 資料科學" 應標記為外部課程
      And 課程 "PHP 基礎課" 不應標記為外部課程
