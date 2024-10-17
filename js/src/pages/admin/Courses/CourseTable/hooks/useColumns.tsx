import React from 'react'
import { Table, TableProps, Tag } from 'antd'
import {
	TChapterRecord,
	TCourseRecord,
} from '@/pages/admin/Courses/CourseTable/types'
import {
	ProductName,
	ProductPrice,
	ProductTotalSales,
	ProductCat,
	ProductAction,
} from '@/components/product'
import { getPostStatus } from '@/utils'
import { DateTime } from 'antd-toolkit'
import { SecondToStr } from '@/components/general'

const useColumns = ({
	showCourseDrawer,
	showChapterDrawer,
}: {
	showCourseDrawer: (_record: TCourseRecord) => () => void
	showChapterDrawer: (_record: TChapterRecord) => () => void
}) => {
	const onClick = (record: TCourseRecord | TChapterRecord) => () => {
		const { type } = record
		const isChapter = type === 'chapter'
		if (isChapter) {
			showChapterDrawer(record as TChapterRecord)()
		} else {
			showCourseDrawer(record as TCourseRecord)()
		}
	}
	const columns: TableProps<TCourseRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		Table.EXPAND_COLUMN,
		{
			title: '商品名稱',
			dataIndex: 'name',
			width: 300,
			key: 'name',
			render: (_, record) => <ProductName record={record} onClick={onClick} />,
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
			title: '開課時間',
			dataIndex: 'course_schedule',
			width: 180,
			key: 'type',
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
