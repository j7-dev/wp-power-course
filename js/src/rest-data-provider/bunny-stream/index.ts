/**
 * TODO Bunny 用
 * 還沒有調整過
 *
 */

import { DataProvider } from '@refinedev/core'
import { generateSort, generateFilter } from '../utils'
import axios, { AxiosInstance, AxiosResponse } from 'axios'
import queryString from 'query-string'
import { THttpMethods, THttpMethodsWithBody } from '@/types'
import { bunny_stream_api_key } from '@/utils'
import { TGetVideosResponse } from '@/pages/admin/MediaLibrary/types'

const { stringify } = queryString

export const bunnyStreamAxios = axios.create({
	baseURL: 'https://video.bunnycdn.com/library',
	headers: {
		AccessKey: bunny_stream_api_key,
	},
})

export const dataProvider = (
	apiUrl: string,
	httpClient: AxiosInstance = bunnyStreamAxios,
): Omit<
	Required<DataProvider>,
	'createMany' | 'updateMany' | 'deleteMany'
> => ({
	getList: async ({ resource, pagination, filters, sorters, meta }) => {
		// TODO 未來要做 bunny 影片管理再整理

		const url = `${apiUrl}/${resource}`

		const { current = 1, pageSize = 10, mode = 'server' } = pagination ?? {}

		const { headers: headersFromMeta, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'

		const queryFilters = generateFilter(filters)
		console.log('⭐  queryFilters:', queryFilters)

		const query: {
			page?: number
			itemsPerPage?: number
			search?: string | null
			collection?: string | null
			orderBy?: string | null //Defaults to date
		} = {}

		if (mode === 'server') {
			query.page = current
			query.itemsPerPage = pageSize
		}

		// const generatedSort = generateSort(sorters)
		// if (generatedSort) {
		// 	const { _sort, _order } = generatedSort
		// 	query.orderby = _sort.join(',')
		// 	query.order = _order.join(',')
		// }

		const { data } = (await httpClient[requestMethod](
			`${url}?${stringify(query)}&${stringify(queryFilters, { arrayFormat: 'bracket' })}`,
			{
				headers: headersFromMeta,
			},
		)) as AxiosResponse<TGetVideosResponse>

		const total = data?.totalItems || data?.items?.length || 0
		const items = data?.items || []

		return {
			data: items,
			total,
		}
	},

	getMany: async ({ resource, ids, meta }) => {
		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'

		const { data } = await httpClient[requestMethod](
			`${apiUrl}/${resource}?${stringify({ id: ids })}`,
			{ headers },
		)

		return {
			data,
		}
	},

	create: async ({ resource, variables, meta }) => {
		const url = `${apiUrl}/${resource}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'post'

		const { data } = await httpClient[requestMethod](url, variables, {
			headers,
		})

		return {
			data,
		}
	},

	update: async ({ resource, id, variables, meta }) => {
		const url = `${apiUrl}/${resource}/${id}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'post'

		const { data } = await httpClient[requestMethod](url, variables, {
			headers,
		})

		return {
			data,
		}
	},

	getOne: async ({ resource, id, meta }) => {
		const url = `${apiUrl}/${resource}/${id}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'

		const { data } = await httpClient[requestMethod](url, { headers })

		return {
			data,
		}
	},

	deleteOne: async ({ resource, id, variables, meta }) => {
		const url = `${apiUrl}/${resource}/${id}`
		const { headers, method } = meta ?? {}
		const requestMethod = (method ?? 'delete') as THttpMethodsWithBody

		const result = await httpClient?.[requestMethod](url, {
			data: variables,
			headers,
		})

		console.log('⭐  result:', result)

		return {
			data: result.data,
		}
	},

	getApiUrl: () => {
		return apiUrl
	},

	custom: async ({
		url,
		method,
		filters,
		sorters,
		payload,
		query,
		headers,
	}) => {
		let requestUrl = `${url}?`

		if (sorters) {
			const generatedSort = generateSort(sorters)
			if (generatedSort) {
				const { _sort, _order } = generatedSort
				const sortQuery = {
					orderby: _sort.join(','),
					order: _order.join(','),
				}
				requestUrl = `${requestUrl}&${stringify(sortQuery)}`
			}
		}

		if (filters) {
			const filterQuery = generateFilter(filters)
			requestUrl = `${requestUrl}&${stringify(filterQuery)}`
		}

		if (query) {
			requestUrl = `${requestUrl}&${stringify(query)}`
		}

		if (headers) {
			httpClient.defaults.headers = {
				...httpClient.defaults.headers,
				...headers,
			}
		}

		let axiosResponse
		switch (method) {
			case 'put':
			case 'post':
			case 'patch':
				axiosResponse = await httpClient[method](url, payload)
				break
			case 'delete':
				axiosResponse = await httpClient.delete(url, {
					data: payload,
				})
				break
			default:
				axiosResponse = await httpClient.get(requestUrl)
				break
		}

		const { data } = axiosResponse

		return Promise.resolve({ data })
	},
})
