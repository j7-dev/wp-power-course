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
	 * Power Course API — POST
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
