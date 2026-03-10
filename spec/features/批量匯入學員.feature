@ignore
Feature: 批量匯入學員

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
    And 系統中有以下課程：
      | courseId | name         | _is_course | status  |
      | 100      | PHP 基礎入門 | yes        | publish |
    And 系統中已存在以下用戶（可被匯入 CSV 找到）：
      | userId | email              | display_name |
      | 20     | alice@test.com     | Alice        |
      | 21     | bob@test.com       | Bob          |

  # ========== 前置（狀態）==========

  Rule: 前置（狀態）- course_id 對應的課程必須存在

    Example: 指定不存在的課程 id 時操作失敗
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 9999：
        """
        email,expire_date
        alice@test.com,0
        """
      Then 操作失敗，錯誤訊息包含 "找不到課程"

  # ========== 前置（參數）==========

  Rule: 前置（參數）- CSV 必須包含 email 欄位

    Example: CSV 缺少 email 欄位時操作失敗
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        display_name,expire_date
        Alice,0
        """
      Then 操作失敗，錯誤訊息包含 "email"

  Rule: 前置（參數）- CSV 每列的 email 格式必須合法

    Example: CSV 中有格式不合法的 email 時跳過該筆並記錄錯誤
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        alice@test.com,0
        not-an-email,0
        bob@test.com,0
        """
      Then 操作部分成功
      And 回應中成功筆數應為 2
      And 回應中失敗筆數應為 1
      And 失敗紀錄應包含 "not-an-email" 的錯誤說明

  Rule: 前置（參數）- expire_date 若有提供必須合法（0 或 10 位 timestamp）

    Example: CSV 中 expire_date 格式不合法時跳過該筆
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        alice@test.com,99999
        """
      Then 操作部分成功
      And 失敗紀錄應包含 "alice@test.com" 的 expire_date 錯誤說明

  # ========== 後置（狀態）==========

  Rule: 後置（狀態）- 已存在的 email 直接加入課程，不建立新用戶

    Example: CSV 中 email 已存在時直接使用現有用戶 id
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        alice@test.com,0
        """
      Then 操作成功
      And 用戶 alice@test.com（userId 20）的 avl_course_ids 應包含課程 100
      And 系統中用戶總數不應增加

  Rule: 後置（狀態）- 不存在的 email 建立新 WP 用戶後再加入課程

    Example: CSV 中 email 不存在時自動建立新用戶
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        newstudent@test.com,0
        """
      Then 操作成功
      And 系統中應新增 email 為 "newstudent@test.com" 的用戶
      And 新用戶的 avl_course_ids 應包含課程 100

  Rule: 後置（狀態）- 以 Action Scheduler 背景任務執行批次處理

    Example: 上傳多筆 CSV 後回傳背景任務 ID
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        alice@test.com,0
        bob@test.com,1893456000
        """
      Then 操作成功
      And 回應中應包含 Action Scheduler 任務 id 或批次執行結果

  Rule: 後置（狀態）- 回傳成功與失敗的筆數統計

    Example: 匯入混合合法與不合法資料後回傳統計摘要
      When 管理員 "Admin" 上傳 CSV 並指定課程 id 100：
        """
        email,expire_date
        alice@test.com,0
        invalid-email,0
        bob@test.com,1893456000
        """
      Then 操作完成
      And 回應中應包含：
        | 欄位          | 期望值 |
        | success_count | 2      |
        | error_count   | 1      |
