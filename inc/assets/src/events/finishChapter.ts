/* eslint-disable @typescript-eslint/ban-ts-comment */
import $ from 'jquery'
import { store, finishChapterAtom } from '../store'
import { site_url } from '../utils'

/**
 * 追蹤各章節是否已自動觸發完成，防止同一頁面週期內重複呼叫 API
 * key 為 chapterId（字串），value 為是否已觸發
 */
const hasAutoFinishedMap: Record<string, boolean> = {}

export function finishChapter() {
	const Dialog = $('#finish-chapter__dialog')
	const FinishButton = $('#finish-chapter__button')

	store.sub(finishChapterAtom, () => {
		const {
			isLoading,
			showDialog,
			isSuccess,
			chapter_id,
			isError,
			dialogMessage,
			isFinished,
			progress,
			icon_html,
			unlocked_chapter_ids,
		} = store.get(finishChapterAtom)

		const ChapterIcon = $(`li[data-post-id="${chapter_id}"]`).find('.pc-chapter-icon')

		if (isLoading) {
			FinishButton.find('.pc-loading-spinner').removeClass('tw-hidden')
		} else {
			FinishButton.find('.pc-loading-spinner').addClass('tw-hidden')
		}

		if (isSuccess) {
			if (ChapterIcon?.length > 0) {
				ChapterIcon.html(icon_html)
			}
			Dialog.find('#finish-chapter__dialog__title').text('成功')
			Dialog.find('#finish-chapter__dialog__message').text(dialogMessage)

			// FinishButton.hide()
			if (isFinished === true) {
				FinishButton.removeClass('text-white').addClass('pc-btn-outline border-solid')
					.find('span:first-child')
					.text('標示為未完成')

				$('#classroom-chapter_title-badge')
					.removeClass('pc-badge-accent')
					.addClass('pc-badge-secondary')
					.text('已完成')
			}

			if (isFinished === false) {
				FinishButton.removeClass('pc-btn-outline border-solid').addClass('text-white')
					.find('span:first-child')
					.text('標示為已完成')

				$('#classroom-chapter_title-badge')
					.removeClass('pc-badge-secondary')
					.addClass('pc-badge-accent')
					.text('未完成')
			}

			// 調整進度條
			if (progress !== undefined) {
				$('progress').attr('value', progress).prev().text(`${progress}%`)
			}

			// 線性觀看：即時更新側邊欄鎖定狀態
			if (unlocked_chapter_ids !== undefined && unlocked_chapter_ids !== null) {
				updateLinearViewingUI(unlocked_chapter_ids)
			}
		}

		if (isError) {
			Dialog.find('#finish-chapter__dialog__title').text('錯誤')
			Dialog.find('#finish-chapter__dialog__message').text(dialogMessage)
		}

		if (showDialog) {
			// @ts-ignore
			Dialog?.[0]?.showModal()
		} else {
			// @ts-ignore
			Dialog?.[0]?.close()
		}
	})

	// 監聽影片自動完成事件（由 Player.tsx dispatch 的 pc:auto-finish-chapter）
	document.addEventListener('pc:auto-finish-chapter', (event) => {
		const customEvent = event as CustomEvent<{ chapterId: number; courseId: number }>
		const { chapterId, courseId } = customEvent.detail

		// 確認事件的 chapterId 與目前頁面的按鈕 data-chapter-id 相符
		const buttonChapterId = Number(FinishButton.data('chapter-id'))
		if (buttonChapterId !== chapterId) return

		// 若章節已完成（按鈕為「標示為未完成」狀態），不重複呼叫
		const buttonText = FinishButton.find('span:first-child').text()
		if (buttonText === '標示為未完成') return

		// 防止同一頁面週期重複觸發
		const mapKey = String(chapterId)
		if (hasAutoFinishedMap[mapKey]) return
		hasAutoFinishedMap[mapKey] = true

		// 靜默呼叫 toggle API，不顯示對話框
		$.ajax({
			url: `${site_url}/wp-json/power-course/toggle-finish-chapters/${chapterId}`,
			type: 'post',
			data: {
				course_id: courseId,
			},
			headers: {
				'X-WP-Nonce': (window as any).pc_data?.nonce,
			},
			timeout: 30000,
			complete(xhr) {
				const is_this_chapter_finished =
					xhr?.responseJSON?.data?.is_this_chapter_finished
				const progress = xhr?.responseJSON?.data?.progress
				const icon_html = xhr?.responseJSON?.data?.icon_html
				const message = xhr?.responseJSON?.message || ''

				store.set(finishChapterAtom, (prev) => ({
					...prev,
					course_id: courseId,
					chapter_id: chapterId,
					isLoading: false,
					// 靜默模式：不彈出對話框
					showDialog: false,
					isSuccess: true,
					dialogMessage: message,
					isFinished: is_this_chapter_finished,
					progress,
					icon_html,
				}))
			},
			error(_xhr, _status, errorMsg) {
				console.warn('[PowerCourse] 自動完成章節 API 呼叫失敗：', errorMsg)
			},
		})
	})

	// 完成章節事件
	FinishButton.on('click', function (e) {
		const course_id = $(this).data('course-id')
		const chapter_id = $(this).data('chapter-id')

		store.set(finishChapterAtom, (prev) => ({
			...prev,
			course_id,
			chapter_id,
			isLoading: true,
		}))

		$.ajax({
			url: `${site_url}/wp-json/power-course/toggle-finish-chapters/${chapter_id}`,
			type: 'post',
			data: {
				course_id,
			},
			headers: {
				'X-WP-Nonce': (window as any).pc_data?.nonce,
			},
			timeout: 30000,
			success(response) {
				const { code } = response
				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isSuccess: '200' === code,
				}))
			},
			error(error) {
				console.log('error', error)
				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isSuccess: false,
					isError: true,
				}))
			},
			complete(xhr) {
				const message = xhr?.responseJSON?.message || '發生錯誤，請稍後再試'
				const is_this_chapter_finished =
					xhr?.responseJSON?.data?.is_this_chapter_finished
				const progress = xhr?.responseJSON?.data?.progress
				const icon_html = xhr?.responseJSON?.data?.icon_html
				// 線性觀看：取得已解鎖章節 IDs
				const unlocked_chapter_ids =
					xhr?.responseJSON?.data?.unlocked_chapter_ids

				// 手動取消完成（isFinished 變為 false）時，重置自動完成 flag，允許再次自動觸發
				if (is_this_chapter_finished === false) {
					const mapKey = String(chapter_id)
					delete hasAutoFinishedMap[mapKey]
				}

				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isLoading: false,
					showDialog: true,
					dialogMessage: message,
					isFinished: is_this_chapter_finished,
					progress,
					icon_html,
					unlocked_chapter_ids,
				}))
			},
		})
	})

	// 線性觀看：頁面載入時顯示 redirect flash message
	const flashMsg = (window as any).pc_data?.linear_flash_message
	if (flashMsg) {
		Dialog.find('#finish-chapter__dialog__title').text('提示')
		Dialog.find('#finish-chapter__dialog__message').text(flashMsg)
		// @ts-ignore
		Dialog?.[0]?.showModal()
	}
}

/**
 * 線性觀看：根據 API 回傳的 unlocked_chapter_ids 即時更新側邊欄鎖定狀態
 * @param unlocked_chapter_ids 已解鎖章節 IDs 陣列
 */
function updateLinearViewingUI(unlocked_chapter_ids: number[]) {
	const $sider = $('#pc-sider__main-chapters')
	if (!$sider.length) return

	// 解鎖原本被鎖定的章節
	$sider.find('li[data-locked="true"]').each(function () {
		const postId = Number($(this).data('post-id'))
		if (unlocked_chapter_ids.includes(postId)) {
			const originalHref = $(this).data('original-href') as string | undefined
			$(this)
				.removeAttr('data-locked')
				.removeClass('opacity-50 cursor-not-allowed')
				.attr('data-href', originalHref ?? '')
			// 恢復一般圖示（移除鎖頭 SVG，由 PHP 的 icon html 取代）
			$(this)
				.find('.pc-chapter-icon')
				.find('.pc-lock-icon')
				.closest('.pc-tooltip')
				.replaceWith(
					'<div class="pc-tooltip pc-tooltip-right h-6" data-tip="點擊觀看"><svg class="w-6 h-6 fill-base-content/30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg></div>',
				)
		}
	})

	// 重新鎖定不在 unlocked 列表中的解鎖章節
	$sider.find('li:not([data-locked])').each(function () {
		const postId = Number($(this).data('post-id'))
		if (!unlocked_chapter_ids.includes(postId)) {
			const currentHref = $(this).data('href') as string | undefined
			// 儲存原始 href 後清空
			if (currentHref) {
				$(this).attr('data-original-href', currentHref)
			}
			$(this)
				.attr('data-locked', 'true')
				.addClass('opacity-50 cursor-not-allowed')
				.attr('data-href', '')
		}
	})

	// 更新 header 的「前往下一章節」按鈕
	const $nextBtn = $('#pc-next-chapter-btn')
	if ($nextBtn.length) {
		const nextChapterId = Number($nextBtn.data('next-chapter-id'))
		if (nextChapterId) {
			const isNextLocked = !unlocked_chapter_ids.includes(nextChapterId)
			if (isNextLocked && !$nextBtn.data('locked')) {
				// 下一章被鎖定：換成禁用按鈕
				$nextBtn.replaceWith(
					`<button id="pc-next-chapter-btn" class="pc-btn pc-btn-primary pc-btn-sm px-0 lg:px-4 text-white cursor-not-allowed opacity-70 w-full lg:w-auto text-xs sm:text-base" tabindex="-1" role="button" aria-disabled="true" data-locked="true" data-next-chapter-id="${nextChapterId}">完成本章節後即可觀看下一章</button>`,
				)
			} else if (!isNextLocked && $nextBtn.data('locked')) {
				// 下一章已解鎖：換成可點擊連結
				const originalHref = $nextBtn.data('original-href') as string | undefined
				const href = originalHref ?? ''
				$nextBtn.replaceWith(
					`<a id="pc-next-chapter-btn" href="${href}" data-next-chapter-id="${nextChapterId}" class="pc-btn pc-btn-primary pc-btn-sm px-0 lg:px-4 text-white w-full lg:w-auto text-xs sm:text-base">前往下一章節<svg class="size-3 sm:size-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.60439 4.23093C4.94586 3.73136 4 4.20105 4 5.02762V18.9724C4 19.799 4.94586 20.2686 5.60439 19.7691L14.7952 12.7967C15.3227 12.3965 15.3227 11.6035 14.7952 11.2033L5.60439 4.23093ZM2 5.02762C2 2.54789 4.83758 1.13883 6.81316 2.63755L16.004 9.60993C17.5865 10.8104 17.5865 13.1896 16.004 14.3901L6.81316 21.3625C4.83758 22.8612 2 21.4521 2 18.9724V5.02762Z" fill="#ffffff"></path><path d="M20 3C20 2.44772 20.4477 2 21 2C21.5523 2 22 2.44772 22 3V21C22 21.5523 21.5523 22 21 22C20.4477 22 20 21.5523 20 21V3Z" fill="#ffffff"></path></svg></a>`,
				)
			}
		}
	}
}
