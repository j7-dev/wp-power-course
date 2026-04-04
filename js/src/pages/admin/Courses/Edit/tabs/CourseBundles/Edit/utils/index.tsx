import React from 'react'

import { TSelectedProduct } from '../atom'

export const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids
export const PRODUCT_QUANTITIES_FIELD_NAME = 'pbp_product_quantities' // 各商品數量 JSON

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

// 取得總金額（含數量計算）
export const getPrice = ({
	isFetching = false,
	type,
	products,
	returnType = 'number',
}: {
	isFetching?: boolean
	type: 'regular_price' | 'sale_price'
	products: TSelectedProduct[] | undefined
	returnType?: 'string' | 'number'
}): React.ReactNode => {
	if (isFetching) {
		return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
	}

	const total = Number(
		products?.reduce((acc, product) => {
			const price = Number(product?.[type] || product?.regular_price || 0)
			const qty = product?.qty || 1
			return acc + price * qty
		}, 0)
	)

	if ('number' === returnType) return total
	return `NT$ ${total?.toLocaleString()}`
}
