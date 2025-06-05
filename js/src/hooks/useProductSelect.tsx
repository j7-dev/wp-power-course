import React, { useState } from 'react'
import { useSelect } from '@refinedev/antd'
import { UseSelectProps, HttpError } from '@refinedev/core'

import { TProductRecord } from '@/components/product/ProductTable/types'
import { SelectProps } from 'antd'

type TUseProductSelectParams = {
	selectProps?: SelectProps
	useSelectProps?: Partial<
		UseSelectProps<TProductRecord, HttpError, TProductRecord>
	>
}

export const useProductSelect = (params?: TUseProductSelectParams) => {
	const selectProps = params?.selectProps
	const useSelectProps = params?.useSelectProps
	const [productIds, setProductIds] = useState<string[]>([])

	const defaultSelectProps: SelectProps = {
		placeholder: '搜尋商品關鍵字',
		className: 'w-full',
		allowClear: true,
		mode: 'multiple',
		optionRender: ({ value, label }) => {
			return (
				<span>
					{label} <span className="text-gray-400 text-xs">#{value}</span>
				</span>
			)
		},
		value: productIds,
		onChange: (value: string[]) => {
			setProductIds(value)
		},
	}

	const { selectProps: refineSelectProps, query } = useSelect<TProductRecord>({
		resource: 'products',
		dataProviderName: 'power-course',
		debounce: 500,
		pagination: {
			pageSize: 20,
			mode: 'server',
		},
		onSearch: (value) => [
			{
				field: 's',
				operator: 'contains',
				value,
			},
		],
		...useSelectProps,
	})

	const products = query.data?.data ?? []
	const options = products.map((product) => ({
		label: product.name,
		value: product.id,
	}))

	const mergedSelectProps: SelectProps = {
		...defaultSelectProps,
		...selectProps,
		...refineSelectProps,
		options,
	}

	return {
		selectProps: mergedSelectProps,
		productIds,
		setProductIds,
	}
}
