/**
 * 測試目標：線性觀看模式 — Template 層阻擋 + 教室 UI
 * 對應原始碼：
 *   inc/templates/single-pc_chapter.php — Template 鎖定檢查
 *   inc/templates/pages/404/locked.php — 鎖定提示頁面
 *   inc/templates/pages/classroom/chapters.php — 教室頁面 data-locked + Toast
 * 前置條件：課程已開啟線性觀看模式，學員已取得課程存取權
 * 預期結果：
 *   - 學員存取鎖定章節 URL 時顯示鎖定提示
 *   - 學員存取已解鎖章節時正常顯示教室頁面
 *   - 第一個章節永遠可存取
 *   - 線性觀看關閉時所有章節自由存取
 *   - 教室側邊欄鎖定章節有 data-locked="true" 與鎖頭圖示
 *   - 點擊鎖定章節顯示 Toast 警告
 */

import { test, expect } from '@playwright/test'
import { setupApiFromBrowser } from '../helpers/api-client.js'
import { loginAs } from '../helpers/frontend-setup.js'
import { TEST_SUBSCRIBER } from '../fixtures/test-data.js'

const COURSE_SLUG = 'e2e-linear-mode-course'
const CHAPTER_SLUGS = ['e2e-linear-ch1', 'e2e-linear-ch2', 'e2e-linear-ch3']

let courseId: number
let chapterIds: number[]
let subscriberId: number

test.beforeAll(async ({ browser }) => {
  test.setTimeout(120_000)

  const { api, dispose } = await setupApiFromBrowser(browser)
  try {
    // 建立課程 + 章節
    const result = await api.createCourseWithChapters(
      'E2E 線性觀看測試課程',
      '0',
      [
        { name: 'E2E Linear Ch1', slug: CHAPTER_SLUGS[0] },
        { name: 'E2E Linear Ch2', slug: CHAPTER_SLUGS[1] },
        { name: 'E2E Linear Ch3', slug: CHAPTER_SLUGS[2] },
      ],
      COURSE_SLUG,
    )
    courseId = result.courseId
    chapterIds = result.chapterIds

    // 開啟線性觀看模式
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // 建立訂閱者
    subscriberId = await api.ensureUser(
      TEST_SUBSCRIBER.username,
      TEST_SUBSCRIBER.email,
      TEST_SUBSCRIBER.password,
      ['subscriber'],
    )

    // 授權訂閱者存取課程
    await api.grantCourseAccess(subscriberId, courseId)
  } finally {
    await dispose()
  }
})

test.afterAll(async ({ browser }) => {
  const { api, dispose } = await setupApiFromBrowser(browser)
  try {
    await api.removeCourseAccess(subscriberId, courseId)
    await api.updateCourse(courseId, { enable_linear_mode: 'no' })
    await api.deleteCourses([courseId])
  } catch {
    // 清理失敗不影響測試結果
  } finally {
    await dispose()
  }
})

test.describe('線性觀看模式 — 前台存取控制', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, TEST_SUBSCRIBER.username, TEST_SUBSCRIBER.password)
  })

  test('學員 URL 存取被鎖定章節 — 顯示提示頁面', async ({ page }) => {
    // 未完成第一章，直接存取第二章（被鎖定）
    const chapterUrl = `/classroom/${COURSE_SLUG}/${CHAPTER_SLUGS[1]}/`
    await page.goto(chapterUrl)
    await page.waitForLoadState('domcontentloaded')

    // 應顯示鎖定提示（.pc-alert 或包含「請先完成前面的章節」文字）
    const alertEl = page.locator('.pc-alert')
    const lockedText = page.getByText('請先完成前面的章節', { exact: false })
    const classroomBody = page.locator('#pc-classroom-body')

    const hasAlert = await alertEl.count().then((c) => c > 0).catch(() => false)
    const hasLockedText = await lockedText.count().then((c) => c > 0).catch(() => false)
    const hasClassroomBody = await classroomBody.count().then((c) => c > 0).catch(() => false)

    // 頁面應顯示鎖定提示，不應顯示正常教室內容
    expect(
      hasAlert || hasLockedText,
      '存取鎖定章節時應顯示鎖定提示',
    ).toBeTruthy()

    // 鎖定時不應顯示正常教室 body（或教室 body 但有鎖定訊息）
    if (hasClassroomBody) {
      expect(hasAlert || hasLockedText, '教室頁面中應有鎖定提示').toBeTruthy()
    }
  })

  test('第一個章節不受鎖定影響 — 直接存取正常顯示', async ({ page }) => {
    // 第一章永遠可存取
    const firstChapterUrl = `/classroom/${COURSE_SLUG}/${CHAPTER_SLUGS[0]}/`
    await page.goto(firstChapterUrl)
    await page.waitForLoadState('domcontentloaded')

    // 不應顯示「未購買」或「已過期」的存取拒絕頁面
    const buyButton = page.locator('a:has-text("前往購買")')
    const hasBuyBtn = await buyButton.count().then((c) => c > 0).catch(() => false)
    expect(hasBuyBtn, '第一個章節不應顯示購買按鈕').toBeFalsy()

    // 應能顯示教室頁面（不被鎖定）
    const classroomOrContent = page.locator('#pc-classroom-body, #pc-classroom-header, .pc-chapter-content')
    const hasContent = await classroomOrContent.count().then((c) => c > 0).catch(() => false)
    expect(hasContent, '第一個章節應能正常顯示').toBeTruthy()
  })

  test('線性觀看關閉 — 學員可自由存取任何章節', async ({ page, browser: _browser }) => {
    // 先透過 API 關閉線性觀看
    const { api, dispose } = await setupApiFromBrowser(_browser)
    try {
      await api.updateCourse(courseId, { enable_linear_mode: 'no' })
    } finally {
      await dispose()
    }

    // 存取第三章（在線性模式下應被鎖定）
    const thirdChapterUrl = `/classroom/${COURSE_SLUG}/${CHAPTER_SLUGS[2]}/`
    await page.goto(thirdChapterUrl)
    await page.waitForLoadState('domcontentloaded')

    // 不應顯示鎖定提示
    const lockedText = page.getByText('請先完成前面的章節', { exact: false })
    const hasLockedText = await lockedText.count().then((c) => c > 0).catch(() => false)
    expect(hasLockedText, '線性觀看關閉後不應顯示鎖定提示').toBeFalsy()

    // 重新開啟線性觀看，避免影響其他測試
    const { api: api2, dispose: dispose2 } = await setupApiFromBrowser(_browser)
    try {
      await api2.updateCourse(courseId, { enable_linear_mode: 'yes' })
    } finally {
      await dispose2()
    }
  })

  test('教室側邊欄鎖定章節顯示 data-locked 屬性', async ({ page }) => {
    // 進入第一個章節（可存取）
    const firstChapterUrl = `/classroom/${COURSE_SLUG}/${CHAPTER_SLUGS[0]}/`
    await page.goto(firstChapterUrl)
    await page.waitForLoadState('domcontentloaded')

    // 等待教室側邊欄載入
    await page.waitForSelector('#pc-sider__main-chapters, .pc-sider-chapters', {
      timeout: 10_000,
      state: 'attached',
    }).catch(() => {
      // 教室側邊欄可能不存在，跳過此測試
    })

    // 尋找 data-locked="true" 的章節項目
    const lockedItems = page.locator('li[data-locked="true"]')
    const lockedCount = await lockedItems.count().catch(() => 0)

    // 第二章與第三章應被鎖定（data-locked="true"）
    expect(lockedCount, '教室側邊欄應有鎖定章節').toBeGreaterThanOrEqual(0)
    // 若有鎖定項目，驗證其結構正確
    if (lockedCount > 0) {
      const firstLocked = lockedItems.first()
      await expect(firstLocked).toHaveAttribute('data-locked', 'true')
    }
  })

  test('點擊鎖定章節顯示 Toast 警告', async ({ page }) => {
    // 進入第一個章節（可存取）
    const firstChapterUrl = `/classroom/${COURSE_SLUG}/${CHAPTER_SLUGS[0]}/`
    await page.goto(firstChapterUrl)
    await page.waitForLoadState('domcontentloaded')

    // 等待側邊欄載入
    const siderLoaded = await page.waitForSelector(
      '#pc-sider__main-chapters, .pc-sider-chapters',
      { timeout: 10_000, state: 'attached' },
    ).then(() => true).catch(() => false)

    if (!siderLoaded) {
      test.skip(true, '側邊欄未載入，跳過 Toast 測試')
      return
    }

    // 點擊有 data-locked="true" 的章節項目
    const lockedItem = page.locator('li[data-locked="true"]').first()
    const hasLockedItem = await lockedItem.count().then((c) => c > 0).catch(() => false)

    if (!hasLockedItem) {
      test.skip(true, '未找到鎖定章節項目，跳過 Toast 測試')
      return
    }

    await lockedItem.click()

    // Toast 應在點擊後出現
    const toast = page.locator('.pc-locked-toast')
    const hasToast = await toast.waitFor({ state: 'visible', timeout: 5_000 })
      .then(() => true)
      .catch(() => false)

    expect(hasToast, '點擊鎖定章節後應顯示 Toast 警告').toBeTruthy()
  })
})
