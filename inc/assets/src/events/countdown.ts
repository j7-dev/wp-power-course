import $ from 'jquery'

// 處理 TAB 組件的切換事件
export const countdown = () => {
  const Countdown = $('.pc-countdown-component')

  // each countdown component

  Countdown.each((index, element) => {
    const t = Number($(element).data('timestamp'))

    const timer = setInterval(() => {
      //current timestamp
      const now = Math.floor(Date.now() / 1000)
      const rest = t - now
      const rest_in_sec = rest % 60
      const rest_in_min = Math.floor(rest / 60) % 60
      const rest_in_hour = Math.floor(rest / 60 / 60) % 24
      const rest_in_day = Math.floor(rest / 60 / 60 / 24)

      $(element)
        .find('.pc-countdown-component__day')
        .attr('style', `--value:${rest_in_day};`)
      $(element)
        .find('.pc-countdown-component__hour')
        .attr('style', `--value:${rest_in_hour};`)
      $(element)
        .find('.pc-countdown-component__min')
        .attr('style', `--value:${rest_in_min};`)
      $(element)
        .find('.pc-countdown-component__sec')
        .attr('style', `--value:${rest_in_sec};`)

      // if rest < 0, reload the page
      if (rest < 0) {
        clearInterval(timer)
        $(element)
          .find('.pc-countdown-component__day')
          .attr('style', '--value:0;')
        $(element)
          .find('.pc-countdown-component__hour')
          .attr('style', '--value:0;')
        $(element)
          .find('.pc-countdown-component__min')
          .attr('style', '--value:0;')
        $(element)
          .find('.pc-countdown-component__sec')
          .attr('style', '--value:0;')

        location.reload()
      }
    }, 1000)
  })
}
