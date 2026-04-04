/**
 * 銷售方案商品數量 E2E 測試（前台）
 *
 * Issue #183：前台銷售頁數量 > 1 的商品應顯示「×N」標示。
 *
 * 測試涵蓋：
 * - 數量 > 1 的商品旁邊顯示「×N」
 * - 數量 = 1 的商品不顯示「×N」
 * - 無 pbp_product_quantities meta 時不顯示「×N」（向下相容）
 */

import { test, expect } from '@playwright/test'
import { setupApiFromBrowser } from '../helpers/api-client'

const BASE_URL = process.env.TEST_SITE_URL || 'http://localhost:8889'

test.describe('銷售方案前台數量顯示', () => {
  let courseId: number
  let tshirtId: number
  let bundleId: number
  let coursePermalink: string

  test.beforeAll(async ({ browser }) => {
    const { api, dispose } = await setupApiFromBrowser(browser)
    try {
      // 建立並發佈測試課程
      courseId = await api.createCourse('E2E 前台數量顯示測試課程')
      await api.updateCourse(courseId, {
        regular_price: '1000',
        status: 'publish',
        type: 'simple',
        course_schedule: '0',
        editor: 'power-editor',
      })

      // 取得課程 permalink
      const courseData = await api.wcGet<{ permalink: string }>(`products/${courseId}`)
      coursePermalink = (courseData.data as { permalink: string }).permalink

      // 建立測試 T-shirt 商品
      const tshirtResp = await api.wcPost('products', {
        name: 'E2E 前台測試 T-shirt',
        type: 'simple',
        regular_price: '500',
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
      if (bundleId) {
        const { page: cleanPage, context } = await browser.newContext({ storageState: '.auth/admin.json' }).then(async (ctx) => {
          const p = await ctx.newPage()
          return { page: p, context: ctx }
        })
        await cleanPage.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
        await cleanPage.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
        const nonce = await cleanPage.evaluate(() => (window as any).wpApiSettings?.nonce || '')
        await cleanPage.request.delete(`${BASE_URL}/wp-json/power-course/v2/bundle_products/${bundleId}`, {
          headers: { 'X-WP-Nonce': nonce },
        })
        await context.close()
      }
      if (courseId) await api.deleteCourses([courseId])
      if (tshirtId) await api.wcPost(`products/${tshirtId}`, { status: 'trash' })
    } finally {
      await dispose()
    }
  })

  test('數量 > 1 的商品顯示「×N」，數量 = 1 的商品不顯示', async ({ browser }) => {
    // 建立銷售方案：課程 × 1，T-shirt × 3
    const { page: setupPage, context: setupCtx } = await browser.newContext({ storageState: '.auth/admin.json' }).then(async (ctx) => {
      const p = await ctx.newPage()
      return { page: p, context: ctx }
    })

    await setupPage.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
    await setupPage.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
    const nonce = await setupPage.evaluate(() => (window as any).wpApiSettings?.nonce || '')

    const params = new URLSearchParams()
    params.append('name', '年度套餐（前台顯示測試）')
    params.append('link_course_ids', String(courseId))
    params.append('bundle_type', 'bundle')
    params.append('regular_price', '2500')
    params.append('status', 'publish')
    params.append('catalog_visibility', 'visible')
    params.append('pbp_product_ids[]', String(courseId))
    params.append('pbp_product_ids[]', String(tshirtId))
    params.append('pbp_product_quantities', JSON.stringify({ [courseId]: 1, [tshirtId]: 3 }))

    const createResp = await setupPage.request.post(
      `${BASE_URL}/wp-json/power-course/v2/bundle_products`,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-WP-Nonce': nonce,
        },
        data: params.toString(),
      },
    )
    const createData = await createResp.json()
    bundleId = Number(createData?.data?.id)
    await setupCtx.close()

    // 前台：訪客瀏覽課程銷售頁
    const { page, context } = await browser.newContext().then(async (ctx) => {
      const p = await ctx.newPage()
      return { page: p, context: ctx }
    })

    try {
      await page.goto(coursePermalink, { waitUntil: 'domcontentloaded' })
      await page.waitForLoadState('networkidle')

      // 找銷售方案卡片
      const bundleCard = page.locator('[class*="bundle"], .bundle-product-card, .bundle-products').first()
      await expect(bundleCard).toBeVisible({ timeout: 15_000 })

      // T-shirt（數量 3）應顯示 ×3
      const tshirtItem = bundleCard.getByText('T-shirt').locator('..')
      const qtyDisplay = tshirtItem.locator('text=×3')
      await expect(qtyDisplay).toBeVisible({ timeout: 5_000 })

      // 課程（數量 1）不應顯示 ×1 或任何 × 符號
      const courseItem = bundleCard.getByText('E2E 前台數量顯示測試課程').locator('..')
      const courseQtyDisplay = courseItem.locator('text=/×\\d+/')
      await expect(courseQtyDisplay).not.toBeVisible()
    } finally {
      await context.close()
    }
  })

  test('無 pbp_product_quantities meta 的舊銷售方案不顯示「×N」', async ({ browser }) => {
    // 建立銷售方案，不設定 pbp_product_quantities
    const { page: setupPage, context: setupCtx } = await browser.newContext({ storageState: '.auth/admin.json' }).then(async (ctx) => {
      const p = await ctx.newPage()
      return { page: p, context: ctx }
    })

    await setupPage.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
    await setupPage.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
    const nonce = await setupPage.evaluate(() => (window as any).wpApiSettings?.nonce || '')

    const params = new URLSearchParams()
    params.append('name', '舊版方案（無數量 meta）')
    params.append('link_course_ids', String(courseId))
    params.append('bundle_type', 'bundle')
    params.append('regular_price', '1000')
    params.append('status', 'publish')
    params.append('catalog_visibility', 'visible')
    params.append('pbp_product_ids[]', String(tshirtId))
    // 故意不傳 pbp_product_quantities

    const createResp = await setupPage.request.post(
      `${BASE_URL}/wp-json/power-course/v2/bundle_products`,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-WP-Nonce': nonce,
        },
        data: params.toString(),
      },
    )
    const createData = await createResp.json()
    const legacyBundleId = Number(createData?.data?.id)
    await setupCtx.close()

    // 前台：訪客瀏覽課程銷售頁
    const { page, context } = await browser.newContext().then(async (ctx) => {
      const p = await ctx.newPage()
      return { page: p, context: ctx }
    })

    try {
      await page.goto(coursePermalink, { waitUntil: 'domcontentloaded' })
      await page.waitForLoadState('networkidle')

      // 頁面上不應有 ×N 符號
      const anyQtyDisplay = page.locator('text=/×\\d+/')
      const qtyCount = await anyQtyDisplay.count()
      expect(qtyCount).toBe(0)
    } finally {
      await context.close()

      // 清理此測試建立的銷售方案
      const { page: cleanPage, context: cleanCtx } = await browser.newContext({ storageState: '.auth/admin.json' }).then(async (ctx) => {
        const p = await ctx.newPage()
        return { page: p, context: ctx }
      })
      await cleanPage.goto(`${BASE_URL}/wp-admin/`, { waitUntil: 'domcontentloaded' })
      await cleanPage.waitForFunction(() => !!(window as any).wpApiSettings?.nonce)
      const cleanNonce = await cleanPage.evaluate(() => (window as any).wpApiSettings?.nonce || '')
      await cleanPage.request.delete(`${BASE_URL}/wp-json/power-course/v2/bundle_products/${legacyBundleId}`, {
        headers: { 'X-WP-Nonce': cleanNonce },
      })
      await cleanCtx.close()
    }
  })
})
