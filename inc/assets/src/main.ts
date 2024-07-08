import jQuery from 'jquery'
import '@/assets/scss/index.scss'
import { finishChapter, dynamicWidth, responsive } from './events'
	; (function ($) {
		responsive()
		// events
		finishChapter()

		dynamicWidth()
	})(jQuery)
