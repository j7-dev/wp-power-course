/**
 * 課程線性觀看功能 E2E 整合測試
 *
 * 測試線性觀看功能的核心行為：
 * - API 層：toggle-finish-chapters 在線性模式下的 403 回應
 * - API 層：完成章節後 next_chapter_unlocked 資訊正確回傳
 * - 管理端：enable_linear_mode meta 的儲存與讀取
 *
 * feature: specs/features/linear-viewing/
 */
import { test, expect } from '@playwright/test'
import { ApiClient, setupApiFromBrowser } from '../helpers/api-client'

const BASE_URL = process.env.TEST_SITE_URL || 'http://localhost:8889'

// ── 回應型別 ────────────────────────────────
interface ToggleResponse {
  code: string
  message: string
  data?: {
    chapter_id: number
    course_id: number
    is_this_chapter_finished: boolean
    progress: Record<string, unknown>
    icon_html: string
    next_chapter_unlocked?: {
      chapter_id: number
      chapter_title: string
      icon_html: string
    } | null
  }
}

interface CourseResponse {
  id: number
  enable_linear_mode: string
  [key: string]: unknown
}

// ── 共用變數 ────────────────────────────────
let api: ApiClient
let dispose: () => Promise<void>
let courseId: number
let chapterIds: number[]

test.setTimeout(120_000)

test.describe('課程線性觀看功能', () => {
  test.beforeAll(async ({ browser }) => {
    ;({ api, dispose } = await setupApiFromBrowser(browser))

    // 建立測試課程（含 4 個章節：1-1, 1-2, 1-3, 2-1）
    const result = await api.createCourseWithChapters(
      'E2E 線性觀看測試課程',
      '0',
      [
        { name: '章節 1-1', slug: 'linear-ch1-1' },
        { name: '章節 1-2', slug: 'linear-ch1-2' },
        { name: '章節 1-3', slug: 'linear-ch1-3' },
        { name: '章節 2-1', slug: 'linear-ch2-1' },
      ],
      'e2e-linear-viewing',
    )
    courseId = result.courseId
    chapterIds = result.chapterIds

    // 確保 admin 有課程存取權
    await api.grantCourseAccess(1, courseId)

    // 重設所有章節為未完成
    for (const chId of chapterIds) {
      const check = await api.pcPostForm<ToggleResponse>(
        `toggle-finish-chapters/${chId}`,
        { course_id: courseId },
      )
      // 若已完成（is_this_chapter_finished = true），再切換一次取消（非線性模式下）
    }
  })

  test.afterAll(async () => {
    await dispose?.()
  })

  // ── 管理員設定 ────────────────────────────────────────────────────────────────

  test('admin-setting: enable_linear_mode 預設為 no 或空字串', async () => {
    const resp = await api.pcGet<CourseResponse>(`courses/${courseId}`)
    expect(resp.status).toBe(200)
    // 預設值應為 'no' 或不存在（不能是 'yes'）
    const linearMode = resp.data?.enable_linear_mode ?? 'no'
    expect(linearMode).not.toBe('yes')
  })

  test('admin-setting: 更新 enable_linear_mode = yes 後課程 meta 應為 yes', async () => {
    // 透過課程更新 API 開啟線性模式
    const updateResp = await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'yes',
    })
    expect(updateResp.status).toBe(200)

    // 重新讀取課程確認 meta 已儲存
    const resp = await api.pcGet<CourseResponse>(`courses/${courseId}`)
    expect(resp.status).toBe(200)
    expect(resp.data?.enable_linear_mode).toBe('yes')
  })

  // ── API 攔截：線性模式下禁止取消完成 ────────────────────────────────────────

  test('chapter-lock: 線性模式下嘗試取消已完成章節應回傳 403', async () => {
    // 確保線性模式已開啟
    await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'yes',
    })

    const ch1 = chapterIds[0]

    // 先完成第一個章節
    const finishResp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )
    // 若剛才已完成，就是 200；若尚未完成也應為 200
    // 確保章節為已完成狀態

    // 再次呼叫（嘗試取消完成）→ 應回傳 403
    const unfinishResp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )
    expect(unfinishResp.status).toBe(403)
    expect(unfinishResp.data?.code).toBe('403')
    expect(unfinishResp.data?.message).toContain('線性觀看模式')
  })

  // ── API 回應：完成章節時回傳 next_chapter_unlocked ─────────────────────────

  test('chapter-lock: 完成章節後 API 回應包含 next_chapter_unlocked 資訊', async () => {
    // 確保線性模式已開啟
    await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'yes',
    })

    // 關閉線性模式暫時取消完成第一章節（允許重設）
    await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'no',
    })

    // 重設第一個章節（取消完成）
    const ch1 = chapterIds[0]
    await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )

    // 重新開啟線性模式
    await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'yes',
    })

    // 完成第一個章節
    const resp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )

    expect(resp.status).toBe(200)
    expect(resp.data?.code).toBe('200')
    expect(resp.data?.data?.is_this_chapter_finished).toBe(true)

    // next_chapter_unlocked 應包含第二章節資訊
    expect(resp.data?.data?.next_chapter_unlocked).not.toBeNull()
    expect(resp.data?.data?.next_chapter_unlocked?.chapter_id).toBe(
      chapterIds[1],
    )
    expect(resp.data?.data?.next_chapter_unlocked?.chapter_title).toBeTruthy()
    expect(resp.data?.data?.next_chapter_unlocked?.icon_html).toBeTruthy()
  })

  // ── 向下相容：非線性模式下章節自由存取 ────────────────────────────────────

  test('backward-compat: 關閉線性模式後可正常取消完成章節', async () => {
    // 關閉線性模式
    await api.pcPostForm(`courses/${courseId}`, {
      enable_linear_mode: 'no',
    })

    const ch1 = chapterIds[0]

    // 確保 ch1 為已完成狀態
    const statusResp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )
    // 若剛才取消了，再完成一次
    if (!statusResp.data?.data?.is_this_chapter_finished) {
      await api.pcPostForm<ToggleResponse>(
        `toggle-finish-chapters/${ch1}`,
        { course_id: courseId },
      )
    }

    // 再次呼叫取消完成 → 非線性模式應允許（200）
    const unfinishResp = await api.pcPostForm<ToggleResponse>(
      `toggle-finish-chapters/${ch1}`,
      { course_id: courseId },
    )
    expect(unfinishResp.status).toBe(200)
    expect(unfinishResp.data?.data?.is_this_chapter_finished).toBe(false)
  })
})
