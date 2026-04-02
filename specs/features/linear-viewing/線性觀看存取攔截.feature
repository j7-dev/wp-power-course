@ignore @read-model
Feature: 線性觀看存取攔截

  當課程開啟線性觀看模式後，學員存取鎖定章節時，
  系統在 PHP 模板層和 REST API 層進行雙重攔截，阻止存取內容。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                |
    And 課程 100 有以下章節（扁平 menu_order 排序）：
      | chapterId | post_title | menu_order |
      | 201       | 1-1        | 10         |
      | 202       | 1-2        | 20         |
      | 203       | 1-3        | 30         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0
    And 用戶 "Alice" 無任何章節完成記錄

  # ========== PHP 模板層攔截 ==========

  Rule: 學員透過 URL 存取鎖定章節時，模板層顯示鎖定提示頁

    Example: 點擊鎖定章節顯示鎖定提示
      When 用戶 "Alice" 造訪章節 202 的 permalink
      Then 頁面應顯示鎖定提示畫面
      And 提示訊息應包含「請先完成前面的章節」
      And 提示訊息應指出需要先完成的章節名稱「1-1」
      And 頁面不應載入章節 202 的影片內容
      And 頁面不應載入章節 202 的文字內容

    Example: 直接輸入 URL 存取鎖定章節
      When 用戶 "Alice" 直接在瀏覽器輸入章節 203 的 URL "/classroom/1-3"
      Then 頁面應顯示鎖定提示畫面
      And 提示訊息應指出需要先完成的章節名稱「1-2」

  Rule: 第一個章節永遠可正常存取

    Example: 第一個章節正常載入
      When 用戶 "Alice" 造訪章節 201 的 permalink
      Then 頁面應正常載入章節 201 的內容
      And 頁面應顯示影片播放器
      And 頁面應顯示「標示為已完成」按鈕

  Rule: 管理員存取鎖定章節時正常顯示內容

    Example: 管理員可正常存取鎖定章節
      When 用戶 "Admin" 造訪章節 203 的 permalink
      Then 頁面應正常載入章節 203 的內容

  # ========== REST API 層攔截 ==========

  Rule: 鎖定章節的內容相關 API 回傳 403

    Example: API 取得鎖定章節內容被攔截
      When 用戶 "Alice" 透過 REST API 取得章節 202 的內容
      Then API 回傳 403
      And 錯誤訊息包含「章節已鎖定」

  # ========== 章節側邊欄視覺提示 ==========

  Rule: 教室側邊欄中鎖定章節顯示鎖頭圖示

    Example: 側邊欄顯示鎖定/解鎖狀態
      When 用戶 "Alice" 造訪章節 201（已解鎖）
      Then 側邊欄章節列表中：
        | chapterId | post_title | 圖示狀態 |
        | 201       | 1-1        | 影片圖示 |
        | 202       | 1-2        | 鎖頭圖示 |
        | 203       | 1-3        | 鎖頭圖示 |

    Example: 完成章節後側邊欄更新
      Given 用戶 "Alice" 已完成章節 201
      When 用戶 "Alice" 造訪章節 202（已解鎖）
      Then 側邊欄章節列表中：
        | chapterId | post_title | 圖示狀態 |
        | 201       | 1-1        | 完成圖示 |
        | 202       | 1-2        | 影片圖示 |
        | 203       | 1-3        | 鎖頭圖示 |

  # ========== 鎖定提示訊息 ==========

  Rule: 鎖定提示訊息應友善且清楚，包含下一步指引

    Example: 提示訊息內容
      When 用戶 "Alice" 造訪鎖定的章節 203
      Then 鎖定提示畫面應包含：
        | 元素       | 內容                                     |
        | 鎖頭圖示   | 顯示鎖頭 SVG 圖示                         |
        | 主標題     | 「此章節尚未解鎖」                         |
        | 說明文字   | 「請先完成『1-2』章節，即可解鎖此章節」       |
        | 導引按鈕   | 「前往上一章節」連結，指向章節 202 的 permalink |
