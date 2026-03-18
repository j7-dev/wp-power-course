/**
 * 字幕管理整合測試
 *
 * 測試章節字幕 CRUD API：上傳（SRT/VTT）、列表查詢、刪除。
 * 涉及 WordPress 媒體庫、章節 postmeta，屬於跨層整合測試。
 *
 * 測試分組：
 * - 冒煙測試（@smoke）：最核心路徑——上傳 SRT 並取得列表
 * - 快樂路徑（@happy）：標準操作流程
 * - 錯誤處理（@error）：無效參數、不存在資源
 * - 邊緣案例（@edge）：重複語言、多語言並存
 *
 * 使用 test.describe.serial 確保測試按順序執行（有資料相依性）。
 * 在 beforeAll 中使用 browser fixture 重新登入 WordPress，
 * 完整複製 global-setup.ts 的登入流程以取得有效 session + nonce。
 */
import { test, expect } from '@playwright/test'
import type { APIRequestContext } from '@playwright/test'
import { ApiClient, getNonceFromPage } from '../helpers/api-client'
import { WP_ADMIN } from '../fixtures/test-data'

const BASE_URL = process.env.TEST_SITE_URL || 'http://localhost:8889'

// ── 測試用字幕內容 ────────────────────────────────

/** 最小合法 SRT 字幕 */
const SRT_CONTENT = `1
00:00:01,000 --> 00:00:04,000
你好世界

2
00:00:05,000 --> 00:00:08,000
這是 E2E 測試字幕
`

/** 最小合法 WebVTT 字幕 */
const VTT_CONTENT = `WEBVTT

00:00:01.000 --> 00:00:04.000
Hello World

00:00:05.000 --> 00:00:08.000
This is an E2E test subtitle
`

// ── 共用狀態 ────────────────────────────────────
let apiRequest: APIRequestContext
let api: ApiClient
let nonce: string
let courseId: number
let chapterId: number
let disposeContext: (() => Promise<void>) | null = null

/**
 * 完整複製 global-setup.ts 的登入流程，取得有效 session + nonce。
 *
 * 與 setupApiFromBrowser 的差別：
 * - 不使用 storageState（避免 domain/cookie 不匹配問題）
 * - 直接從登入後的 dashboard 頁面取 nonce（同 global-setup.ts）
 * - 等待 domcontentloaded 後從 page.evaluate 取 nonce，不用 waitForFunction
 */
async function freshLoginAndGetNonce(
	browser: import('@playwright/test').Browser,
): Promise<{ request: APIRequestContext; nonce: string; dispose: () => Promise<void> }> {
	const context = await browser.newContext({
		ignoreHTTPSErrors: true,
	})
	const page = await context.newPage()

	// 覆蓋 actionTimeout（playwright.config.ts 設定 10 秒，beforeAll 需要更長）
	page.setDefaultTimeout(60_000)

	// Step 1：打開登入頁
	await page.goto(`${BASE_URL}/wp-login.php`, {
		waitUntil: 'domcontentloaded',
		timeout: 30_000,
	})

	// Step 2：填寫帳密
	await page.fill('#user_login', WP_ADMIN.username)
	await page.fill('#user_pass', WP_ADMIN.password)

	// Step 3：送出表單，等待 domcontentloaded（同 global-setup.ts 做法）
	await Promise.all([
		page.waitForNavigation({
			waitUntil: 'domcontentloaded',
			timeout: 60_000,
		}),
		page.locator('#wp-submit').click(),
	])

	// Step 4：若不在 wp-admin，手動導航（同 global-setup.ts）
	if (!page.url().includes('wp-admin')) {
		await page.goto(`${BASE_URL}/wp-admin/`, {
			waitUntil: 'domcontentloaded',
			timeout: 30_000,
		})
	}

	// Step 5：等待 wpApiSettings.nonce 可用（同 global-setup.ts waitForFunction）
	await page.waitForFunction(
		() => !!(window as unknown as { wpApiSettings?: { nonce?: string } }).wpApiSettings?.nonce,
		{ timeout: 60_000 },
	)

	// Step 6：取得 nonce
	const extractedNonce = await getNonceFromPage(page)
	if (!extractedNonce) {
		throw new Error('登入成功但無法取得 wpApiSettings.nonce')
	}

	await page.close()

	return {
		request: context.request,
		nonce: extractedNonce,
		dispose: async () => {
			await context.close()
		},
	}
}

/**
 * 以 multipart/form-data 上傳字幕檔案。
 *
 * WordPress REST API 的字幕上傳端點要求 multipart 格式，
 * 無法使用 ApiClient.pcPost（JSON 格式）。
 */
async function uploadSubtitleMultipart(params: {
	chId: number
	filename: string
	content: string
	srclang: string
	mimeType?: string
}): Promise<{ status: number; data: unknown }> {
	const { chId, filename, content, srclang, mimeType = 'application/x-subrip' } = params

	const resp = await apiRequest.post(
		`${BASE_URL}/wp-json/power-course/chapters/${chId}/subtitles`,
		{
			headers: { 'X-WP-Nonce': nonce },
			multipart: {
				file: {
					name: filename,
					mimeType,
					buffer: Buffer.from(content),
				},
				srclang,
			},
		},
	)

	let data: unknown
	try {
		data = await resp.json()
	} catch {
		// PHP debug 模式可能在 JSON 前輸出 notices，嘗試擷取 JSON 部分
		const text = await resp.text()
		const match = text.match(/[\[{][\s\S]*/)
		data = match ? JSON.parse(match[0]) : text
	}

	return { status: resp.status(), data }
}

test.setTimeout(120_000)

test.describe.serial('字幕管理 API', () => {
	test.beforeAll(async ({ browser }) => {
		// 重新登入取得有效的 session + nonce（不依賴 storageState）
		const result = await freshLoginAndGetNonce(browser)
		apiRequest = result.request
		nonce = result.nonce
		disposeContext = result.dispose

		// 建立 API client（使用登入後的 context.request，帶有 session cookie）
		api = new ApiClient(apiRequest, nonce)

		// 建立測試課程 + 章節
		const courseResult = await api.createCourseWithChapters(
			'E2E Subtitle Test Course',
			'0',
			[{ name: 'E2E Subtitle Chapter', slug: 'e2e-subtitle-chapter' }],
			'e2e-subtitle-test',
		)
		courseId = courseResult.courseId
		chapterId = courseResult.chapterIds[0]
	})

	test.afterAll(async () => {
		// 清理：嘗試刪除所有可能遺留的字幕後刪除課程
		for (const lang of ['zh-TW', 'en', 'ja', 'zh-Hant-TW']) {
			try {
				await api.pcDelete(`chapters/${chapterId}/subtitles/${lang}`)
			} catch { /* ignore — 字幕可能已不存在 */ }
		}
		try {
			await api.deleteCourses([courseId])
		} catch { /* ignore */ }
		if (disposeContext) {
			await disposeContext()
		}
	})

	// ─────────────────────────────────────────────
	// 冒煙測試（Smoke）
	// ─────────────────────────────────────────────

	test('冒煙：章節存在時，GET 字幕列表應回傳 200 空陣列 @smoke', async () => {
		const resp = await api.pcGet(`chapters/${chapterId}/subtitles`)
		expect(resp.status).toBe(200)
		expect(Array.isArray(resp.data)).toBe(true)
		// 初始狀態應無字幕
		const subtitles = resp.data as unknown[]
		expect(subtitles.length).toBe(0)
	})

	// ─────────────────────────────────────────────
	// 快樂路徑（Happy Flow）
	// ─────────────────────────────────────────────

	test('快樂路徑：成功上傳 SRT 字幕，API 回傳 201 含 url（.vtt）與 attachment_id @happy @smoke', async () => {
		const { status, data } = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle-zh.srt',
			content: SRT_CONTENT,
			srclang: 'zh-TW',
		})

		// API 回傳 201 Created
		expect(status).toBe(201)

		const body = data as Record<string, unknown>

		// 驗證語言代碼
		expect(body).toHaveProperty('srclang', 'zh-TW')

		// 驗證 attachment_id 為正整數
		expect(body).toHaveProperty('attachment_id')
		expect(typeof body['attachment_id']).toBe('number')
		expect((body['attachment_id'] as number) > 0).toBe(true)

		// SRT 應自動轉換為 VTT，url 必須以 .vtt 結尾
		expect(body).toHaveProperty('url')
		expect(typeof body['url']).toBe('string')
		expect((body['url'] as string)).toMatch(/\.vtt$/)
	})

	test('快樂路徑：上傳 SRT 後，GET 列表應包含 zh-TW 字幕且 url 為 .vtt @happy @smoke', async () => {
		const resp = await api.pcGet(`chapters/${chapterId}/subtitles`)
		expect(resp.status).toBe(200)

		const subtitles = resp.data as Array<{
			srclang: string
			label: string
			url: string
			attachment_id: number
		}>

		expect(Array.isArray(subtitles)).toBe(true)

		const zhTW = subtitles.find((s) => s.srclang === 'zh-TW')
		expect(zhTW, '應找到 zh-TW 字幕').toBeDefined()
		expect(zhTW?.url).toMatch(/\.vtt$/)
		expect(zhTW?.attachment_id).toBeGreaterThan(0)
	})

	test('快樂路徑：成功上傳第二語言 VTT 字幕（英文），回傳 201 @happy', async () => {
		const { status, data } = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle-en.vtt',
			content: VTT_CONTENT,
			srclang: 'en',
			mimeType: 'text/vtt',
		})

		expect(status).toBe(201)

		const body = data as Record<string, unknown>
		expect(body).toHaveProperty('srclang', 'en')
		expect((body['url'] as string)).toMatch(/\.vtt$/)
	})

	test('快樂路徑：GET 列表應同時包含 zh-TW 與 en 兩種字幕 @happy', async () => {
		const resp = await api.pcGet(`chapters/${chapterId}/subtitles`)
		expect(resp.status).toBe(200)

		const subtitles = resp.data as Array<{ srclang: string }>
		const langs = subtitles.map((s) => s.srclang)

		expect(langs).toContain('zh-TW')
		expect(langs).toContain('en')
	})

	test('快樂路徑：成功刪除 en 字幕，回傳 { deleted: true }，列表不再含 en @happy @smoke', async () => {
		const delResp = await api.pcDelete(`chapters/${chapterId}/subtitles/en`)
		expect(delResp.status).toBe(200)

		const delBody = delResp.data as Record<string, unknown>
		expect(delBody).toHaveProperty('deleted', true)

		// 查詢確認移除
		const listResp = await api.pcGet(`chapters/${chapterId}/subtitles`)
		const subtitles = listResp.data as Array<{ srclang: string }>
		const langs = subtitles.map((s) => s.srclang)

		expect(langs).not.toContain('en')
		// zh-TW 仍應存在
		expect(langs).toContain('zh-TW')
	})

	test('快樂路徑：刪除最後一筆（zh-TW）後，GET 列表應回傳空陣列 @happy', async () => {
		const delResp = await api.pcDelete(`chapters/${chapterId}/subtitles/zh-TW`)
		expect(delResp.status).toBe(200)

		const listResp = await api.pcGet(`chapters/${chapterId}/subtitles`)
		const subtitles = listResp.data as unknown[]

		expect(Array.isArray(subtitles)).toBe(true)
		expect(subtitles.length).toBe(0)
	})

	// ─────────────────────────────────────────────
	// 錯誤處理（Error Handling）
	// ─────────────────────────────────────────────

	test('錯誤處理：取得不存在章節的字幕列表應回傳 404 @error', async () => {
		const resp = await api.pcGet('chapters/9999999/subtitles')
		expect(resp.status).toBe(404)
	})

	test('錯誤處理：刪除不存在章節的字幕應回傳 404 @error', async () => {
		const resp = await api.pcDelete('chapters/9999999/subtitles/zh-TW')
		expect(resp.status).toBe(404)
	})

	test('錯誤處理：刪除不存在語言的字幕應回傳 404 @error', async () => {
		// ja（日文）從未上傳過，應回傳 404
		const resp = await api.pcDelete(`chapters/${chapterId}/subtitles/ja`)
		expect(resp.status).toBe(404)
	})

	test('錯誤處理：上傳不支援的檔案格式（.txt）應回傳 400 @error', async () => {
		const { status } = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle.txt',
			content: 'This is not a subtitle file',
			srclang: 'zh-TW',
			mimeType: 'text/plain',
		})

		expect(status).toBe(400)
	})

	test('錯誤處理：上傳時未提供 srclang 應回傳 400 @error', async () => {
		// 故意省略 srclang 欄位
		const resp = await apiRequest.post(
			`${BASE_URL}/wp-json/power-course/chapters/${chapterId}/subtitles`,
			{
				headers: { 'X-WP-Nonce': nonce },
				multipart: {
					file: {
						name: 'subtitle.srt',
						mimeType: 'application/x-subrip',
						buffer: Buffer.from(SRT_CONTENT),
					},
					// 刻意省略 srclang
				},
			},
		)

		expect(resp.status()).toBe(400)
	})

	// ─────────────────────────────────────────────
	// 邊緣案例（Edge Cases）
	// ─────────────────────────────────────────────

	test('邊緣案例：重複上傳相同語言字幕應回傳 422 @edge', async () => {
		// 第一次上傳
		const firstResult = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle-1st.srt',
			content: SRT_CONTENT,
			srclang: 'zh-TW',
		})
		expect(firstResult.status).toBe(201)

		// 第二次上傳相同語言 → 應拒絕（422 業務規則不符）
		const secondResult = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle-2nd.srt',
			content: SRT_CONTENT,
			srclang: 'zh-TW',
		})
		expect(secondResult.status).toBe(422)

		// 清理：刪除剛上傳的字幕，維持測試後狀態一致
		await api.pcDelete(`chapters/${chapterId}/subtitles/zh-TW`)
	})

	test('邊緣案例：章節 ID 為超大整數應正常回應（非 500）@edge', async () => {
		// 使用接近 PHP int 上限的數值
		const resp = await api.pcGet('chapters/9223372036854775/subtitles')
		// 應正常處理（404 章節不存在），不得崩潰為 500
		expect(resp.status).not.toBe(500)
	})

	test('邊緣案例：上傳含 Unicode 語言代碼（如 zh-Hant-TW）應被接受或回傳適當錯誤 @edge', async () => {
		// zh-Hant-TW 是合法的 BCP-47 擴展語言代碼
		const { status } = await uploadSubtitleMultipart({
			chId: chapterId,
			filename: 'subtitle-zhtw.srt',
			content: SRT_CONTENT,
			srclang: 'zh-Hant-TW',
		})

		// 可接受（201）或拒絕（400），但不能是 500
		expect(status).not.toBe(500)

		// 若成功上傳，清理
		if (status === 201) {
			await api.pcDelete(`chapters/${chapterId}/subtitles/zh-Hant-TW`)
		}
	})
})
