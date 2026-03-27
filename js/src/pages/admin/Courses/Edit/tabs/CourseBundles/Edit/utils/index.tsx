import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

export const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids
export const PRODUCT_QUANTITIES_FIELD_NAME = 'pbp_product_quantities' // 每個商品的數量

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
export const getPrice = ({
	isFetching = false,
	type,
	products,
	course,
	returnType = 'number',
	excludeMainCourse = false,
	quantities,
}: {
	isFetching?: boolean
	type: 'regular_price' | 'sale_price'
	products: TBundleProductRecord[] | undefined
	course: TCourseRecord | undefined
	returnType?: 'string' | 'number'
	excludeMainCourse?: boolean
	quantities?: Record<string, number>
}): React.ReactNode => {
	if (isFetching) {
		return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
	}
	const courseQty = quantities?.[course?.id ?? ''] ?? 1
	const coursePrice =
		Number(course?.[type] || course?.regular_price || 0) * courseQty
	const total =
		Number(
			products?.reduce((acc, product) => {
				const qty = quantities?.[product.id] ?? 1
				return acc + Number(product?.[type] || product.regular_price) * qty
			}, 0)
		) + (excludeMainCourse ? 0 : coursePrice)

	if ('number' === returnType) return total
	return `NT$ ${total?.toLocaleString()}`
}
