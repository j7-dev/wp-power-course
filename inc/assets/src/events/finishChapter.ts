/* eslint-disable @typescript-eslint/ban-ts-comment */
import $ from 'jquery'
import { store, finishChapterAtom } from '../store'

const CheckIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none">
<path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="#1677ff"></path>
</svg>`

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
    } = store.get(finishChapterAtom)
    if (isLoading) {
      FinishButton.find('.pc-loading-spinner').removeClass('tw-hidden')
    } else {
      FinishButton.find('.pc-loading-spinner').addClass('tw-hidden')
    }

    if (isSuccess) {
      Dialog.find('#finish-chapter__dialog__title').text('成功')
      Dialog.find('#finish-chapter__dialog__message').text(dialogMessage)

      // FinishButton.hide()
      if (isFinished === true) {
        FinishButton.addClass('pc-btn-outline border-solid')
          .find('span:first-child')
          .text('標示為未完成')

        $('#classroom-chapter_title-badge')
          .removeClass('pc-badge-accent')
          .addClass('pc-badge-secondary')
          .text('已完成')
      }

      if (isFinished === false) {
        FinishButton.removeClass('pc-btn-outline border-solid')
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

      $(`#classroom__sider-collapse__chapter-${chapter_id}`).html(CheckIcon)
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

    const site_url = window.location.origin

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

        store.set(finishChapterAtom, (prev) => ({
          ...prev,
          isLoading: false,
          showDialog: true,
          dialogMessage: message,
          isFinished: is_this_chapter_finished,
          progress,
        }))
      },
    })
  })
}
