import React from 'react'

import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

export const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids

export const BUNDLE_TYPE_OPTIONS = [
	{ label: '合購優惠', value: 'bundle', color: 'cyan' },
	{
		label: '🚧 團購優惠 (開發中...)',
		value: 'groupbuy',
		disabled: true,
		color: 'purple',
	},
]

export const PRODUCT_TYPE_OPTIONS = [
	{ label: '簡單商品', value: 'simple' },
	{ label: '定期定額', value: 'subscription' },
]

// 取得總金額
// quantities: 各商品數量，key 為商品 ID，value 為數量（1~999）
// 當前課程已統一在 products 列表中，不再需要 excludeMainCourse 參數
export const getPrice = ({
	isFetching = false,
	type,
	products,
	course: _course,
	returnType = 'number',
	quantities = {},
}: {
	isFetching?: boolean
	type: 'regular_price' | 'sale_price'
	products: TBundleProductRecord[] | undefined
	course?: TCourseRecord | undefined
	returnType?: 'string' | 'number'
	quantities?: Record<string, number>
}): React.ReactNode => {
	if (isFetching) {
		return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
	}
	const total =
		products?.reduce((acc, product) => {
			const qty = quantities[product.id] ?? 1
			return acc + Number(product?.[type] || product.regular_price) * qty
		}, 0) ?? 0

	if ('number' === returnType) return total
	return `NT$ ${total?.toLocaleString()}`
}
