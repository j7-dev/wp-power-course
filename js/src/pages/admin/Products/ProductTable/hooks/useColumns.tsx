import React from 'react'
import { Table, TableProps, Tag } from 'antd'
import { TProductRecord } from '@/components/product/ProductTable/types'
import {
	ProductName,
	ProductPrice,
	ProductTotalSales,
	ProductCat,
} from '@/components/product'
import { getPostStatus } from '@/utils'
import { DateTime } from 'antd-toolkit'
import { SecondToStr } from '@/components/general'

const useColumns = () => {
	const columns: TableProps<TProductRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		Table.EXPAND_COLUMN,
		{
			title: '商品名稱',
			dataIndex: 'name',
			width: 300,
			key: 'name',
			render: (_, record) => <ProductName record={record} />,
		},
		{
			title: '狀態',
			dataIndex: 'status',
			width: 80,
			key: 'status',
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
			key: 'total_sales',
			render: (_, record) => <ProductTotalSales record={record} />,
		},
		{
			title: '價格',
			dataIndex: 'price',
			width: 150,
			key: 'price',
			render: (_, record) => <ProductPrice record={record} />,
		},
		{
			title: '商品分類 / 商品標籤',
			dataIndex: 'category_ids',
			key: 'category_ids',
			render: (_, record) => <ProductCat record={record} />,
		},
	]

	return columns
}

export default useColumns
