/**
 * Playwright Global Setup
 *
 * 測試開始前執行：
 * 1. 套用 LC bypass（注入 'lc' => false 到 plugin.php）
 * 2. 登入 WordPress Admin
 * 3. 儲存認證狀態供後續測試使用
 */
import { chromium, type FullConfig } from '@playwright/test'
import { applyLcBypass } from './helpers/lc-bypass'
import { WP_ADMIN } from './fixtures/test-data'
import path from 'path'
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
	} catch (error) {
		console.error('[Global Setup] Login failed:', error)
		throw error
	} finally {
		await browser.close()
	}

	console.log('[Global Setup] Complete.')
}

export default globalSetup
