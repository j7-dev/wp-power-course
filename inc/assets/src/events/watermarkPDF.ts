import $ from 'jquery'
import { site_url } from '../utils'

// PDF 浮水印下載
export const watermarkPDF = () => {
	const watermark = 'PCClassroom 測試測試\n 段行測試'

	// 1. 確保是在 classroom 的 body 內
	const $container = $('#pc-classroom-body')
	if (!$container.length) {
		return
	}

	// 2. 找到所有 PDF 下載連結
	const $pdfDownloadLinks = $('#pc-classroom-body a[href*=".pdf"]')
	$pdfDownloadLinks.each(async function (index, el) {
		const $link = $(el)
		const href = $link.attr('href')

		// 將所有連結 轉為 data-href
		$link
			.removeAttr('href')
			.removeAttr('target')
			.attr('data-href', href)
			.attr('data-completed', 'false')

		// 押浮水印
		const pdfName = href.split('/').pop() || 'watermarked.pdf'

		try {
			// 動態載入需要的套件
			const [{ PDFDocument, rgb, degrees }, { default: fontkit }] =
				await Promise.all([
					import('pdf-lib'),
					import('@pdf-lib/fontkit'),
				])

			// 取得原始 PDF
			const response = await fetch(href)
			if (!response.ok) {
				throw new Error('無法讀取 PDF 檔案')
			}
			const pdfBuffer = await response.arrayBuffer()

			// 載入 PDF 文件
			const pdfDoc = await PDFDocument.load(pdfBuffer)

			// 註冊 fontkit
			pdfDoc.registerFontkit(fontkit)

			// 載入中文字型
			const fontResponse = await fetch(
				`${site_url}/wp-content/plugins/power-course/inc/assets/src/assets/fonts/NotoSansTC-Regular.ttf`,
			)
			if (!fontResponse.ok) {
				throw new Error('無法載入字型檔案')
			}
			const fontBytes = await fontResponse.arrayBuffer()
			const font = await pdfDoc.embedFont(fontBytes)

			// 取得所有頁面
			const pages = pdfDoc.getPages()

			// 設定浮水印樣式
			const fontSize = 18
			const opacity = 0.3
			const qty = 1 // 每頁幾個浮水印

			// 處理每一頁
			for (const page of pages) {
				const { width, height } = page.getSize()

				// 在每個頁面添加多個浮水印
				for (let i = 0; i < qty; i++) {
					// 隨機位置
					const x = Math.random() * (width - width / 4)
					const y = Math.random() * height

					// 隨機角度（-15 到 15 度之間）
					// const rotation = -15 + Math.random() * 30
					const rotation = 15

					page.drawText(watermark, {
						x,
						y,
						size: fontSize,
						font,
						color: rgb(1, 0, 0),
						opacity,
						rotate: degrees(rotation),
					})
				}
			}

			// 儲存帶浮水印的 PDF
			const pdfBytes = await pdfDoc.save()

			// 建立下載
			const blob = new Blob([pdfBytes], {
				type: 'application/pdf',
			})
			const url = URL.createObjectURL(blob)

			// 使用原本的連結來下載
			const downloadBtn = $link.get(0)
			downloadBtn.href = url
			downloadBtn.download = pdfName

			// 標示為處理完成
			$link.attr('data-completed', 'true')

			// 清理 URL
			setTimeout(() => {
				URL.revokeObjectURL(url)
			}, 100)
		} catch (error) {
			console.error('處理 PDF 時發生錯誤:', error)

			// 發生錯誤時下載原檔案
			$link.attr('href', href)
		}
	})

	$pdfDownloadLinks.on('click', function (e) {
		const $link = $(this)
		const completed = $link.data('completed')
		if (!completed) {
			// 檢查此 PDF 是否已經處理完了
			e.stopPropagation()
			e.preventDefault()
			alert('此 PDF 還在處理中，請稍後幾秒鐘後再試')
		}
	})
}
