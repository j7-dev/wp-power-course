/* eslint-disable @typescript-eslint/ban-ts-comment */
import $ from 'jquery'
import { store, finishChapterAtom } from '../store'
import { site_url } from '../utils'

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
			next_unlocked_chapter_id,
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

				// 線性觀看模式：即時解鎖下一個章節
				if (next_unlocked_chapter_id) {
					const $nextLi = $(`li[data-post-id="${next_unlocked_chapter_id}"]`)
					if ($nextLi.length > 0) {
						$nextLi.removeData('locked')
						$nextLi.removeAttr('data-locked')
						$nextLi.removeClass('pc-chapter-locked opacity-50 cursor-not-allowed')
						// 恢復 data-href 為實際連結（取 permalink）
						const nextPermalink = $nextLi.data('permalink') || ''
						if (nextPermalink) {
							$nextLi.data('href', nextPermalink)
							$nextLi.attr('data-href', nextPermalink)
						}
						// 更新圖示：以 video 圖示取代鎖頭圖示
						const $nextIcon = $nextLi.find('.pc-chapter-icon')
						if ($nextIcon.length > 0) {
							// 使用與完成回傳相同的 icon 更新方式（需要頁面 reload 取得正確圖示，這裡先簡單處理）
							$nextIcon.find('.pc-tooltip').attr('data-tip', '點擊觀看')
						}
					}
				}
			}

			if (isFinished === false) {
				FinishButton.removeClass('pc-btn-outline border-solid').addClass('text-white')
					.find('span:first-child')
					.text('標示為已完成')

				$('#classroom-chapter_title-badge')
					.removeClass('pc-badge-secondary')
					.addClass('pc-badge-accent')
					.text('未完成')

				// 取消完成操作：連鎖鎖定太複雜，直接重新整理頁面以確保一致性
				const isSequential = $('#pc-sider__main-chapters').data('is_sequential')
				if (isSequential === true || isSequential === 'true') {
					window.location.reload()
					return
				}
			}

			// 調整進度條
			if (progress !== undefined) {
				$('progress').attr('value', progress).prev().text(`${progress}%`)
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
				const next_unlocked_chapter_id =
					xhr?.responseJSON?.data?.next_unlocked_chapter_id

				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isLoading: false,
					showDialog: true,
					dialogMessage: message,
					isFinished: is_this_chapter_finished,
					progress,
					icon_html,
					next_unlocked_chapter_id,
				}))
			},
		})
	})
}
