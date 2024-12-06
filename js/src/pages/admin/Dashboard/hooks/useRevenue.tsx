import React, { useState } from 'react'
import {
	useCustom,
	useApiUrl,
	CustomResponse,
	HttpError,
	UseLoadingOvertimeReturnType,
	CrudFilter,
} from '@refinedev/core'
import { QueryObserverResult } from '@tanstack/react-query'
import dayjs from 'dayjs'
import { TRevenue, TFormattedRevenue, EViewType } from '../types'
import { Form } from 'antd'

const defaultQuery = {
	order: 'asc',
	interval: 'day',
	per_page: 100,
	after: dayjs().add(-7, 'd').startOf('day').format('YYYY-MM-DDTHH:mm:ss'),
	before: dayjs().endOf('day').format('YYYY-MM-DDTHH:mm:ss'),
	_locale: 'user',
	page: 1,
}

export type TQuery = typeof defaultQuery

const useRevenue = () => {
	const apiUrl = useApiUrl()
	const [query, setQuery] = useState(defaultQuery)
	const [viewType, setViewType] = useState(EViewType.DEFAULT)

	const result = useCustom<TRevenue>({
		url: `${apiUrl}/reports/revenue/stats`,
		method: 'get',
		config: {
			filters: getFormattedFilter(query),
		},
	})

	// 格式化新的 result
	const formattedResult = getFormattedResult(result)

	// 取得 response header 上的 X-WP-TotalPages
	const totalPages = Number(result?.data?.headers?.['x-wp-totalpages']) || 1
	const total = Number(result?.data?.headers?.['x-wp-total']) || 1

	const [form] = Form.useForm()

	return {
		result: formattedResult,
		filterProps: {
			isFetching: result.isFetching,
			isLoading: result.isLoading,
			setQuery,
			query,
			totalPages,
			total,
			form,
			viewType,
			setViewType,
		},
		viewTypeProps: {
			revenueData: formattedResult?.data?.data,
			form,
		},
	}
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
	} as QueryObserverResult<CustomResponse<TFormattedRevenue>, HttpError> &
		UseLoadingOvertimeReturnType

	return formatResult
}

function getFormattedFilter(query: TQuery) {
	const filters = Object.keys(query).map((key) => ({
		field: key,
		operator: 'eq',
		value: query[key as keyof typeof query],
	})) as CrudFilter[]
	return filters
}

export default useRevenue
