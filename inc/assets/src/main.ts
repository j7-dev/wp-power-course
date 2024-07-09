import jQuery from 'jquery'
import '@/assets/scss/index.scss'
import { finishChapter, dynamicWidth, responsive, tabs, coursesProduct } from './events'
	; (function ($) {
		// 訂閱放前面
		responsive()

		// classroom 頁面，完成章節
		finishChapter()

		// 改變大小時設定 state
		dynamicWidth()

		// 添加 tabs 組件事件
		tabs()
		coursesProduct()


	})(jQuery)
