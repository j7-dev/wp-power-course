import React from 'react'
import { useCustom, useApiUrl } from '@refinedev/core'
import { TTerm } from '@/pages/admin/Courses/CourseSelector/types'
import { QueryObserverResult } from '@tanstack/react-query'

type TOptions = {
	product_cats: TTerm[]
	product_tags: TTerm[]
	product_brands: (TTerm & {
		logo: string
	})[]
	top_sales_products: (TTerm & {
		total_sales: number
	})[]
	max_price: number
	min_price: number
}

const useOptions: () => QueryObserverResult<any> & {
	options: TOptions
} = () => {
	const apiUrl = useApiUrl()
	const result = useCustom<TOptions>({
		url: `${apiUrl}/courses/options`,
		method: 'get',
	})

	const options: TOptions = result?.data?.data || {
		product_cats: [],
		product_tags: [],
		product_brands: [],
		top_sales_products: [],
		max_price: 0,
		min_price: 0,
	}

	return {
		...result,
		options,
	}
}

export default useOptions
