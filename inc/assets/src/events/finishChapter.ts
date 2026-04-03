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
			next_chapter_id,
			next_chapter_permalink,
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
				const isLinear = (window as any).pc_data?.linear_chapter_mode === 'yes'

				if (isLinear) {
					// 線性觀看模式：已完成章節按鈕變為不可點擊的「已完成」狀態
					FinishButton.prop('disabled', true)
						.addClass('cursor-not-allowed opacity-70 pc-btn-outline border-solid')
						.removeClass('text-white')
						.find('span:first-child')
						.text('已完成')
					FinishButton.find('.pc-loading-spinner').addClass('tw-hidden')

					// 解鎖下一章側邊欄圖示
					if (next_chapter_id) {
						const $nextLi = $(`li[data-post-id="${next_chapter_id}"]`)
						if ($nextLi.length > 0) {
							$nextLi.removeAttr('data-locked')
								.removeClass('opacity-50 cursor-not-allowed')
								.attr('data-href', next_chapter_permalink ?? '')

							// 替換鎖頭圖示為正常播放圖示（video icon）
							const $nextIcon = $nextLi.find('.pc-chapter-icon')
							if ($nextIcon.length > 0) {
								$nextIcon.html('<div class="pc-tooltip pc-tooltip-right h-6" data-tip="點擊觀看"><svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#6419e6" opacity="0.3"/><path d="M15.4137 13.059L10.6935 15.8458C9.93371 16.2944 9 15.7105 9 14.7868V9.21316C9 8.28947 9.93371 7.70561 10.6935 8.15419L15.4137 10.941C16.1954 11.4026 16.1954 12.5974 15.4137 13.059Z" fill="#fff"/></svg></div>')
							}
						}
					}

					// 啟用「前往下一章節」按鈕（將禁用的 button 替換為可點擊的 a）
					const $nextBtn = $('#next-chapter__button')
					if ($nextBtn.length > 0 && next_chapter_permalink) {
						const $newLink = $(`<a id="next-chapter__button" href="${next_chapter_permalink}" class="pc-btn pc-btn-primary pc-btn-sm px-0 lg:px-4 text-white w-full lg:w-auto text-xs sm:text-base">前往下一章節 <svg class="size-3 sm:size-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g><path fill-rule="evenodd" clip-rule="evenodd" d="M5.60439 4.23093C4.94586 3.73136 4 4.20105 4 5.02762V18.9724C4 19.799 4.94586 20.2686 5.60439 19.7691L14.7952 12.7967C15.3227 12.3965 15.3227 11.6035 14.7952 11.2033L5.60439 4.23093ZM2 5.02762C2 2.54789 4.83758 1.13883 6.81316 2.63755L16.004 9.60993C17.5865 10.8104 17.5865 13.1896 16.004 14.3901L6.81316 21.3625C4.83758 22.8612 2 21.4521 2 18.9724V5.02762Z" fill="#ffffff"></path><path d="M20 3C20 2.44772 20.4477 2 21 2C21.5523 2 22 2.44772 22 3V21C22 21.5523 21.5523 22 21 22C20.4477 22 20 21.5523 20 21V3Z" fill="#ffffff"></path></g></svg></a>`)
						$nextBtn.replaceWith($newLink)
					}
				} else {
					// 非線性模式：原有邏輯
					FinishButton.removeClass('text-white').addClass('pc-btn-outline border-solid')
						.find('span:first-child')
						.text('標示為未完成')
				}

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
		// 線性觀看模式下，已完成的按鈕不可點擊（disabled 屬性應已攔截，此為防禦性檢查）
		if ($(this).prop('disabled')) {
			return
		}

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
				// 線性觀看模式：下一章節資訊
				const next_chapter_id = xhr?.responseJSON?.data?.next_chapter_id
				const next_chapter_unlocked =
					xhr?.responseJSON?.data?.next_chapter_unlocked
				const next_chapter_permalink =
					xhr?.responseJSON?.data?.next_chapter_permalink

				store.set(finishChapterAtom, (prev) => ({
					...prev,
					isLoading: false,
					showDialog: true,
					dialogMessage: message,
					isFinished: is_this_chapter_finished,
					progress,
					icon_html,
					next_chapter_id,
					next_chapter_unlocked,
					next_chapter_permalink,
				}))
			},
		})
	})
}
