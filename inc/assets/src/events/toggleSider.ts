import $ from 'jquery'
import { store, windowAtom } from '../store'



export const toggleSider = () => {
	const siderWidth = $('#pc-classroom-sider').outerWidth() || 400

	store.sub(windowAtom, () => {
		const { isMobile, windowWidth, isSiderExpended: isExpend } = store.get(windowAtom)
		console.log({ isMobile, windowWidth, isExpend })

		$('#pc-classroom-sider').animate({
			left: isExpend ? '0px' : `-${siderWidth}px`,
		})

		$('#pc-classroom-sider__main').animate({
			opacity: isExpend ? 1 : 0
		})

		// icon 翻轉
		$('#pc-classroom-sider__toggle svg').toggleClass('flip-horizontal')

		// 內容寬度
		$('#pc-classroom-main').animate({
			padding: isExpend && !isMobile ? `0 0 0 ${siderWidth}px` : '0',
		})

		$('#pc-classroom-header').animate({
			left: isExpend && !isMobile ? `${siderWidth}px` : '0',
			width: isExpend && !isMobile ? `${windowWidth - siderWidth}px` : windowWidth
		})


	})

	$('body').on('click', '#pc-classroom-sider__toggle', (e) => {
		e.preventDefault()
		e.stopPropagation()
		store.set(windowAtom, (prev) => ({
			...prev,
			isSiderExpended: !prev.isSiderExpended,
		}))
	})

	// $('body').on('click', (e) => {
	// 	e.preventDefault()
	// 	e.stopPropagation()
	// 	store.set(windowAtom, (prev) => ({
	// 		...prev,
	// 		isSiderExpended: !prev.isSiderExpended,
	// 	}))
	// })


}