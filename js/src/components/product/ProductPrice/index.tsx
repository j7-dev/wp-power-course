import { FC } from 'react'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { TProductRecord } from '@/components/product/ProductTable/types'
import { renderHTML } from 'antd-toolkit'

export const ProductPrice: FC<{
	record: TProductRecord | TCourseBaseRecord
}> = ({ record }) => {
	const { price_html } = record
	if (!price_html) return null
	return <div className="at-product-price">{renderHTML(price_html)}</div>
}
