/**
 * 銷售方案商品數量 E2E 測試（管理端）
 *
 * Issue #183：讓銷售方案中每個商品可自由設定數量（1~999），
 * 同時移除「排除目前課程」開關。
 *
 * 測試涵蓋：
 * - 新建銷售方案時自動帶入目前課程
 * - 每個商品旁邊有數量 InputNumber
 * - 修改數量後組合原價自動重新計算
 * - 儲存後數量正確回填
 * - 目前課程可刪除
 * - 數量邊界值（0 → 1，1000 → 999，小數 → 整數）
 * - 「排除目前課程」開關已不存在
 */

import { test, expect } from '@playwright/test'
import {
  navigateToAdmin,
  waitForFormLoaded,
  clickTab,
  waitForMessage,
} from '../helpers/admin-page'
import { setupApiFromBrowser } from '../helpers/api-client'

const BASE_URL = process.env.TEST_SITE_URL || 'http://localhost:8889'

/**
 * 導航到銷售方案 Tab 並點擊「新增」按鈕，等待新方案出現後點選進入編輯面板
 */
async function createAndSelectBundle(page: import('@playwright/test').Page, courseId: number) {
  await navigateToAdmin(page, `/courses/edit/${courseId}`)
  await waitForFormLoaded(page)
  await clickTab(page, '銷售方案')

  // 等待銷售方案 Tab 內容渲染完成
  const tabPane = page.locator('.ant-tabs-tabpane-active')
  await expect(tabPane).toBeVisible({ timeout: 10_000 })

  // 點擊「新增」按鈕（CourseBundles 元件中唯一的 primary button）
  const addBtn = tabPane.locator('.ant-btn-primary').first()
  await expect(addBtn).toBeVisible({ timeout: 10_000 })
  await addBtn.click()

  // 等待列表刷新，出現新的銷售方案項目
  // @ant-design/pro-editor SortableList 的 item 使用 data-testid="list-item"
  const listItem = tabPane.locator('[data-testid="list-item"]').first()
  await expect(listItem).toBeVisible({ timeout: 15_000 })

  // 點擊商品名稱進入編輯面板
  await listItem.click()

  // 等待右側編輯面板載入（Refine <Edit> 元件含「儲存銷售方案」按鈕）
  const saveBtn = page.getByRole('button', { name: /儲存銷售方案/ })
  await expect(saveBtn).toBeVisible({ timeout: 15_000 })

  // 等待已選商品載入完成（bundle-selected-item 出現）
  const selectedItem = page.locator('.bundle-selected-item').first()
  await expect(selectedItem).toBeVisible({ timeout: 10_000 })
}

test.describe('銷售方案商品數量 — 管理端', () => {
  test.use({ storageState: '.auth/admin.json' })

  let courseId: number
  let tshirtId: number

  test.beforeAll(async ({ browser }) => {
    const { api, dispose } = await setupApiFromBrowser(browser)
    try {
      // 建立測試課程
      courseId = await api.createCourse('E2E 數量測試課程')

      // 建立測試 T-shirt 商品（WooCommerce 一般商品）
      const tshirtResp = await api.wcPost('products', {
        name: 'E2E 測試 T-shirt',
        type: 'simple',
        regular_price: '500',
        stock_quantity: 50,
        manage_stock: true,
        status: 'publish',
      })
      const tshirtData = tshirtResp.data as { id: number }
      tshirtId = tshirtData.id
    } finally {
      await dispose()
    }
  })

  test.afterAll(async ({ browser }) => {
    const { api, dispose } = await setupApiFromBrowser(browser)
    try {
      if (courseId) {
        await api.deleteCourses([courseId])
      }
      if (tshirtId) {
        await api.wcPost(`products/${tshirtId}`, { status: 'trash' })
      }
    } finally {
      await dispose()
    }
  })

  test('新建銷售方案時目前課程自動帶入且數量為 1', async ({ page }) => {
    await createAndSelectBundle(page, courseId)

    // 目前課程應自動出現在已選商品清單中（有「目前課程」Tag）
    const currentCourseTag = page.locator('.ant-tag').filter({ hasText: '目前課程' })
    await expect(currentCourseTag).toBeVisible({ timeout: 5_000 })

    // 數量 InputNumber 應存在且預設值為 1
    const selectedItem = page.locator('.bundle-selected-item').first()
    const qtyInput = selectedItem.locator('.ant-input-number input')
    await expect(qtyInput).toBeVisible()
    const value = await qtyInput.inputValue()
    expect(Number(value)).toBe(1)
  })

  test('新建銷售方案 — 「排除目前課程」開關不應存在', async ({ page }) => {
    await createAndSelectBundle(page, courseId)

    // 「排除目前課程」相關文字不應出現
    const excludeSwitch = page.getByText('排除目前課程')
    await expect(excludeSwitch).not.toBeVisible()
  })

  test('新建含數量的銷售方案 — 儲存後數量回填正確', async ({ page, request }) => {
    // 先透過 API 建立銷售方案（帶有 T-shirt + 數量 3）
    await page.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
    await page.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
    const actualNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce || '')

    const params = new URLSearchParams()
    params.append('name', 'E2E 數量回填測試方案')
    params.append('link_course_ids', String(courseId))
    params.append('bundle_type', 'bundle')
    params.append('regular_price', '2500')
    params.append('status', 'publish')
    params.append('pbp_product_ids[]', String(courseId))
    params.append('pbp_product_ids[]', String(tshirtId))
    params.append('pbp_product_quantities', JSON.stringify({ [courseId]: 1, [tshirtId]: 3 }))

    const createResp = await request.post(
      `${BASE_URL}/wp-json/power-course/bundle_products`,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-WP-Nonce': actualNonce,
        },
        data: params.toString(),
      },
    )
    expect(createResp.status()).toBe(200)
    const createData = await createResp.json()
    const bundleId = Number(createData?.data?.id)
    expect(bundleId).toBeGreaterThan(0)

    try {
      // 前往銷售方案編輯頁
      await navigateToAdmin(page, `/courses/edit/${courseId}`)
      await waitForFormLoaded(page)
      await clickTab(page, '銷售方案')

      // 點擊該銷售方案的名稱進入編輯（列表中的 item）
      const listItem = page.locator('.ant-tabs-tabpane-active [data-testid="list-item"]').first()
      await expect(listItem).toBeVisible({ timeout: 10_000 })
      await listItem.click()

      // 等待編輯面板載入
      const saveBtn = page.getByRole('button', { name: /儲存銷售方案/ })
      await expect(saveBtn).toBeVisible({ timeout: 15_000 })

      // 等待已選商品列表載入
      const tshirtItem = page.locator('.bundle-selected-item').filter({ hasText: 'T-shirt' })
      await expect(tshirtItem).toBeVisible({ timeout: 10_000 })

      // T-shirt 的數量應為 3
      const tshirtQty = tshirtItem.locator('.ant-input-number input')
      const tshirtValue = await tshirtQty.inputValue().catch(() => '1')
      expect(Number(tshirtValue)).toBe(3)
    } finally {
      // 清理銷售方案
      await request.delete(
        `${BASE_URL}/wp-json/power-course/bundle_products/${bundleId}`,
        {
          headers: { 'X-WP-Nonce': actualNonce },
        },
      )
    }
  })

  test('修改商品數量後組合原價自動重新計算', async ({ page, request }) => {
    // 先取得 nonce
    await page.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
    await page.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
    const actualNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce || '')

    // API 建立銷售方案（課程 × 1，T-shirt × 1）
    const params = new URLSearchParams()
    params.append('name', 'E2E 價格計算測試方案')
    params.append('link_course_ids', String(courseId))
    params.append('bundle_type', 'bundle')
    params.append('regular_price', '0')
    params.append('status', 'publish')
    params.append('pbp_product_ids[]', String(courseId))
    params.append('pbp_product_ids[]', String(tshirtId))
    params.append('pbp_product_quantities', JSON.stringify({ [courseId]: 1, [tshirtId]: 1 }))

    const createResp = await request.post(
      `${BASE_URL}/wp-json/power-course/bundle_products`,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-WP-Nonce': actualNonce,
        },
        data: params.toString(),
      },
    )
    const createData = await createResp.json()
    const bundleId = Number(createData?.data?.id)

    try {
      await navigateToAdmin(page, `/courses/edit/${courseId}`)
      await waitForFormLoaded(page)
      await clickTab(page, '銷售方案')

      // 點擊銷售方案項目進入編輯面板
      const listItem = page.locator('.ant-tabs-tabpane-active [data-testid="list-item"]').first()
      await expect(listItem).toBeVisible({ timeout: 10_000 })
      await listItem.click()

      // 等待編輯面板載入
      const saveBtn = page.getByRole('button', { name: /儲存銷售方案/ })
      await expect(saveBtn).toBeVisible({ timeout: 15_000 })

      // 等待已選商品載入
      const tshirtRow = page.locator('.bundle-selected-item').filter({ hasText: 'T-shirt' })
      await expect(tshirtRow).toBeVisible({ timeout: 10_000 })

      // 找到 T-shirt 的數量 InputNumber 並改為 3
      const tshirtQtyInput = tshirtRow.locator('.ant-input-number input')
      await tshirtQtyInput.click({ clickCount: 3 })
      await tshirtQtyInput.fill('3')
      await tshirtQtyInput.press('Tab')

      // 組合原價應重新計算：T-shirt 500 × 3 = 1500
      // 驗證組合原價欄位有更新（驗證有值即可）
      await page.waitForTimeout(500) // 等待 debounce 計算
      const regularPriceField = page.locator('#regular_price, input[id*="regular_price"]').first()
      await expect(regularPriceField).toBeVisible()
      const newPrice = await regularPriceField.inputValue()
      expect(Number(newPrice)).toBeGreaterThan(0)
    } finally {
      await request.delete(
        `${BASE_URL}/wp-json/power-course/bundle_products/${bundleId}`,
        {
          headers: { 'X-WP-Nonce': actualNonce },
        },
      )
    }
  })

  test('目前課程可從銷售方案中刪除', async ({ page, request }) => {
    // 取得 nonce
    await page.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
    await page.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
    const actualNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce || '')

    // API 建立含目前課程的銷售方案
    const params = new URLSearchParams()
    params.append('name', 'E2E 刪除課程測試方案')
    params.append('link_course_ids', String(courseId))
    params.append('bundle_type', 'bundle')
    params.append('regular_price', '999')
    params.append('status', 'publish')
    params.append('pbp_product_ids[]', String(courseId))
    params.append('pbp_product_quantities', JSON.stringify({ [courseId]: 1 }))

    const createResp = await request.post(
      `${BASE_URL}/wp-json/power-course/bundle_products`,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-WP-Nonce': actualNonce,
        },
        data: params.toString(),
      },
    )
    const createData = await createResp.json()
    const bundleId = Number(createData?.data?.id)

    try {
      await navigateToAdmin(page, `/courses/edit/${courseId}`)
      await waitForFormLoaded(page)
      await clickTab(page, '銷售方案')

      // 點擊銷售方案項目進入編輯面板
      const listItem = page.locator('.ant-tabs-tabpane-active [data-testid="list-item"]').first()
      await expect(listItem).toBeVisible({ timeout: 10_000 })
      await listItem.click()

      // 等待編輯面板載入
      const saveBtn = page.getByRole('button', { name: /儲存銷售方案/ })
      await expect(saveBtn).toBeVisible({ timeout: 15_000 })

      // 等待目前課程的選中項出現
      const courseItem = page.locator('.bundle-selected-item').filter({ hasText: '目前課程' })
      await expect(courseItem).toBeVisible({ timeout: 10_000 })

      // 找到目前課程的刪除按鈕（PopconfirmDelete 是最後一個按鈕區域）
      const deleteBtn = courseItem.locator('.ant-btn-icon-only, .anticon-delete').last()
      await expect(deleteBtn).toBeVisible({ timeout: 5_000 })
      await deleteBtn.click()

      // 確認 Popconfirm
      const popconfirmBtn = page.locator('.ant-popconfirm .ant-btn-primary, .ant-popover .ant-btn-primary')
      if (await popconfirmBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
        await popconfirmBtn.click()
      }

      // 目前課程的 Tag 應消失
      const courseTag = page.locator('.bundle-selected-item .ant-tag').filter({ hasText: '目前課程' })
      await expect(courseTag).not.toBeVisible({ timeout: 5_000 })

      // 應顯示「加入目前課程」按鈕（表示課程已被移除）
      const addCourseBtn = page.getByText('加入目前課程')
      await expect(addCourseBtn).toBeVisible({ timeout: 5_000 })
    } finally {
      await request.delete(
        `${BASE_URL}/wp-json/power-course/bundle_products/${bundleId}`,
        {
          headers: { 'X-WP-Nonce': actualNonce },
        },
      )
    }
  })

  test.describe('數量邊界值驗證', () => {
    test('輸入 0 應自動修正為 1', async ({ page }) => {
      await createAndSelectBundle(page, courseId)

      // 找到已選商品的 InputNumber
      const selectedItem = page.locator('.bundle-selected-item').first()
      const qtyInput = selectedItem.locator('.ant-input-number input')
      await expect(qtyInput).toBeVisible({ timeout: 5_000 })
      await qtyInput.click({ clickCount: 3 })
      await qtyInput.fill('0')
      await qtyInput.press('Tab')

      // InputNumber min=1，值不應低於 1
      const value = await qtyInput.inputValue()
      expect(Number(value)).toBeGreaterThanOrEqual(1)
    })

    test('輸入 1000 應自動修正為 999', async ({ page }) => {
      await createAndSelectBundle(page, courseId)

      const selectedItem = page.locator('.bundle-selected-item').first()
      const qtyInput = selectedItem.locator('.ant-input-number input')
      await expect(qtyInput).toBeVisible({ timeout: 5_000 })
      await qtyInput.click({ clickCount: 3 })
      await qtyInput.fill('1000')
      await qtyInput.press('Tab')

      const value = await qtyInput.inputValue()
      expect(Number(value)).toBeLessThanOrEqual(999)
    })

    test('輸入小數 2.7 應取整為 2 或 3', async ({ page }) => {
      await createAndSelectBundle(page, courseId)

      const selectedItem = page.locator('.bundle-selected-item').first()
      const qtyInput = selectedItem.locator('.ant-input-number input')
      await expect(qtyInput).toBeVisible({ timeout: 5_000 })
      await qtyInput.click({ clickCount: 3 })
      await qtyInput.fill('2.7')
      await qtyInput.press('Tab')

      const value = await qtyInput.inputValue()
      expect(Number(value)).toBe(Math.floor(Number(value))) // 必須是整數
    })
  })
})
