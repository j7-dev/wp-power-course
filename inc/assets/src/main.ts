import jQuery from 'jquery'
import '@/assets/scss/index.scss'
import { finishChapter, dynamicWidth } from './events'
	; (function ($) {

		dynamicWidth()
		// events
		finishChapter()
	})(jQuery)
