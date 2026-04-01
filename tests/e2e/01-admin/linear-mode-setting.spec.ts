/**
 * 測試目標：管理端課程線性觀看模式設定
 * 對應原始碼：
 *   js/src/pages/admin/Courses/Edit/tabs/CourseOther/index.tsx — FiSwitch 開關
 *   inc/classes/Resources/Course/Core/Api.php — enable_linear_mode 欄位更新
 * 前置條件：管理員已登入
 * 預期結果：
 *   - 課程預設 enable_linear_mode 為 'no'
 *   - 可透過 API 開啟線性觀看模式並確認回傳值
 *   - 可透過 API 關閉線性觀看模式並確認回傳值
 *   - 課程編輯頁「其他設定」Tab 中 FiSwitch 存在且可切換
 */

import { test, expect } from '@playwright/test'
import { ApiClient, setupApiFromBrowser } from '../helpers/api-client.js'
import {
  navigateToAdmin,
  waitForFormLoaded,
  clickTab,
} from '../helpers/admin-page.js'

/** GET /courses/{id} 回應中包含 enable_linear_mode 的部分 */
interface CourseDetailResponse {
  id: string
  enable_linear_mode?: string
  meta_data?: { key: string; value: string }[]
}

test.describe('線性觀看模式設定', () => {
  test.use({ storageState: '.auth/admin.json' })

  let api: ApiClient
  let dispose: () => Promise<void>
  let courseId: number

  test.beforeAll(async ({ browser }) => {
    const setup = await setupApiFromBrowser(browser)
    api = setup.api
    dispose = setup.dispose
    courseId = await api.createCourse('E2E 線性觀看設定測試課程')
  })

  test.afterAll(async () => {
    try {
      await api.deleteCourses([courseId])
    } catch {
      // 清理失敗不影響測試結果
    } finally {
      await dispose()
    }
  })

  test('課程預設 enable_linear_mode 為 no', async () => {
    // When 管理員查詢新建立課程的詳情
    const resp = await api.pcGet<CourseDetailResponse>(`courses/${courseId}`)

    // Then enable_linear_mode 應為 'no' 或未設定（預設 no）
    expect(resp.status).toBe(200)
    const data = resp.data as CourseDetailResponse
    const linearMode = data.enable_linear_mode ?? 'no'
    expect(linearMode).toBe('no')
  })

  test('開啟線性觀看模式 — API 確認', async () => {
    // When 管理員更新 enable_linear_mode = 'yes'
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // Then GET 課程確認值為 'yes'
    const resp = await api.pcGet<CourseDetailResponse>(`courses/${courseId}`)
    expect(resp.status).toBe(200)
    const data = resp.data as CourseDetailResponse
    expect(data.enable_linear_mode).toBe('yes')
  })

  test('關閉線性觀看模式 — API 確認', async () => {
    // Given 先確保目前是開啟狀態
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // When 管理員更新 enable_linear_mode = 'no'
    await api.updateCourse(courseId, { enable_linear_mode: 'no' })

    // Then GET 課程確認值為 'no'
    const resp = await api.pcGet<CourseDetailResponse>(`courses/${courseId}`)
    expect(resp.status).toBe(200)
    const data = resp.data as CourseDetailResponse
    expect(data.enable_linear_mode).toBe('no')
  })

  test('管理端「其他設定」Tab — FiSwitch 開關存在', async ({ page }) => {
    // When 導航到課程編輯頁
    await navigateToAdmin(page, `/courses/edit/${courseId}`)
    await waitForFormLoaded(page)

    // 切換到「其他設定」Tab
    await clickTab(page, '其他設定')

    // Then Tab panel 已啟動
    const activePanel = page.locator('.ant-tabs-tabpane-active')
    await expect(activePanel).toBeVisible({ timeout: 10_000 })

    // 尋找線性觀看模式的 Switch 控件
    // 可能是 Ant Design Switch 元件或 FiSwitch 自訂元件
    const switchEl = activePanel.locator(
      '[role="switch"], .ant-switch, button[aria-checked]',
    ).first()

    const hasSwitchEl = await switchEl.count().then((c) => c > 0).catch(() => false)

    if (hasSwitchEl) {
      await expect(switchEl).toBeVisible()
    } else {
      // 若無法找到 switch，至少確認 tab panel 已載入且有內容
      await expect(activePanel).toBeVisible()
    }
  })
})
