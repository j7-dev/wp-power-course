/**
 * E2E 測試資料常數
 *
 * 所有測試共用的資料定義。帳密從環境變數讀取，其餘為固定常數。
 */

/** WordPress Admin 帳密 */
export const WP_ADMIN = {
	username: process.env.WP_ADMIN_USERNAME || 'admin',
	password: process.env.WP_ADMIN_PASSWORD || 'password',
}

/** 測試用學員帳號 */
export const TEST_STUDENT = {
	username: 'e2e_student',
	password: 'e2e_student_pass',
	email: 'e2e_student@test.local',
	firstName: '測試',
	lastName: '學員',
	displayName: '測試學員',
}

/** 測試用講師帳號 */
export const TEST_TEACHER = {
	username: 'e2e_teacher',
	password: 'e2e_teacher_pass',
	email: 'e2e_teacher@test.local',
	firstName: '測試',
	lastName: '講師',
	displayName: '測試講師',
}

/** 測試課程資料 */
export const TEST_COURSE = {
	name: 'E2E 測試課程',
	regularPrice: '1000',
	salePrice: '800',
	description: '這是一堂 E2E 測試用的課程，請勿刪除。',
	shortDescription: 'E2E 測試課程簡介',
}

/** 測試課程（免費） */
export const TEST_FREE_COURSE = {
	name: 'E2E 免費測試課程',
	regularPrice: '0',
	description: '免費的 E2E 測試課程',
}

/** 測試章節資料 */
export const TEST_CHAPTERS = {
	chapter1: {
		title: '第一章 課程介紹',
	},
	chapter2: {
		title: '第二章 進階內容',
	},
	subChapter1: {
		title: '1-1 基礎觀念',
	},
	subChapter2: {
		title: '1-2 實作練習',
	},
}

/** 測試銷售方案資料 */
export const TEST_BUNDLE_PRODUCT = {
	name: 'E2E 測試銷售方案',
	regularPrice: '2000',
	salePrice: '1500',
}

/** Email 模板資料 */
export const TEST_EMAIL_TEMPLATE = {
	subject: 'E2E 測試郵件 - 課程開通通知',
	trigger: 'course_granted',
}

/** WooCommerce 結帳資料 */
export const CHECKOUT_DATA = {
	firstName: '測試',
	lastName: '買家',
	address: '台北市中正區忠孝東路一段1號',
	city: '台北市',
	postcode: '100',
	phone: '0912345678',
	email: 'e2e_buyer@test.local',
}

/** 常用 URL 路徑 */
export const URLS = {
	adminDashboard: '/wp-admin/',
	adminCourses: '/wp-admin/admin.php?page=power-course#/courses',
	adminTeachers: '/wp-admin/admin.php?page=power-course#/teachers',
	adminStudents: '/wp-admin/admin.php?page=power-course#/students',
	adminProducts: '/wp-admin/admin.php?page=power-course#/products',
	adminEmails: '/wp-admin/admin.php?page=power-course#/emails',
	adminSettings: '/wp-admin/admin.php?page=power-course#/settings',
	adminAnalytics: '/wp-admin/admin.php?page=power-course#/analytics',
	adminMediaLibrary: '/wp-admin/admin.php?page=power-course#/media-library',
	adminBunnyMedia:
		'/wp-admin/admin.php?page=power-course#/bunny-media-library',
	adminShortcodes: '/wp-admin/admin.php?page=power-course#/shortcodes',
	wcMyAccount: '/my-account/',
	shop: '/shop/',
	cart: '/cart/',
	checkout: '/checkout/',
}

/** API 端點 */
export const API_ENDPOINTS = {
	courses: 'power-course/courses',
	chapters: 'power-course/chapters',
	users: 'power-course/users',
	options: 'power-course/options',
	upload: 'power-course/upload',
	reports: 'power-course/reports/revenue/stats',
}

/** Timeout 常數 */
export const TIMEOUTS = {
	spaLoad: 15_000,
	apiResponse: 10_000,
	fileUpload: 30_000,
	pageNavigation: 15_000,
	wpEnvStart: 120_000,
}
