import $ from 'jquery'
import { store, windowWidthAtom } from '../store'
import { SCREEN } from '../utils'

// 手機板 classroom 的 TAB 要切換
const showChapterInMobile = (isMobile = true) => {
	if (!$('#pc-classroom-body div[id^="tab-"]').length) {
		return
	}
	$('#pc-classroom-body div[id^="tab-"]').removeClass('active')
	if (isMobile) {
		$(
			'#pc-classroom-body #tab-nav-chapter, #pc-classroom-body #tab-content-chapter',
		).addClass('active')
	} else {
		$(
			'#pc-classroom-body #tab-nav-chapter, #pc-classroom-body #tab-nav-discuss',
		).addClass('active')
	}
}

// classroom 的 sider 移動到當前章節
const scrollToChapter = () => {
	if (!$('#pc-classroom-sider__main').length) {
		return
	}
	const url = new URL(window.location.href)
	const chapter_id = url.pathname.split('/')?.[3]
	const target = $(
		`#pc-classroom-sider__main [data-chapter_id="${chapter_id}"]`,
	)
	if (!target.length) {
		console.log('chapter target not found')
		return
	}
	const targetOffset = target.offset().top
	const parentDiv = target.closest('.pc-sider-chapters')

	// 將父級 div 滾動到目標節點的位置
	parentDiv.animate(
		{
			scrollTop: targetOffset - parentDiv.offset().top,
		},
		500,
	)
}

const stickyTabsNav = (isMobile = true) => {
	if (!$('.pc-classroom-body__tabs-nav').length) {
		return
	}
	const videoHeight = $('.pc-classroom-body__video').height() || 0
	$('.pc-classroom-body__tabs-nav').css({
		position: isMobile ? 'sticky' : 'relative',
		top: isMobile ? `${videoHeight + 52}px` : 'unset',
	})
}

// 調整 classroom 畫面
export const responsive = () => {
	const siderWidth = $('#pc-classroom-sider').outerWidth() || 400

	showChapterInMobile(window.outerWidth < SCREEN.LG)

	store.sub(windowWidthAtom, () => {
		const windowWidth = store.get(windowWidthAtom)

		// 內容寬度
		$('#pc-classroom-main').animate({
			padding: windowWidth < SCREEN.LG ? '0' : `0 0 0 ${siderWidth}px`,
		})

		$('#pc-classroom-header').animate({
			left: windowWidth < SCREEN.LG ? '0' : `${siderWidth}px`,
			width:
				windowWidth < SCREEN.LG ? windowWidth : `${windowWidth - siderWidth}px`,
		})

		showChapterInMobile(windowWidth < SCREEN.LG)
		stickyTabsNav(window.outerWidth < SCREEN.LG)
	})

	scrollToChapter()
	stickyTabsNav(window.outerWidth < SCREEN.LG)
}
