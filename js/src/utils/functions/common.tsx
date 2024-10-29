import React from 'react'
import { toFormData as axiosToFormData, GenericFormData } from 'axios'
import { clsx, ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

// DELETE
export const cn = (...args: ClassValue[]) => twMerge(clsx(args))

// DELETE
export const getIsVariation = (type: string) => {
	return ['variation', 'subscription_variation'].includes(type)
}

//DELETE
export function removeTrailingSlash(str: string) {
	if (str.endsWith('/')) {
		// 如果字符串以斜杠结尾，使用 slice 方法去除最后一个字符

		return str.slice(0, -1)
	}

	// 否则，返回原字符串

	return str
}

export const getEstimateUploadTimeInSeconds = (fileSize: number) => {
	// 將文件大小轉換為 bits（1 byte = 8 bits）

	const fileSizeInBits = fileSize * 8

	// 上傳速度（30 Mbps = 30,000,000 bits/second）
	const uploadSpeed = 30 * 1000 * 1000 // bits per second

	// 計算預期上傳時間（秒）

	const estimatedTimeInSeconds = fileSizeInBits / uploadSpeed

	// 返回秒數，保留兩位小數

	return Number(estimatedTimeInSeconds.toFixed(2))
}

export const getVideoUrl = (file: File) => {
	return URL.createObjectURL(file)
}

/**
 * DELETE
 * 因為 原本 axios 的 toFormData 會把空陣列轉為過濾掉，這樣後端收不到資料
 * 我希望還是能傳 '[]'給後端處理
 * @param  data
 * @return {GenericFormData}
 */
export const toFormData = (data: object): GenericFormData => {
	const formattedData = Object.entries(data).reduce(
		(acc, [key, value]) => {
			if (Array.isArray(value) && value.length === 0) {
				acc[key] = '[]'
				return acc
			}
			if (value === null || value === undefined) {
				acc[key] = ''
				return acc
			}
			acc[key] = value

			return acc
		},
		{} as {
			[key: string]: any
		},
	)

	const formData = axiosToFormData(formattedData)

	return formData
}

/**
 * 從 Youtube 的 URL 中取得影片 ID
 *
 * @param {string} url
 * @return {string | null} 影片 ID
 */
export const getYoutubeVideoId = (url: string | null): string | null => {
	if (!url) return ''
	try {
		const urlObj = new URL(url)
		if (urlObj.hostname === 'youtu.be') {
			return urlObj.pathname.slice(1)
		}
		const searchParams = new URLSearchParams(urlObj.search)
		return searchParams.get('v')
	} catch (error) {
		console.error('無效的 YouTube URL:', error)
		return null
	}
}

/**
 * 從 vimeo 的 URL 中取得影片 ID
 *
 * @param {string} url // ex: https://vimeo.com/900151069
 * @return {string | null} 影片 ID
 */
export const getVimeoVideoId = (url: string | null): string | null => {
	if (!url) return ''
	try {
		const regex = /(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(\d+)/
		const match = url.match(regex)
		return match ? match[1] : null
	} catch (error) {
		console.error('无效的 Vimeo URL:', error)
		return null
	}
}
