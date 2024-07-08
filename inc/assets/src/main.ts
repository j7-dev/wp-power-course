import jQuery from 'jquery'
import '@/assets/scss/index.scss'
import { finishChapter, dynamicWidth, toggleSider } from './events'
	; (function ($) {

		// events
		toggleSider()
		finishChapter()

		dynamicWidth()
	})(jQuery)
