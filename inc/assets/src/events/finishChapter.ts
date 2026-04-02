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

				// 線性觀看：解鎖下一個章節的 UI 鎖定狀態
				if (next_unlocked_chapter_id) {
					$(`li[data-post-id="${next_unlocked_chapter_id}"]`)
						.removeClass('pc-locked')
						.removeAttr('data-locked')
						.find('.pc-lock-icon')
						.remove()
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

				// 線性觀看：取消完成後重新整理頁面更新鎖定狀態
				const enable_sequential = (window as any).pc_data?.enable_sequential
				if (enable_sequential === 'yes') {
					window.location.reload()
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
		const is_currently_finished = $(this).hasClass('pc-btn-outline')

		// 線性觀看：取消完成時需要用戶確認
		const enable_sequential = (window as any).pc_data?.enable_sequential
		if (enable_sequential === 'yes' && is_currently_finished) {
			const confirmed = window.confirm(
				'取消完成後，後續章節將會重新鎖定，確定要取消嗎？'
			)
			if (!confirmed) {
				return
			}
		}

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
