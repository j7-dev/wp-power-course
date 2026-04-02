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
			next_chapter_unlocked,
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

			// 線性模式：JS 局部解鎖下一章節
			if (next_chapter_unlocked) {
				const nextChapterId = next_chapter_unlocked.chapter_id
				const nextChapterItem = $(`li[data-post-id="${nextChapterId}"]`)

				if (nextChapterItem.length > 0) {
					// 移除鎖定屬性
					nextChapterItem.removeAttr('data-locked')
					// 替換鎖頭圖示
					nextChapterItem.find('.pc-lock-icon').remove()
					// 更新下一章節的 icon（若 API 回傳）
					if (next_chapter_unlocked.icon_html) {
						nextChapterItem.find('.pc-chapter-icon').html(next_chapter_unlocked.icon_html)
					}
				}

				Dialog.find('#finish-chapter__dialog__title').text('已完成！')
				Dialog.find('#finish-chapter__dialog__message').text(
					`「${next_chapter_unlocked.chapter_title}」已解鎖，可以繼續學習了！`,
				)
			} else {
				Dialog.find('#finish-chapter__dialog__title').text('成功')
				Dialog.find('#finish-chapter__dialog__message').text(dialogMessage)
			}

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
				const next_chapter_unlocked =
					xhr?.responseJSON?.data?.next_chapter_unlocked ?? undefined

				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isLoading: false,
					showDialog: true,
					dialogMessage: message,
					isFinished: is_this_chapter_finished,
					progress,
					icon_html,
					next_chapter_unlocked,
				}))
			},
		})
	})

	// 線性模式：攔截鎖定章節的點擊事件
	$(document).on('click', 'li[data-locked="true"]', function (e) {
		e.preventDefault()
		e.stopPropagation()

		const chapterId = $(this).data('post-id')
		const chapterTitle =
			$(this).find('span.ml-2').text() || $(this).find('span').first().text()

		Dialog.find('#finish-chapter__dialog__title').text('章節已鎖定')
		Dialog.find('#finish-chapter__dialog__message').text(
			`「${chapterTitle}」尚未解鎖，請先完成前一個章節。`,
		)
		// @ts-ignore
		Dialog?.[0]?.showModal()
	})
}
