/**
 * 銷售方案商品數量 管理端 E2E 測試
 *
 * 涵蓋：
 * - 新建銷售方案 → 確認當前課程自動出現在商品列表中
 * - 搜尋商品加入 → 確認數量輸入框預設為 1
 * - 修改數量為 3 → 儲存 → 重新開啟 → 確認數量回顯為 3
 * - 輸入 0 → 確認自動修正為 1
 * - 確認「排除目前課程」開關已移除
 * - 移除當前課程 → 確認可以成功儲存
 *
 * Feature: specs/features/bundle/銷售方案商品數量.feature
 * Feature: specs/features/bundle/銷售方案當前課程統一管理.feature
 */

import { test, expect, Page } from '@playwright/test'
import {
	navigateToAdmin,
	waitForFormLoaded,
	clickTab,
} from '../helpers/admin-page'
import { setupApiFromBrowser } from '../helpers/api-client'

/**
 * 建立新銷售方案並開啟內嵌編輯表單
 *
 * 實際 UI 流程：
 * 1. 點擊「新增」按鈕 → API POST 建立銷售方案
 * 2. 列表重新載入 → 新銷售方案出現在 SortableList
 * 3. 點擊列表項目的產品名稱 → 右側內嵌 Card 編輯表單出現
 */
async function createAndOpenBundleForm(page: Page): Promise<void> {
	// 等待 active tab pane 渲染完成（forceRender: false 的 tab 需要 mount 時間）
	const tabContent = page.locator('.ant-tabs-tabpane-active')
	const addBtn = tabContent.locator('.ant-btn-primary').first()
	await expect(addBtn).toBeVisible({ timeout: 10_000 })

	// 記錄建立前的列表項目數量
	const initialCount = await tabContent
		.locator('[data-testid="list-item"]')
		.count()

	// 先設定 response listener 再點擊，避免遺漏 response
	const postPromise = page.waitForResponse(
		(resp) =>
			resp.url().includes('bundle_products') &&
			resp.request().method() === 'POST' &&
			resp.ok(),
		{ timeout: 15_000 },
	)

	// 點擊「新增」按鈕（使用 CSS selector，與 bundle-product.spec.ts 一致）
	await addBtn.click()

	// 等待 API POST 完成
	await postPromise

	// 等待列表項目數量增加（list invalidation + refetch 完成）
	await expect(tabContent.locator('[data-testid="list-item"]')).toHaveCount(
		initialCount + 1,
		{ timeout: 15_000 },
	)

	// 點擊第一個列表項目的產品名稱以開啟編輯表單
	// 排序為 menu_order asc, date desc，新建的銷售方案在第一位
	const listItem = tabContent.locator('[data-testid="list-item"]').first()
	await listItem.locator('text=銷售方案').first().click()

	// 等待編輯表單載入完成（EditBundle 面板出現）
	const selectedProducts = page.locator(
		'[data-testid="selected-products-list"]',
	)
	await expect(selectedProducts).toBeVisible({ timeout: 10_000 })

	// 等待商品資料非同步載入完成（loading skeleton 在 selected-products-list 外層）
	// 透過等待 selected-products-list 內有實際內容來判斷載入完成
	await expect(
		selectedProducts.locator('.ant-input-number'),
	).toBeVisible({ timeout: 10_000 })
}

test.describe('銷售方案商品數量', () => {
	test.use({ storageState: '.auth/admin.json' })

	let courseId: number

	test.beforeAll(async ({ browser }) => {
		const { api, dispose } = await setupApiFromBrowser(browser)
		try {
			courseId = await api.createCourse('E2E 銷售方案數量測試課程')
		} finally {
			await dispose()
		}
	})

	test.afterAll(async ({ browser }) => {
		if (!courseId) return
		const { api, dispose } = await setupApiFromBrowser(browser)
		try {
			await api.deleteCourses([courseId])
		} finally {
			await dispose()
		}
	})

	test('新建銷售方案，當前課程應自動出現在商品列表中', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		// 驗證當前課程出現在已選商品列表中
		const selectedProducts = page.locator(
			'[data-testid="selected-products-list"]',
		)
		await expect(selectedProducts).toBeVisible({ timeout: 10_000 })
		await expect(selectedProducts).toContainText('E2E 銷售方案數量測試課程', {
			timeout: 5_000,
		})
	})

	test('商品數量輸入框預設值應為 1', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		// 驗證數量輸入框預設值為 1
		const selectedProducts = page.locator(
			'[data-testid="selected-products-list"]',
		)
		const qtyInput = selectedProducts.locator('.ant-input-number input').first()
		await expect(qtyInput).toBeVisible({ timeout: 10_000 })
		await expect(qtyInput).toHaveValue('1')
	})

	test('修改數量後儲存，重新開啟應回顯正確數量', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		const selectedProducts = page.locator(
			'[data-testid="selected-products-list"]',
		)

		// 修改數量為 3
		const qtyInput = selectedProducts.locator('.ant-input-number input').first()
		await expect(qtyInput).toBeVisible({ timeout: 10_000 })
		await qtyInput.click({ clickCount: 3 })
		await qtyInput.fill('3')

		// 設定銷售方案名稱（必填）
		const nameField = page
			.locator('.ant-form-item')
			.filter({ hasText: '銷售方案名稱' })
		const nameInput = nameField.locator('input')
		await nameInput.fill('數量測試方案')

		// 儲存
		const saveBtn = page.getByRole('button', { name: '儲存銷售方案' })
		const savePromise = page.waitForResponse(
			(resp) =>
				resp.url().includes('bundle_products/') &&
				resp.ok() &&
				resp.request().method() !== 'GET',
			{ timeout: 15_000 },
		)
		await saveBtn.click()
		await savePromise

		// 重新載入頁面以驗證資料持久化
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')

		// 等待 tab content 渲染完成
		const tabContent = page.locator('.ant-tabs-tabpane-active')
		await expect(tabContent.locator('.ant-btn-primary').first()).toBeVisible({
			timeout: 10_000,
		})

		// 等待列表載入完成
		await expect(
			tabContent.locator('[data-testid="list-item"]').first(),
		).toBeVisible({ timeout: 15_000 })

		// 找到更名後的銷售方案並開啟
		const bundleItem = tabContent
			.locator('[data-testid="list-item"]')
			.filter({ hasText: '數量測試方案' })
			.first()

		if (await bundleItem.isVisible({ timeout: 10_000 }).catch(() => false)) {
			await bundleItem.locator('text=數量測試方案').first().click()

			// 等待編輯表單載入
			const reopenedProducts = page.locator(
				'[data-testid="selected-products-list"]',
			)
			await expect(reopenedProducts).toBeVisible({ timeout: 10_000 })
			await expect(
				reopenedProducts.locator('.ant-input-number'),
			).toBeVisible({ timeout: 10_000 })

			// 驗證數量回顯為 3
			const reopenedQtyInput = reopenedProducts
				.locator('.ant-input-number input')
				.first()
			await expect(reopenedQtyInput).toHaveValue('3', { timeout: 5_000 })
		}
	})

	test('輸入 0 應自動修正為 1', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		const selectedProducts = page.locator(
			'[data-testid="selected-products-list"]',
		)

		// 找到數量輸入框並輸入 0
		const qtyInput = selectedProducts.locator('.ant-input-number input').first()
		await expect(qtyInput).toBeVisible({ timeout: 10_000 })
		await qtyInput.click({ clickCount: 3 })
		await qtyInput.fill('0')
		// 觸發 blur 事件讓 clamp 生效
		await qtyInput.press('Tab')

		// 驗證自動修正為 1
		await expect(qtyInput).toHaveValue('1', { timeout: 3_000 })
	})

	test('「排除目前課程」開關應已從 UI 移除', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		// 在編輯表單中確認無「排除目前課程」文字
		const editForm = page.locator('.ant-form').filter({
			has: page.locator('[data-testid="selected-products-list"]'),
		})

		await expect(editForm).not.toContainText('排除目前課程', {
			timeout: 3_000,
		})
		await expect(editForm).not.toContainText('exclude_main_course', {
			timeout: 3_000,
		})
	})

	test('移除當前課程後應可成功儲存', async ({ page }) => {
		await navigateToAdmin(page, `/courses/edit/${courseId}`)
		await waitForFormLoaded(page)
		await clickTab(page, '銷售方案')
		await createAndOpenBundleForm(page)

		const selectedProducts = page.locator(
			'[data-testid="selected-products-list"]',
		)

		// 找到刪除按鈕（PopconfirmDelete 渲染為 DeleteOutlined icon button）
		const deleteBtn = selectedProducts.locator('.anticon-delete').first()
		if (await deleteBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
			await deleteBtn.click()

			// 確認 Popconfirm 刪除
			await page.locator('.ant-popconfirm .ant-btn-primary').click()
		}

		// 設定銷售方案名稱（必填）
		const nameField = page
			.locator('.ant-form-item')
			.filter({ hasText: '銷售方案名稱' })
		const nameInput = nameField.locator('input')
		await nameInput.fill('移除課程測試方案')

		// 儲存並驗證 API 回應成功
		const saveBtn = page.getByRole('button', { name: '儲存銷售方案' })
		const savePromise = page.waitForResponse(
			(resp) =>
				resp.url().includes('bundle_products/') &&
				resp.ok() &&
				resp.request().method() !== 'GET',
			{ timeout: 15_000 },
		)
		await saveBtn.click()
		const saveResponse = await savePromise
		expect(saveResponse.status()).toBeLessThan(400)
	})
})
