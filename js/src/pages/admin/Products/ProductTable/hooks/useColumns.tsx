import React from 'react'
import { Table, TableProps, Tag } from 'antd'
import { TProductRecord } from '@/components/product/ProductTable/types'
import {
	ProductName,
	ProductPrice,
	ProductTotalSales,
	ProductCat,
	ProductBoundCourses,
} from '@/components/product'
import { getPostStatus, siteUrl, course_permalink_structure } from '@/utils'

const useColumns = () => {
	const columns: TableProps<TProductRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		Table.EXPAND_COLUMN,
		{
			title: '商品名稱',
			dataIndex: 'name',
			width: 300,
			render: (_, record) => (
				<ProductName
					record={record}
					onClick={
						'variation' === record?.type
							? undefined
							: () => {
									window.open(
										`${siteUrl}/${course_permalink_structure}/${record?.slug}`,
										'_blank',
									)
								}
					}
				/>
			),
		},
		{
			title: '狀態',
			dataIndex: 'status',
			width: 80,
			render: (_, record) => (
				<Tag color={getPostStatus(record?.status)?.color}>
					{getPostStatus(record?.status)?.label}
				</Tag>
			),
		},
		{
			title: '總銷量',
			dataIndex: 'total_sales',
			width: 150,
			render: (_, record) => <ProductTotalSales record={record} />,
		},
		{
			title: '價格',
			dataIndex: 'price',
			width: 150,
			render: (_, record) => <ProductPrice record={record} />,
		},
		{
			title: '綁定的課程',
			dataIndex: 'bind_courses_data',
			width: 320,
			render: (_, record) => <ProductBoundCourses record={record} />,
		},
		{
			title: '商品分類 / 商品標籤',
			dataIndex: 'category_ids',
			render: (_, record) => <ProductCat record={record} />,
		},
	]

	return columns
}

export default useColumns
