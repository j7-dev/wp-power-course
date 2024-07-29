import $ from 'jquery'
import { store, windowWidthAtom } from '../store'
import { SCREEN } from '../utils'

// 調整 classroom 畫面
export const responsive = () => {
	const siderWidth = $('#pc-classroom-sider').outerWidth() || 400

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
	})
}
