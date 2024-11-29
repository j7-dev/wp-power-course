import React from 'react'
import {
	useCustom,
	useApiUrl,
	CustomResponse,
	HttpError,
	UseLoadingOvertimeReturnType,
} from '@refinedev/core'
import { QueryObserverResult } from '@tanstack/react-query'
import { TRevenue, TFormattedRevenue } from '../types'

const query = {
	order: 'asc',
	interval: 'day',
	per_page: 100,
	after: '2024-10-01T00:00:00',
	before: '2024-11-29T23:59:59',
	_locale: 'user',
	page: 1,
}

const useRevenue = () => {
	const apiUrl = useApiUrl()

	const result = useCustom<TRevenue>({
		url: `${apiUrl}/reports/revenue/stats`,
		method: 'get',
		config: {
			query,
		},
	})
	console.log('⭐  result:', result)

	// 格式化新的 result
	const formattedResult = getFormattedResult(result)

	// 取得 response header 上的 X-WP-TotalPages
	const totalPages = Number(result?.data?.headers?.['x-wp-totalpages']) || 1
	const total = Number(result?.data?.headers?.['x-wp-total']) || 1

	return formattedResult
}

/**
 * 格式化 result
 * @param result
 * @return
 */
function getFormattedResult(
	result: QueryObserverResult<CustomResponse<TRevenue>, HttpError> &
		UseLoadingOvertimeReturnType,
): QueryObserverResult<CustomResponse<TFormattedRevenue>, HttpError> &
	UseLoadingOvertimeReturnType {
	const intervals = result?.data?.data?.intervals || []
	const formatIntervals = intervals.map(({ subtotals, ...restInterval }) => {
		// 建立新物件而不是修改原物件
		const newInterval = {
			...restInterval,
			...subtotals,
		}
		return newInterval
	})

	// 創建新的 result
	const formatResult = {
		...result,
		data: {
			...result?.data,
			data: {
				...result?.data?.data,
				intervals: formatIntervals,
			},
		},
	}

	return formatResult
}

export default useRevenue
