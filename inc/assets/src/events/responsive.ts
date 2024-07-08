import $ from 'jquery'
import { store, windowAtom } from '../store'

export const responsive = () => {
	const siderWidth = $('#pc-classroom-sider').outerWidth() || 400

	store.sub(windowAtom, () => {
		const { isMobile, windowWidth } = store.get(windowAtom)

		// 內容寬度
		$('#pc-classroom-main').animate({
			padding: isMobile ? '0' : `0 0 0 ${siderWidth}px`,
		})

		$('#pc-classroom-header').animate({
			left: isMobile ? '0' : `${siderWidth}px`,
			width: isMobile ? windowWidth : `${windowWidth - siderWidth}px`
		})
	})

}