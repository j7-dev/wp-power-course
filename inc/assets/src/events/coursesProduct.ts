import $ from 'jquery'
import { SCREEN } from '../utils'
import { throttle } from 'lodash-es'

// 判斷元素距離可視區域頂部的距離
function getDistanceFromViewportTop($element) {
  const elementTop = $element?.offset()?.top || 0
  const scrollTop = $(window).scrollTop()
  return elementTop - scrollTop
}

// 處理 courses product 銷售業手機板的事件
export const coursesProduct = () => {
  const video = $('#courses-product__feature-video')
  const tabsNav = $('#courses-product__tabs-nav')
  const videoH = video.outerHeight()
  const tabsOffset = tabsNav?.[0]?.offsetTop || 0 // 獲取 tabsNav 元素的初始頂部位置
  const videoOffset = video?.offset()?.top || 0 // 獲取 video 元素的初始頂部位置

  $(window).scroll(
    throttle(() => {
      if (window.innerWidth > SCREEN.MD) return
      const scrollTop = $(window).scrollTop() // 獲取當前滾動位置
      if (scrollTop > videoOffset) {
        // 如果滾動位置超過 video 頂部
        video.css({
          position: 'fixed',
          top: '0',
          left: '0',
        })
      } else {
        // 如果滾動位置還沒超過 video 頂部
        video.css({
          position: 'relative',
          top: 'unset',
          left: 'unset',
        })
      }

      // 要扣掉 video 2倍的高度，因為1個是原本 video 佔住的空間，另一個是 fixed 之後的空間
      if (scrollTop > tabsOffset - videoH * 2 - 48) {
        // 如果滾動位置超過 tabsNav 頂部
        tabsNav.css({
          "position": 'fixed',
          "top": `${videoH}px`,
          "left": '0',
          "padding": '0 1rem',
          'background-color': '#fff',
        })
      } else {
        // 如果滾動位置還沒超過 tabsNav 頂部
        tabsNav.css({
          "position": 'relative',
          "top": 'unset',
          "left": 'unset',
          "padding": 'unset',
          'background-color': 'unset',
        })
      }
    }, 200),
  )
}
