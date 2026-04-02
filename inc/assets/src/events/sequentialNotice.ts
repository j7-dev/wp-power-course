/* eslint-disable @typescript-eslint/ban-ts-comment */
import $ from 'jquery'

/**
 * 線性觀看模式導向提示
 * 當用戶被導向至可存取章節時，顯示友善提示
 */
export function sequentialNotice() {
	const notice = (window as any).pc_data?.sequential_notice
	if (!notice) return

	const Dialog = $('#finish-chapter__dialog')
	if (Dialog.length > 0) {
		Dialog.find('#finish-chapter__dialog__title').text('提示')
		Dialog.find('#finish-chapter__dialog__message').text(notice)
		// @ts-ignore
		Dialog?.[0]?.showModal()
	} else {
		alert(notice)
	}

	// 清除 URL 中的 pc_locked query param，避免重複顯示
	const url = new URL(window.location.href)
	if (url.searchParams.has('pc_locked')) {
		url.searchParams.delete('pc_locked')
		window.history.replaceState({}, '', url.toString())
	}
}
