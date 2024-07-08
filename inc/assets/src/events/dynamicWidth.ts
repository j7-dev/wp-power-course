import $ from 'jquery'

export const dynamicWidth = () => {
	const sider = $('#pc-classroom-sider')

	// 調整 classroom content 的高度
	const siderWidth = sider?.outerWidth() || 0
	const windowWidth = $(window).width() || 0
	$('#pc-classroom-main').css({
		padding: `0 0 0 ${siderWidth}px`,
	})

	$('#pc-classroom-header').css({
		left: `${siderWidth}px`,
		width: `${windowWidth - siderWidth}px`
	})
}