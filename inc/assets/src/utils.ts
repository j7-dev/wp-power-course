import $ from 'jquery'

// eslint-disable-next-line no-shadow
export enum SCREEN {
	SM = 576,
	MD = 810,
	LG = 1080,
	XL = 1280,
	XXL = 1440,
}

export const site_url = window.location.origin

export const isMobile = (size = 'LG') => {
	return window.innerWidth < SCREEN?.[size]
}

// 判斷元素距離可視區域頂部的距離
export const getDistanceFromViewportTop = ($element) => {
	const elementTop = $element?.offset()?.top || 0
	const scrollTop = $(window).scrollTop()
	return elementTop - scrollTop
}
