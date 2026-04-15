import { useNavigation } from '@refinedev/core'
import { useWindowSize } from '@uidotdev/usehooks'
import { Table, TableProps, Tag } from 'antd'
import { DateTime } from 'antd-toolkit'
import { POST_STATUS } from 'antd-toolkit/wp'
import React from 'react'

import { SecondToStr } from '@/components/general'
import {
	ProductName,
	ProductType,
	ProductPrice,
	ProductTotalSales,
	ProductStock,
	ProductCat,
	ProductAction,
} from '@/components/product'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'

const useColumns = () => {
	const { width } = useWindowSize()
	const { edit } = useNavigation()
	const onClick = (record: TCourseBaseRecord) => () => {
		edit('courses', record.id)
	}
	const columns: TableProps<TCourseBaseRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		{
			title: '商品名稱',
			dataIndex: 'name',
			width: 300,
			key: 'name',
			fixed: (width || 400) > 768 ? 'left' : undefined,
			render: (_, record) => (
				<ProductName<TCourseBaseRecord>
					record={record}
					onClick={onClick(record)}
				/>
			),
		},
		{
			title: '商品類型',
			dataIndex: 'type',
			width: 180,
			key: 'type',
			render: (_, record) => (
				<ProductType
					record={
						record as unknown as Parameters<typeof ProductType>[0]['record']
					}
				/>
			),
		},
		{
			title: '狀態',
			dataIndex: 'status',
			width: 80,
			key: 'status',
			align: 'center',
			render: (_, record) => {
				const status = POST_STATUS.find((item) => item.value === record?.status)
				// unknown status fallback：避免渲染空 Tag
				if (!status) {
					return <Tag color="default">{record?.status || '-'}</Tag>
				}
				return <Tag color={status.color}>{status.label}</Tag>
			},
		},
		{
			title: '總銷量',
			dataIndex: 'total_sales',
			width: 80,
			key: 'total_sales',
			align: 'center',
			render: (_, record) => (
				<ProductTotalSales
					record={record}
					optionParams={{
						endpoint: 'courses/options',
					}}
				/>
			),
		},
		{
			title: '價格',
			dataIndex: 'price',
			width: 150,
			key: 'price',
			render: (_, record) => <ProductPrice record={record} />,
		},
		{
			title: '庫存',
			dataIndex: 'stock',
			width: 150,
			key: 'stock',
			align: 'center',
			render: (_, record) => <ProductStock record={record} />,
		},
		{
			title: '開課時間',
			dataIndex: 'course_schedule',
			width: 180,
			key: 'course_schedule',
			render: (course_schedule: number) =>
				course_schedule ? (
					<DateTime
						date={course_schedule * 1000}
						timeProps={{
							format: 'HH:mm',
						}}
					/>
				) : (
					'-'
				),
		},
		{
			title: '時數',
			dataIndex: 'course_length',
			width: 180,
			key: 'course_length',
			render: (course_length) => <SecondToStr second={course_length} />,
		},
		{
			title: '商品分類 / 商品標籤',
			dataIndex: 'category_ids',
			key: 'category_ids',
			render: (_, record) => <ProductCat record={record} />,
		},
		{
			title: '操作',
			dataIndex: '_actions',
			key: '_actions',
			render: (_, record) => <ProductAction record={record} />,
		},
	]

	return columns
}

export default useColumns
