/**
 * REST API Client Helper
 *
 * 提供 Power Course / WooCommerce REST API 的簡便呼叫方式。
 * 使用 Playwright 的 request context 發送請求，自動帶上 nonce。
 */
import { type APIRequestContext } from '@playwright/test'

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8889'

interface ApiResponse<T = unknown> {
	status: number
	data: T
	headers: Record<string, string>
}

/**
 * 建立已認證的 API Client
 *
 * @param request - Playwright APIRequestContext
 * @param nonce - WP REST nonce（從 wpApiSettings 取得）
 */
export class ApiClient {
	constructor(
		private request: APIRequestContext,
		private nonce: string = '',
	) {}

	private headers() {
		const h: Record<string, string> = {
			'Content-Type': 'application/json',
		}
		if (this.nonce) {
			h['X-WP-Nonce'] = this.nonce
		}
		return h
	}

	/**
	 * Power Course API — GET
	 */
	async pcGet<T = unknown>(
		endpoint: string,
		params?: Record<string, string>,
	): Promise<ApiResponse<T>> {
		const url = new URL(`${BASE_URL}/wp-json/power-course/${endpoint}`)
		if (params) {
			Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))
		}
		const resp = await this.request.get(url.toString(), {
			headers: this.headers(),
		})
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * Power Course API — POST (JSON)
	 */
	async pcPost<T = unknown>(
		endpoint: string,
		body?: unknown,
	): Promise<ApiResponse<T>> {
		const resp = await this.request.post(
			`${BASE_URL}/wp-json/power-course/${endpoint}`,
			{
				headers: this.headers(),
				data: body,
			},
		)
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * Power Course API — POST (form-encoded)
	 *
	 * PHP 端使用 $request->get_body_params() 讀取，
	 * 必須以 application/x-www-form-urlencoded 格式送出。
	 */
	async pcPostForm<T = unknown>(
		endpoint: string,
		formData: Record<string, string>,
	): Promise<ApiResponse<T>> {
		const h: Record<string, string> = {}
		if (this.nonce) h['X-WP-Nonce'] = this.nonce
		const resp = await this.request.post(
			`${BASE_URL}/wp-json/power-course/${endpoint}`,
			{ headers: h, form: formData },
		)
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * Power Course API — DELETE
	 */
	async pcDelete<T = unknown>(
		endpoint: string,
		body?: unknown,
	): Promise<ApiResponse<T>> {
		const resp = await this.request.delete(
			`${BASE_URL}/wp-json/power-course/${endpoint}`,
			{
				headers: this.headers(),
				data: body,
			},
		)
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * WooCommerce REST API (v3)
	 */
	async wcGet<T = unknown>(
		endpoint: string,
		params?: Record<string, string>,
	): Promise<ApiResponse<T>> {
		const url = new URL(`${BASE_URL}/wp-json/wc/v3/${endpoint}`)
		if (params) {
			Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))
		}
		const resp = await this.request.get(url.toString(), {
			headers: this.headers(),
		})
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * WooCommerce REST API (v3) — POST
	 */
	async wcPost<T = unknown>(
		endpoint: string,
		body?: unknown,
	): Promise<ApiResponse<T>> {
		const resp = await this.request.post(
			`${BASE_URL}/wp-json/wc/v3/${endpoint}`,
			{
				headers: this.headers(),
				data: body,
			},
		)
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * WordPress REST API (v2)
	 */
	async wpGet<T = unknown>(
		endpoint: string,
		params?: Record<string, string>,
	): Promise<ApiResponse<T>> {
		const url = new URL(`${BASE_URL}/wp-json/wp/v2/${endpoint}`)
		if (params) {
			Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))
		}
		const resp = await this.request.get(url.toString(), {
			headers: this.headers(),
		})
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	/**
	 * WordPress REST API (v2) — POST
	 */
	async wpPost<T = unknown>(
		endpoint: string,
		body?: unknown,
	): Promise<ApiResponse<T>> {
		const resp = await this.request.post(
			`${BASE_URL}/wp-json/wp/v2/${endpoint}`,
			{
				headers: this.headers(),
				data: body,
			},
		)
		return {
			status: resp.status(),
			data: (await resp.json()) as T,
			headers: resp.headers(),
		}
	}

	// ── 便利方法 ──────────────────────────────────

	/**
	 * 建立測試課程
	 *
	 * Power Course API 回傳格式: { code, message, data: { id: "string" } }
	 * 此方法正確解析並回傳數字 ID。
	 */
	async createCourse(name: string = 'E2E 測試課程'): Promise<number> {
		const resp = await this.pcPost('courses', { name })
		const body = resp.data as { code: string; data: { id: string } }
		const id = Number(body?.data?.id)
		if (!id || isNaN(id)) {
			throw new Error(
				`課程建立失敗，API 回傳: ${JSON.stringify(resp.data)}`,
			)
		}
		return id
	}

	/**
	 * 批量刪除課程
	 */
	async deleteCourses(ids: number[]): Promise<void> {
		await this.pcDelete('courses', { ids })
	}
}

/**
 * 從頁面 wpApiSettings 取得 nonce
 */
export async function getNonceFromPage(
	page: import('@playwright/test').Page,
): Promise<string> {
	return await page.evaluate(() => {
		// @ts-expect-error — wpApiSettings is injected by WP
		return window.wpApiSettings?.nonce || ''
	})
}

/**
 * 在 beforeAll/afterAll 中建立 API Client
 *
 * worker-scoped browser fixture 在 beforeAll 中可用。
 * 此函式建立獨立的 context → 頁面 → 取 nonce → 回傳 ApiClient。
 */
export async function setupApiFromBrowser(
	browser: import('@playwright/test').Browser,
): Promise<{ api: ApiClient; dispose: () => Promise<void> }> {
	const context = await browser.newContext({
		storageState: '.auth/admin.json',
	})
	const page = await context.newPage()
	const baseUrl = process.env.WP_BASE_URL || 'http://localhost:8889'
	await page.goto(`${baseUrl}/wp-admin/`)
	await page.waitForSelector('body.wp-admin', { timeout: 15_000 })
	const nonce = await getNonceFromPage(page)
	return {
		api: new ApiClient(context.request, nonce),
		dispose: async () => {
			await page.close()
			await context.close()
		},
	}
}
