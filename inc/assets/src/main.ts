import jQuery from 'jquery'
import '@/assets/scss/index.scss'

;(function ($) {
  // 調整 classroom sider 的高度
  const headerHeight = $('header')?.outerHeight() || 0
  const sider = $('#pc-classroom-sider')
  sider?.css({
    top: `${headerHeight}px`,
    height: `calc(100% - ${headerHeight}px)`,
  })

  // 調整 classroom content 的高度
  const siderWidth = sider?.outerWidth() || 0
  $('#pc-classroom-main').css({
    padding: `${headerHeight}px 0 0 ${siderWidth}px`,
  })
})(jQuery)
