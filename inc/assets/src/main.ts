import jQuery from 'jquery'
import '@/assets/scss/index.scss'

	; import { log } from 'console';
(function ($) {
	const pc_data = window?.['pc_data'] || {}
	const dialog = $('#finish-chapter__dialog')

	// 調整 classroom sider 的高度
	const headerHeight = $('header')?.outerHeight() || 0
	const sider = $('#pc-classroom-sider')
	sider?.css({
		top: `${headerHeight}px`,
		height: `calc(100% - ${headerHeight}px)`,
	})

	// 調整 classroom content 的高度
	const siderWidth = sider?.outerWidth() || 0
	$('#pc-classroom-main').css({
		padding: `0 0 0 ${siderWidth}px`,
	})

	// 完成章節事件
	$('#finish-chapter__button').on('click', function (e) {
		const chapter_id = $(this).data('chapter-id')
		const course_id = $(this).data('course-id')
		$.ajax({
			url: pc_data?.ajax_url,
			type: 'post',
			data: {
				action: 'finish_chapter',
				security: pc_data?.nonce,
				data: {
					course_id,
					chapter_id,
				}
			},
			success: function (response) {
				const { success = false, data } = response
				const message = data?.message || ''
				dialog.find('#finish-chapter__dialog__message').text(message)
				if (success) {
					dialog.find('#finish-chapter__dialog__title').text('成功')
				} else {
					dialog.find('#finish-chapter__dialog__title').text('發生錯誤，請稍後再試')
				}
				dialog[0].showModal()
				$('#finish-chapter__button').hide()
			},
			error: function (error) {
				console.log('error', error)
			}
		});
	})

})(jQuery)
