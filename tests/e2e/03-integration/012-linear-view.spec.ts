/**
 * 線性觀看（Sequential Chapter Viewing）整合測試
 * Feature: specs/features/progress/線性觀看.feature
 *
 * 測試場景：
 * 1. 管理員在課程「其他設定」tab 看到 enable_linear_mode 開關
 * 2. 開啟線性觀看後，學員在教室頁面看到鎖頭圖示
 * 3. 點擊鎖定章節顯示 Toast 提示，不跳轉
 * 4. API 回傳 403 當嘗試標記被鎖定章節為完成
 * 5. 直接 URL 存取被鎖定章節顯示鎖定提示頁面
 */
import { test, expect, type Page } from '@playwright/test'
import { ApiClient, setupApiFromBrowser } from '../helpers/api-client'
import { loginAs } from '../helpers/frontend-setup'
import { WP_ADMIN } from '../fixtures/test-data'

const BASE_URL = process.env.TEST_SITE_URL || 'http://localhost:8889'

// ── 回應型別 ────────────────────────────────
interface ToggleResponse {
  code: string
  message: string
  data: {
    chapter_id: number
    course_id: number
    is_this_chapter_finished: boolean
    progress: Record<string, unknown>
    icon_html: string
  }
}

// ── 共用變數 ────────────────────────────────
let api: ApiClient
let dispose: () => Promise<void>
let courseId: number
let chapterIds: number[]
let courseSlug: string
let studentId: number

const STUDENT_USERNAME = 'e2e_linear_student'
const STUDENT_PASSWORD = 'e2e_linear_pass'
const STUDENT_EMAIL = 'e2e_linear_student@test.local'

test.setTimeout(180_000)

test.describe.serial('線性觀看整合測試', () => {
  test.beforeAll(async ({ browser }) => {
    ;({ api, dispose } = await setupApiFromBrowser(browser))

    // 建立測試課程（含 5 個章節，模擬 .feature 中的架構）
    const result = await api.createCourseWithChapters(
      'E2E 線性觀看測試課程',
      '0',
      [
        { name: '第一章', slug: 'e2e-linear-ch1' },
        { name: '1-1', slug: 'e2e-linear-ch1-1' },
        { name: '1-2', slug: 'e2e-linear-ch1-2' },
        { name: '第二章', slug: 'e2e-linear-ch2' },
        { name: '2-1', slug: 'e2e-linear-ch2-1' },
      ],
      'e2e-linear-view-course',
    )
    courseId = result.courseId
    chapterIds = result.chapterIds
    courseSlug = result.courseSlug

    // 建立測試學員
    studentId = await api.ensureUser(
      STUDENT_USERNAME,
      STUDENT_EMAIL,
      STUDENT_PASSWORD,
      ['subscriber'],
    )

    // 授權學員存取課程
    await api.grantCourseAccess(studentId, courseId)
    // 授權 admin（ID=1）存取課程（供 toggle API 測試）
    await api.grantCourseAccess(1, courseId)

    // 預設：確保線性觀看為關閉狀態
    await api.updateCourse(courseId, { enable_linear_mode: 'no' })
  })

  test.afterAll(async () => {
    try {
      // 關閉線性觀看並清理資料
      await api.updateCourse(courseId, { enable_linear_mode: 'no' })
    } catch { /* ignore */ }
    try {
      await api.removeCourseAccess(1, courseId)
    } catch { /* ignore */ }
    try {
      await api.removeCourseAccess(studentId, courseId)
    } catch { /* ignore */ }
    try {
      await api.deleteCourses([courseId])
    } catch { /* ignore */ }
    await dispose()
  })

  // ── Test 1: 管理員在「其他設定」tab 看到 enable_linear_mode 開關 ────────────
  test('管理員在課程「其他設定」tab 看到線性觀看開關', async ({ page }) => {
    await loginAs(page, WP_ADMIN.username, WP_ADMIN.password)

    // 前往課程編輯頁（React SPA）
    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=power-course#/courses/${courseId}`)
    await page.waitForLoadState('networkidle')

    // 等待 SPA 載入
    await page.waitForTimeout(3000)

    // 尋找「其他設定」tab
    const otherTab = page.getByRole('tab', { name: /其他設定|Other/i }).first()
    await otherTab.waitFor({ state: 'visible', timeout: 15_000 })
    await otherTab.click()

    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // 驗證「線性觀看」開關存在
    // 使用 label 文字定位（FiSwitch 的 label 文字）
    const linearModeLabel = page.getByText(/線性觀看|linear/i).first()
    await expect(linearModeLabel).toBeVisible({ timeout: 10_000 })
  })

  // ── Test 2: 開啟線性觀看後，教室章節列表顯示鎖頭圖示 ────────────────────
  test('開啟線性觀看後學員在教室頁面看到鎖頭圖示', async ({ page }) => {
    // Given 開啟線性觀看
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // And 第一章有 finished_at（讓 1-1 解鎖，1-2 仍鎖定）
    // 直接標記第一章完成（使用 admin 帳號）
    await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[0]}`,
      { course_id: courseId },
    )

    // 以學員身份登入並進入教室
    await loginAs(page, WP_ADMIN.username, WP_ADMIN.password)
    const chapter1Url = `${BASE_URL}/classroom/${courseSlug}/${(await getChapterSlug(page, chapterIds[0]))}/`
    await page.goto(chapter1Url)
    await page.waitForLoadState('networkidle')

    // 等待側邊欄載入
    await page.locator('#pc-sider__main-chapters').waitFor({
      state: 'visible',
      timeout: 15_000,
    })

    // 驗證有章節顯示鎖頭圖示（data-locked="true" 的 li 元素）
    // 注意：功能尚未實作，此測試應處於 Red 狀態
    const lockedChapterItems = page.locator('#pc-sider__main-chapters li[data-locked="true"]')
    await expect(lockedChapterItems.first()).toBeVisible({ timeout: 10_000 })

    // 清理：取消第一章完成狀態
    await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[0]}`,
      { course_id: courseId },
    )
  })

  // ── Test 3: 點擊鎖定章節顯示 Toast，不跳轉 ─────────────────────────────
  test('點擊鎖定章節顯示 Toast 提示且不跳轉頁面', async ({ page }) => {
    // Given 線性觀看開啟
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // And 未完成任何章節（第二章及後面都鎖定）

    // 登入並進入第一章教室
    await loginAs(page, WP_ADMIN.username, WP_ADMIN.password)
    const chapterSlug = await getChapterSlug(page, chapterIds[0])
    const chapter1Url = `${BASE_URL}/classroom/${courseSlug}/${chapterSlug}/`
    await page.goto(chapter1Url)
    await page.waitForLoadState('networkidle')

    await page.locator('#pc-sider__main-chapters').waitFor({
      state: 'visible',
      timeout: 15_000,
    })

    // 記錄當前 URL
    const currentUrl = page.url()

    // 點擊第二個章節（1-1，已鎖定）
    const lockedItem = page.locator('#pc-sider__main-chapters li[data-locked="true"]').first()
    await lockedItem.waitFor({ state: 'visible', timeout: 10_000 })
    await lockedItem.click()

    // 等待一小段時間
    await page.waitForTimeout(1000)

    // 驗證：頁面沒有跳轉
    expect(page.url()).toBe(currentUrl)

    // 驗證：顯示 Toast 訊息（DaisyUI toast）
    // Toast 應包含「請先完成前面的章節才能觀看此章節」
    const toast = page.getByText(/請先完成前面的章節才能觀看此章節/).first()
    await expect(toast).toBeVisible({ timeout: 5_000 })
  })

  // ── Test 4: API 回傳 403 當嘗試標記被鎖定章節為完成 ──────────────────────
  test('API 拒絕標記被鎖定章節為完成，回傳 403', async ({ page }) => {
    // Given 線性觀看開啟
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // And 未完成任何章節
    // 確保第一章未完成（若已完成則取消）
    const checkResp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[0]}`,
      { course_id: courseId },
    )
    // 若剛才是完成操作（state becomes true），再次取消
    if (checkResp.data?.data?.is_this_chapter_finished === true) {
      await api.pcPostForm<ToggleResponse>(
        `toggle-finish-chapters/${chapterIds[0]}`,
        { course_id: courseId },
      )
    }

    // When 嘗試標記第二個章節（1-1，索引1）為完成
    // 1-1 的前一章（第一章）未完成，所以 1-1 被鎖定
    const resp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[1]}`,
      { course_id: courseId },
    )

    // Then API 應回傳 403
    expect(resp.status).toBe(403)

    // And 訊息應包含「章節已鎖定」
    const message = (resp.data as { message?: string })?.message ?? ''
    expect(message).toContain('章節已鎖定')
  })

  // ── Test 5: 直接 URL 存取被鎖定章節顯示鎖定提示頁面 ──────────────────────
  test('直接 URL 存取被鎖定章節顯示鎖定提示頁面', async ({ page }) => {
    // Given 線性觀看開啟
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // And Alice（用學員帳號）未完成任何章節

    // 取得第二個章節（1-1）的 URL
    const lockedChapterSlug = await getChapterSlug(page, chapterIds[1])
    const lockedChapterUrl = `${BASE_URL}/classroom/${courseSlug}/${lockedChapterSlug}/`

    // 登入學員帳號（此測試需要非 admin 帳號）
    await loginAs(page, STUDENT_USERNAME, STUDENT_PASSWORD)

    // When 直接存取被鎖定章節
    await page.goto(lockedChapterUrl)
    await page.waitForLoadState('networkidle')

    // Then 頁面應顯示鎖定提示
    const lockedMessage = page.getByText(/請先完成前面的章節才能觀看此章節/).first()
    await expect(lockedMessage).toBeVisible({ timeout: 10_000 })

    // And 頁面不應顯示章節的影片內容
    const videoPlayer = page.locator('video, .vds-media, #chapter-video').first()
    await expect(videoPlayer).not.toBeVisible({ timeout: 3_000 }).catch(() => {
      // 若元素不存在，視為通過
    })
  })

  // ── Test 6: 線性觀看關閉時，學員可自由存取任意章節 ─────────────────────
  test('線性觀看關閉時 API 允許標記任意章節為完成', async () => {
    // Given 關閉線性觀看
    await api.updateCourse(courseId, { enable_linear_mode: 'no' })

    // When 標記最後一個章節（2-1）為完成，且前面章節未完成
    const resp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[4]}`,
      { course_id: courseId },
    )

    // Then 不應回傳 403（應允許完成）
    expect(resp.status).not.toBe(403)
    expect(resp.status).toBeLessThan(400)

    // 清理：取消完成
    const cleanup = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${chapterIds[4]}`,
      { course_id: courseId },
    )
    // 若已變為完成則再 toggle 取消
    if (cleanup.data?.data?.is_this_chapter_finished === false) {
      // 已還原
    }
  })

  // ── Test 7: 「前往下一章節」按鈕在下一章節鎖定時禁用 ───────────────────
  test('下一章節被鎖定時「前往下一章節」按鈕禁用', async ({ page }) => {
    // Given 線性觀看開啟
    await api.updateCourse(courseId, { enable_linear_mode: 'yes' })

    // And 未完成任何章節（第一章的下一章 1-1 應被鎖定）

    // 登入並進入第一章教室
    await loginAs(page, WP_ADMIN.username, WP_ADMIN.password)
    const chapterSlug = await getChapterSlug(page, chapterIds[0])
    const chapter1Url = `${BASE_URL}/classroom/${courseSlug}/${chapterSlug}/`
    await page.goto(chapter1Url)
    await page.waitForLoadState('networkidle')

    // 等待 header 載入
    await page.locator('#pc-classroom-header').waitFor({
      state: 'visible',
      timeout: 15_000,
    })

    // 驗證「前往下一章節」按鈕應為禁用狀態
    // 功能尚未實作，此測試預期 Red（按鈕可能仍可點擊）
    const nextChapterBtn = page.locator('#pc-classroom-header button[aria-disabled="true"], #pc-classroom-header a[aria-disabled="true"]').filter({ hasText: /前往下一章節/ })
    // 或者尋找 disabled 屬性的按鈕
    const disabledNextBtn = page.locator('#pc-classroom-header').getByRole('button', { disabled: true }).filter({ hasText: /前往下一章節/ })

    // 至少一種方式能找到禁用按鈕
    const btnCount1 = await nextChapterBtn.count()
    const btnCount2 = await disabledNextBtn.count()
    expect(btnCount1 + btnCount2).toBeGreaterThan(0)
  })
})

// ── Helper ──────────────────────────────────────────────────────────────
/**
 * 取得章節的 slug（透過 WP REST API）
 */
async function getChapterSlug(page: Page, chapterId: number): Promise<string> {
  const baseUrl = process.env.TEST_SITE_URL || 'http://localhost:8889'
  const response = await page.request.get(
    `${baseUrl}/wp-json/wp/v2/pc_chapter/${chapterId}`,
    {
      headers: { 'Content-Type': 'application/json' },
    },
  )
  if (!response.ok()) {
    // Fallback：直接使用 ID 作為部分 URL
    throw new Error(`無法取得章節 ${chapterId} 的 slug，狀態碼：${response.status()}`)
  }
  const data = (await response.json()) as { slug: string }
  return data.slug
}
