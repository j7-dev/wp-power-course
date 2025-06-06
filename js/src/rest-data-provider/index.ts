import { DataProvider } from '@refinedev/core'
import { axiosInstance, generateSort, generateFilter } from './utils'
import { AxiosInstance } from 'axios'
import queryString from 'query-string'
import { TOrderBy, TOrder, THttpMethods, THttpMethodsWithBody } from '@/types'

export * from './utils'

const { stringify } = queryString

export const dataProvider = (
	apiUrl: string,
	httpClient: AxiosInstance = axiosInstance,
): Omit<Required<DataProvider>, 'deleteMany'> => ({
	getList: async ({ resource, pagination, filters, sorters, meta }) => {
		const url = `${apiUrl}/${resource}`

		const { current = 1, pageSize = 10, mode = 'server' } = pagination ?? {}

		const { headers: headersFromMeta, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'

		const queryFilters = generateFilter(filters)

		const query: {
			paged?: number
			posts_per_page?: number

			// orderby?: TOrderBy
			// order?: TOrder
		} = {}

		if (mode === 'server') {
			query.paged = current
			query.posts_per_page = pageSize
		}

		const sortQueryString = generateSort(sorters)

		const { data, headers } = await httpClient[requestMethod](
			`${url}?${stringify(query)}&${stringify(queryFilters, { arrayFormat: 'bracket' })}&${sortQueryString}`,
			{
				headers: headersFromMeta,
			},
		)

		// 取得 response header 上的 X-WP-TotalPages
		const totalPages = Number(headers?.['x-wp-totalpages']) || 1
		const total = Number(headers?.['x-wp-total']) || 1
		const currentPage = Number(headers?.['x-wp-currentpage']) || 1

		return {
			data,
			total,
			totalPages,
			currentPage,
		}
	},

	getMany: async ({ resource, ids, meta }) => {
		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'
		const includeParams = ids.map((id) => `include[]=${id}`).join('&')

		const result = await httpClient[requestMethod](
			`${apiUrl}/${resource}?${includeParams}`,
			{ headers },
		)

		return result
	},

	create: async ({ resource, variables, meta }) => {
		const url = `${apiUrl}/${resource}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'post'

		const { data } = await httpClient[requestMethod](url, variables, {
			headers: {
				'Content-Type': 'multipart/form-data;',
				...headers,
			},
		})

		return {
			data,
		}
	},

	createMany: async ({ resource, variables, meta }) => {
		const url = `${apiUrl}/${resource}`

		const formattedVariables = {
			...variables,
		}

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'post'

		const { data } = await httpClient[requestMethod](url, formattedVariables, {
			headers: {
				'Content-Type': 'multipart/form-data;',
				...headers,
			},
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
			headers: {
				'Content-Type': 'multipart/form-data;',
				...headers,
			},
		})

		return {
			data,
		}
	},

	updateMany: async ({ resource, ids, variables, meta }) => {
		const url = `${apiUrl}/${resource}`
		const formattedVariables = {
			...variables,
			ids,
			action: 'update',
		}

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'post'

		const result = await httpClient[requestMethod](url, formattedVariables, {
			headers: {
				'Content-Type': 'multipart/form-data;',
				...headers,
			},
		})

		return result
	},

	getOne: async ({ resource, id, meta }) => {
		const url = `${apiUrl}/${resource}/${id}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethods) ?? 'get'

		const result = await httpClient[requestMethod](url, { headers })

		return result
	},

	deleteOne: async ({ resource, id, variables, meta }) => {
		const url = `${apiUrl}/${resource}/${id}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'delete'

		const { data } = await httpClient[requestMethod](url, {
			data: variables,
			headers: {
				'Content-Type': 'multipart/form-data;',
				...headers,
			},
		})

		return {
			data,
		}
	},

	deleteMany: async ({ resource, ids, variables, meta }) => {
		const url = `${apiUrl}/${resource}`

		const { headers, method } = meta ?? {}
		const requestMethod = (method as THttpMethodsWithBody) ?? 'delete'

		const { data } = await httpClient[requestMethod](url, {
			data: {
				...variables,
				ids,
			},
			headers: {
				'Content-Type': 'application/json;',
				...headers,
			},
		})

		return {
			data,
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
			const sortQueryString = generateSort(sorters)
			requestUrl = `${requestUrl}&${sortQueryString}`
		}

		if (filters) {
			const filterQuery = generateFilter(filters)
			requestUrl = `${requestUrl}&${stringify(filterQuery, { arrayFormat: 'bracket' })}`
		}

		if (query) {
			requestUrl = `${requestUrl}&${stringify(query, { arrayFormat: 'bracket' })}`
		}

		if (headers) {
			httpClient.defaults.headers = {
				'Content-Type': 'multipart/form-data;',
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
		return Promise.resolve(axiosResponse)
	},
})
