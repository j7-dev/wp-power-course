import $ from 'jquery'
import { isMobile, getDistanceFromViewportTop, header_offset } from '../utils'

// 處理 TAB 組件的切換事件
export const tabs = () => {

	// 偏移 TAB 置頂
	$('div[id^="tab-nav-"]').on('click', function () {
		$(this).addClass('active').siblings().removeClass('active')
		$('#tab-content-' + $(this).attr('id').split('-')[2])
			.addClass('active')
			.siblings()
			.removeClass('active')

		// 切換 TAB 時滾到目標節點的位置
		const _isMobile = isMobile()
		const headerH = $('#pc-classroom-header').height() || 0
		const videoH = _isMobile
			? $(
				'.pc-classroom-body__video, #courses-product__feature-video',
			).height() || 0
			: 0

		const navH =
			$('.pc-classroom-body__tabs-nav, #courses-product__tabs-nav').height() ||
			0
		const isNavFixed =
			$('#courses-product__tabs-nav').css('position') === 'fixed'

		// const offsetTop = _isMobile
		// 	? videoHeight + 52 // 52 是手機 header 的高度
		// 	: 64 // 64 是 header 的高度

		const offsetTop = headerH + videoH + navH + header_offset
		const scrollTop =
			$('div.active[id^="tab-content-"]').parent('div').offset().top -
			offsetTop +
			(isNavFixed ? 24 : -12) // 24 是 nav 的 margin bottom

		$('html, body').animate(
			{
				scrollTop,
			},
			500,
		)
	})

	/**
	 * 設定最小高度是因為點了 TAB 會有偏移
	 * TAB 會置頂
	 * 如果高度不夠  會偏移到中間，才設定此 min height
	 *
	$('div[id^="tab-content-"]').each(function () {
		const _isMobile = isMobile()
		const videoH = $('.pc-classroom-body__video').height() || 0
		const offsetTop = _isMobile
			? videoH + 52 // 52 是手機 header 的高度
			: 64 // 64 是 header 的高度
		// 48 是 內容 margin botton 的數值
		// 43 是 tabs nav 的高度
		// 64 是 header 的高度
		$(this).css({
			minHeight: `${window.innerHeight - 48 - 43 - offsetTop}px`,
		})
	})
	*/
}
