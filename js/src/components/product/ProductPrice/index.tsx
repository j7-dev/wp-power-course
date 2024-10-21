import { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseTable/types'
import { TProductRecord } from '@/components/product/ProductTable/types'
import { renderHTML } from 'antd-toolkit'
import './style.scss'

export const ProductPrice: FC<{
	record: TProductRecord | TCourseRecord
}> = ({ record }) => {
	const { price_html } = record
	if (!price_html) return null
	return <div className="at-product-price">{renderHTML(price_html)}</div>
}
