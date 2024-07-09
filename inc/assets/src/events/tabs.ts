import $ from 'jquery'

// 處理 TAB 組件的切換事件
export const tabs = () => {
	$('div[id^="tab-nav-"]').on('click', function () {
		$(this).addClass('active').siblings().removeClass('active')
		$('#tab-content-' + $(this).attr('id').split('-')[2]).addClass('active').siblings().removeClass('active')
	})
}