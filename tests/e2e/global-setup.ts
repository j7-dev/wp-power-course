/**
 * Playwright Global Setup
 *
 * 測試開始前執行：
 * 1. 套用 LC bypass（注入 'lc' => false 到 plugin.php）
 * 2. 登入 WordPress Admin
 * 3. 儲存認證狀態供後續測試使用
 * 4. 建立前台測試共用資料（課程、章節、訂閱者、BACS）
 */
import { chromium, type FullConfig } from '@playwright/test'
import { applyLcBypass } from './helpers/lc-bypass'
import { ApiClient, getNonceFromPage } from './helpers/api-client'
import { ensureFrontendTestData, clearFrontendTestDataCache } from './helpers/frontend-setup'
import { WP_ADMIN } from './fixtures/test-data'
import path from 'path'
import { execSync } from 'child_process'
import { fileURLToPath } from 'url'
import fs from 'fs'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const STORAGE_STATE_PATH = path.join(__dirname, '.auth', 'admin.json')

async function globalSetup(config: FullConfig): Promise<void> {
	const baseURL =
		config.projects[0]?.use?.baseURL || 'http://localhost:8889'

	// 1. 套用 LC bypass
	console.log('[Global Setup] Applying LC bypass...')
	applyLcBypass()

	// 1.5 停用 WooCommerce "Coming Soon" 模式
	console.log('[Global Setup] Disabling WooCommerce Coming Soon mode...')
	const projectRoot = path.resolve(__dirname, '..', '..')
	try {
		execSync(
			'npx wp-env run cli -- wp option update woocommerce_coming_soon no',
			{ cwd: projectRoot, stdio: 'pipe', timeout: 30_000 },
		)
	} catch (e) {
		console.warn('[Global Setup] Could not disable Coming Soon mode:', e)
	}

	// 2. 確保 .auth 目錄存在
	const authDir = path.dirname(STORAGE_STATE_PATH)
	if (!fs.existsSync(authDir)) {
		fs.mkdirSync(authDir, { recursive: true })
	}

	// 3. 登入 WordPress Admin 並儲存 storageState
	console.log('[Global Setup] Logging in to WordPress Admin...')
	const browser = await chromium.launch()
	const context = await browser.newContext()
	const page = await context.newPage()

	try {
		await page.goto(`${baseURL}/wp-login.php`, {
			waitUntil: 'domcontentloaded',
			timeout: 30_000,
		})

		await page.fill('#user_login', WP_ADMIN.username)
		await page.fill('#user_pass', WP_ADMIN.password)
		await page.click('#wp-submit')

		// 等待登入成功 — 重導到 dashboard
		await page.waitForURL(/wp-admin/, { timeout: 30_000 })

		console.log('[Global Setup] Login successful, saving storage state...')
		await context.storageState({ path: STORAGE_STATE_PATH })

		// 3.5 刷新 WordPress 永久連結（flush rewrite rules）
		//     確保 pc_chapter CPT 的 /classroom/ slug 生效
		console.log('[Global Setup] Flushing rewrite rules via Permalinks page...')
		try {
			await page.goto(`${baseURL}/wp-admin/options-permalink.php`, {
				waitUntil: 'domcontentloaded',
				timeout: 30_000,
			})
			await page.click('#submit')
			await page.waitForURL(/options-permalink/, { timeout: 30_000 })
			console.log('[Global Setup] Rewrite rules flushed.')
		} catch (e) {
			console.warn('[Global Setup] Flush rewrite rules warning:', e)
		}

		// 4. 清除舊 E2E 測試資料，避免重複 slug 造成 404
		console.log('[Global Setup] Cleaning old E2E test data...')
		try {
			// 刪除所有 e2e 開頭的 pc_chapter
			const chapterIds = execSync(
				'npx wp-env run cli -- wp post list --post_type=pc_chapter --post_status=any --name__like=e2e --field=ID --format=csv',
				{ cwd: projectRoot, stdio: 'pipe', timeout: 30_000 },
			).toString().trim()
			if (chapterIds) {
				execSync(
					`npx wp-env run cli -- wp post delete ${chapterIds.split('\n').join(' ')} --force`,
					{ cwd: projectRoot, stdio: 'pipe', timeout: 30_000 },
				)
			}
			// 刪除所有 e2e 開頭的 product
			const productIds = execSync(
				'npx wp-env run cli -- wp post list --post_type=product --post_status=any --name__like=e2e --field=ID --format=csv',
				{ cwd: projectRoot, stdio: 'pipe', timeout: 30_000 },
			).toString().trim()
			if (productIds) {
				execSync(
					`npx wp-env run cli -- wp post delete ${productIds.split('\n').join(' ')} --force`,
					{ cwd: projectRoot, stdio: 'pipe', timeout: 30_000 },
				)
			}
		} catch (e) {
			console.warn('[Global Setup] Cleanup warning (non-fatal):', e)
		}

		// 5. 建立前台測試共用資料（課程、章節、訂閱者帳號、BACS 付款）
		console.log('[Global Setup] Ensuring frontend test data...')
		clearFrontendTestDataCache() // 清除舊快取，強制以修正後的 API 重新建立
		// 回到 wp-admin 首頁以確保 wpApiSettings.nonce 可用
		await page.goto(`${baseURL}/wp-admin/`, {
			waitUntil: 'domcontentloaded',
			timeout: 30_000,
		})
		await page.waitForSelector('body.wp-admin', { timeout: 15_000 })
		const nonce = await getNonceFromPage(page)
		const api = new ApiClient(context.request, nonce)
		await ensureFrontendTestData(api)
		console.log('[Global Setup] Frontend test data ready.')
	} catch (error) {
		console.error('[Global Setup] Failed:', error)
		throw error
	} finally {
		await browser.close()
	}

	console.log('[Global Setup] Complete.')
}

export default globalSetup
