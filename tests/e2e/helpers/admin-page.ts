/**
 * Admin SPA 導航 Helper
 *
 * Power Course 後台使用 React SPA + HashRouter，
 * 頁面 URL 格式: /wp-admin/admin.php?page=power-course#/path
 */
import { type Page, expect } from '@playwright/test'

/** Admin SPA 基礎 URL */
const ADMIN_PAGE = '/wp-admin/admin.php?page=power-course'

/** SPA 載入最長等待時間 */
const SPA_LOAD_TIMEOUT = 15_000

/**
 * 導航到 Admin SPA 的指定路由
 *
 * @param page - Playwright Page 實例
 * @param hash - Hash 路由路徑，例如 '/courses' 或 '/courses/edit/123'
 */
export async function navigateToAdmin(page: Page, hash: string): Promise<void> {
	const url = `${ADMIN_PAGE}#${hash}`
	await page.goto(url, { waitUntil: 'domcontentloaded' })

	// 等待 React SPA 根節點掛載
	await page.waitForSelector('#power_course', {
		state: 'attached',
		timeout: SPA_LOAD_TIMEOUT,
	})

	// 等待 Ant Design Spin 消失（SPA 資料載入完畢）
	await page.waitForFunction(
		() => {
			const spinners = document.querySelectorAll('.ant-spin-spinning')
			return spinners.length === 0
		},
		{ timeout: SPA_LOAD_TIMEOUT },
	)
}

/**
 * 等待 Ant Design Table 載入完成
 */
export async function waitForTableLoaded(page: Page): Promise<void> {
	// 等待 Ant Design 表格出現
	await page.waitForSelector('.ant-table', { timeout: SPA_LOAD_TIMEOUT })

	// 等待 loading 結束
	await page.waitForFunction(
		() => {
			const loading = document.querySelector('.ant-table-loading')
			return !loading
		},
		{ timeout: SPA_LOAD_TIMEOUT },
	)
}

/**
 * 等待 Ant Design ProTable 載入完成
 */
export async function waitForProTableLoaded(page: Page): Promise<void> {
	await page.waitForSelector('.ant-pro-table', { timeout: SPA_LOAD_TIMEOUT })
	await waitForTableLoaded(page)
}

/**
 * 等待 Ant Design Form 載入完成
 */
export async function waitForFormLoaded(page: Page): Promise<void> {
	await page.waitForSelector('.ant-form', { timeout: SPA_LOAD_TIMEOUT })

	// 等待 skeleton 消失
	await page.waitForFunction(
		() => {
			const skeletons = document.querySelectorAll('.ant-skeleton-active')
			return skeletons.length === 0
		},
		{ timeout: SPA_LOAD_TIMEOUT },
	)
}

/**
 * 等待 Ant Design 訊息提示出現
 *
 * @param page - Playwright Page 實例
 * @param type - 訊息類型 'success' | 'error' | 'warning' | 'info'
 */
export async function waitForMessage(
	page: Page,
	type: 'success' | 'error' | 'warning' | 'info' = 'success',
): Promise<void> {
	await page.waitForSelector(`.ant-message-${type}`, { timeout: 10_000 })
}

/**
 * 等待 Ant Design Notification 出現
 */
export async function waitForNotification(page: Page): Promise<void> {
	await page.waitForSelector('.ant-notification', { timeout: 10_000 })
}

/**
 * 點擊 Ant Design Tabs 的指定 Tab
 *
 * @param page - Playwright Page
 * @param tabName - Tab 顯示文字
 */
export async function clickTab(page: Page, tabName: string): Promise<void> {
	await page.click(`.ant-tabs-tab:has-text("${tabName}")`)

	// 等待 Tab 內容載入
	await page.waitForTimeout(500)
}

/**
 * 在 Ant Design Modal 中確認
 */
export async function confirmModal(page: Page): Promise<void> {
	await page.click('.ant-modal-footer .ant-btn-primary')
	await page.waitForSelector('.ant-modal', {
		state: 'hidden',
		timeout: 10_000,
	})
}

/**
 * 在 Ant Design Popconfirm 中確認
 */
export async function confirmPopconfirm(page: Page): Promise<void> {
	await page.click('.ant-popconfirm .ant-btn-primary')
}

/**
 * 收集頁面上的 console errors
 */
export function collectConsoleErrors(page: Page): string[] {
	const errors: string[] = []
	page.on('console', (msg) => {
		if (msg.type() === 'error') {
			errors.push(msg.text())
		}
	})
	return errors
}
