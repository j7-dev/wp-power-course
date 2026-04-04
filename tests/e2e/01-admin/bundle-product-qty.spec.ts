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

import { test, expect } from '@playwright/test'
import {
  navigateToAdmin,
  waitForFormLoaded,
  clickTab,
} from '../helpers/admin-page'
import { setupApiFromBrowser } from '../helpers/api-client'

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

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待銷售方案表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    // 驗證當前課程應自動出現在商品列表中（含數量輸入框）
    // 這個 assertion 在 Red 階段會失敗，因為功能尚未實作
    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()

    // 當前課程應出現在已選商品列表中
    await expect(
      bundleFormContent.locator('[data-testid="selected-products-list"]'),
    ).toBeVisible({ timeout: 10_000 })

    // 當前課程的課程名稱應顯示在列表中
    await expect(bundleFormContent).toContainText('E2E 銷售方案數量測試課程', {
      timeout: 5_000,
    })
  })

  test('商品數量輸入框預設值應為 1', async ({ page }) => {
    await navigateToAdmin(page, `/courses/edit/${courseId}`)
    await waitForFormLoaded(page)
    await clickTab(page, '銷售方案')

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    // 驗證數量輸入框預設值為 1
    // 這個 assertion 在 Red 階段會失敗（InputNumber 尚未實作）
    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()
    const qtyInput = bundleFormContent
      .locator('.ant-input-number input')
      .first()
    await expect(qtyInput).toBeVisible({ timeout: 10_000 })
    await expect(qtyInput).toHaveValue('1')
  })

  test('修改數量後儲存，重新開啟應回顯正確數量', async ({ page }) => {
    await navigateToAdmin(page, `/courses/edit/${courseId}`)
    await waitForFormLoaded(page)
    await clickTab(page, '銷售方案')

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()

    // 找到數量輸入框並修改為 3
    const qtyInput = bundleFormContent
      .locator('.ant-input-number input')
      .first()
    await expect(qtyInput).toBeVisible({ timeout: 10_000 })
    await qtyInput.click({ clickCount: 3 })
    await qtyInput.fill('3')

    // 設定銷售方案名稱（必填）
    const nameInput = bundleFormContent.locator('input[id*="name"]').first()
    if (await nameInput.isVisible()) {
      await nameInput.fill('數量測試方案')
    }

    // 儲存
    const saveBtn = bundleFormContent
      .locator('button[type="submit"], .ant-btn-primary')
      .last()
    await saveBtn.click()

    // 等待儲存完成（Drawer/Modal 關閉或成功訊息）
    await page.waitForTimeout(2_000)

    // 重新開啟剛才建立的銷售方案
    const bundleListItem = tabContent
      .locator('.ant-list-item, .ant-table-row')
      .filter({ hasText: '數量測試方案' })
      .first()
    if (await bundleListItem.isVisible()) {
      await bundleListItem.locator('button, .ant-btn').first().click()

      // 等待表單重新開啟
      await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
        timeout: 10_000,
      })

      // 驗證數量回顯為 3
      const reopenedFormContent = page
        .locator('.ant-drawer-body, .ant-modal-body')
        .first()
      const reopenedQtyInput = reopenedFormContent
        .locator('.ant-input-number input')
        .first()
      await expect(reopenedQtyInput).toHaveValue('3', { timeout: 5_000 })
    }
  })

  test('輸入 0 應自動修正為 1', async ({ page }) => {
    await navigateToAdmin(page, `/courses/edit/${courseId}`)
    await waitForFormLoaded(page)
    await clickTab(page, '銷售方案')

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()

    // 找到數量輸入框並輸入 0
    const qtyInput = bundleFormContent
      .locator('.ant-input-number input')
      .first()
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

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()

    // 確認「排除目前課程」文字不存在於 UI
    await expect(bundleFormContent).not.toContainText('排除目前課程', {
      timeout: 3_000,
    })
    await expect(bundleFormContent).not.toContainText('exclude_main_course', {
      timeout: 3_000,
    })
  })

  test('移除當前課程後應可成功儲存', async ({ page }) => {
    await navigateToAdmin(page, `/courses/edit/${courseId}`)
    await waitForFormLoaded(page)
    await clickTab(page, '銷售方案')

    // 點擊新建銷售方案按鈕
    const tabContent = page.locator('.ant-tabs-tabpane-active')
    await tabContent.locator('.ant-btn').first().click()

    // 等待表單開啟
    await expect(page.locator('.ant-drawer, .ant-modal')).toBeVisible({
      timeout: 10_000,
    })

    const bundleFormContent = page
      .locator('.ant-drawer-body, .ant-modal-body')
      .first()

    // 找到課程項目並點擊刪除按鈕
    const courseItem = bundleFormContent
      .locator('[data-testid="selected-products-list"] .ant-list-item')
      .first()
    if (await courseItem.isVisible()) {
      const deleteBtn = courseItem.locator(
        'button[aria-label="刪除"], .anticon-delete, button:has(.anticon-close)',
      )
      await deleteBtn.first().click()
    }

    // 儲存（移除當前課程後的方案）
    const saveBtn = bundleFormContent
      .locator('button[type="submit"], .ant-btn-primary')
      .last()
    await saveBtn.click()

    // 應顯示成功訊息（不應有錯誤）
    await expect(page.locator('.ant-message-success')).toBeVisible({
      timeout: 10_000,
    })
  })
})
