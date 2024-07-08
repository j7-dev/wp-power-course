import $ from 'jquery'
import { store, windowAtom } from '../store'
import { debounce } from 'lodash-es'

const adjustWidth = () => {
	store.set(windowAtom, (prev) => ({
		...prev,
		windowWidth: window.innerWidth,
		isMobile: window.innerWidth < 1080,
	}))
}


export const dynamicWidth = () => {
	store.set(windowAtom, () => ({
		windowWidth: window.innerWidth,
		isMobile: window.innerWidth < 1080,
		isSiderExpended: true,
	}))

	$(window).on('resize', debounce(() => adjustWidth(), 300))
}