@ignore @read-model
Feature: 線性觀看章節解鎖判定

  當課程開啟線性觀看模式後，系統根據學員的完成進度決定每個章節的解鎖狀態。
  解鎖規則：章節按照扁平化的 menu_order 排序，前一章已完成才能解鎖下一章。
  所有章節（含父章節）都參與線性順序判定，不區分是否有子章節。

  Background:
    Given 系統中有以下用戶：
      | userId | name  | email          | role          |
      | 1      | Admin | admin@test.com | administrator |
      | 2      | Alice | alice@test.com | subscriber    |
    And 系統中有以下課程：
      | courseId | name       | _is_course | status  | enable_linear_mode |
      | 100      | PHP 基礎課 | yes        | publish | yes                |
    And 課程 100 有以下章節（扁平 menu_order 排序）：
      | chapterId | post_title | post_parent | menu_order |
      | 201       | 第一章     | 100         | 10         |
      | 202       | 1-1        | 201         | 20         |
      | 203       | 1-2        | 201         | 30         |
      | 204       | 第二章     | 100         | 40         |
      | 205       | 2-1        | 204         | 50         |
    And 用戶 "Alice" 已被加入課程 100，expire_date 0

  # ========== 基本解鎖規則 ==========

  Rule: 第一個章節（menu_order 最小）永遠解鎖

    Example: 無任何完成記錄時第一章為解鎖
      Given 用戶 "Alice" 無任何章節完成記錄
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 201（第一章）應為「已解鎖」
      And 章節 202（1-1）應為「已鎖定」
      And 章節 203（1-2）應為「已鎖定」
      And 章節 204（第二章）應為「已鎖定」
      And 章節 205（2-1）應為「已鎖定」

  Rule: 前一章已完成時下一章解鎖

    Example: 完成第一章後 1-1 解鎖
      Given 用戶 "Alice" 已完成章節 201
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 201（第一章）應為「已解鎖」
      And 章節 202（1-1）應為「已解鎖」
      And 章節 203（1-2）應為「已鎖定」

    Example: 依次完成後逐步解鎖
      Given 用戶 "Alice" 已完成章節 201, 202, 203
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 204（第二章）應為「已解鎖」
      And 章節 205（2-1）應為「已鎖定」

  # ========== 已完成章節保持可存取 ==========

  Rule: 已完成的章節永遠保持解鎖（不受順序影響）

    Example: 中途開啟線性觀看，已完成的跳序章節仍可存取
      # 情境：Alice 在自由模式下完成了 201 和 203（跳過 202），之後管理員才開啟線性觀看
      Given 用戶 "Alice" 已完成章節 201, 203
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 201（第一章）應為「已解鎖」
      And 章節 202（1-1）應為「已解鎖」（因前一章 201 已完成）
      And 章節 203（1-2）應為「已解鎖」（因本身已完成）
      And 章節 204（第二章）應為「已鎖定」（因前一章 203 雖已完成但 202 未完成，需依扁平順序）

  # 補充說明：解鎖邏輯為 "前一章已完成 OR 本章已完成"
  # 203 雖已完成所以可存取，但 204 的前一章是 203，203 已完成，
  # 不過嚴格來說 202 未完成意味著 203 不應該被視為「正常解鎖」，
  # 但因為 203 本身已完成所以保持可存取。
  # 204 解鎖條件是 203 已完成 → 203 的確已完成 → 但 202 未完成。
  # 根據需求：「已完成的章節不會被重新鎖定」，但 204 本身未完成且前一章 203 的完成
  # 並不代表 202 也完成了。此情境需要進一步確認判定邏輯。

  Rule: 解鎖判定公式：章節 N 已解鎖 = (N 是第一章) OR (N 本身已完成) OR (N-1 已完成)

    Example: 完整順序完成所有章節
      Given 用戶 "Alice" 已完成章節 201, 202, 203, 204, 205
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 所有章節均為「已解鎖」

  # ========== 父章節參與線性順序 ==========

  Rule: 所有章節（含父章節）皆參與線性順序，不區分是否有子章節

    Example: 父章節未完成時其子章節被鎖定
      Given 用戶 "Alice" 無任何章節完成記錄
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 201（第一章，父章節）應為「已解鎖」
      And 章節 202（1-1，子章節）應為「已鎖定」

    Example: 父章節完成後其子章節解鎖
      Given 用戶 "Alice" 已完成章節 201（第一章，父章節）
      When 查詢用戶 "Alice" 在課程 100 的章節解鎖狀態
      Then 章節 202（1-1，子章節）應為「已解鎖」

  # ========== 管理員與講師繞過 ==========

  Rule: 具有 manage_woocommerce 能力的用戶繞過所有鎖定

    Example: 管理員可存取所有鎖定章節
      Given 用戶 "Admin" 無任何章節完成記錄
      When 查詢用戶 "Admin" 在課程 100 的章節解鎖狀態
      Then 所有章節均為「已解鎖」

  Rule: 課程作者（講師）繞過鎖定

    Example: 講師可存取所有鎖定章節
      Given 系統中有以下用戶：
        | userId | name    | email            | role          |
        | 3      | Teacher | teacher@test.com | author        |
      And 用戶 "Teacher" 為課程 100 的作者
      When 查詢用戶 "Teacher" 在課程 100 的章節解鎖狀態
      Then 所有章節均為「已解鎖」

  # ========== 未開啟線性觀看的課程 ==========

  Rule: 未開啟線性觀看的課程，所有章節均為已解鎖

    Example: 一般課程不受線性限制
      Given 系統中有以下課程：
        | courseId | name       | _is_course | status  | enable_linear_mode |
        | 300      | CSS 進階課 | yes        | publish | no                 |
      And 課程 300 有以下章節：
        | chapterId | post_title | menu_order |
        | 301       | 基礎篇     | 10         |
        | 302       | 進階篇     | 20         |
      And 用戶 "Alice" 已被加入課程 300
      And 用戶 "Alice" 無任何章節完成記錄
      When 查詢用戶 "Alice" 在課程 300 的章節解鎖狀態
      Then 所有章節均為「已解鎖」
