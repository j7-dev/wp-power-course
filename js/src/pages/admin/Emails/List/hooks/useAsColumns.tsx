import React from 'react'
import { Table, TableProps, Tag } from 'antd'
import { TAsRecord } from '@/pages/admin/Emails/types'
import { useNavigation } from '@refinedev/core'
import { getPostStatus } from '@/utils'
import { ProductName } from '@/components/product'

const useAsColumns = () => {
	const { edit } = useNavigation()
	const onClick = (record: TAsRecord) => () => {
		edit('emails', record.id)
	}
	const columns: TableProps<TAsRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		{
			title: 'Email 名稱',
			dataIndex: 'name',
			width: 180,
			render: (name: string, record) => (
				<ProductName<TAsRecord>
					record={record}
					hideImage
					onClick={onClick(record)}
				/>
			),
		},

		{
			title: '狀態',
			width: 64,
			align: 'center',
			dataIndex: 'is_finished',
			render: (is_finished: boolean) => (
				<Tag color={is_finished ? 'blue' : 'orange'}>
					{is_finished ? '已結束' : '未結束'}
				</Tag>
			),
		},
		{
			title: '順序',
			dataIndex: 'priority',
			width: 64,
		},
		{
			title: '變數',
			width: 240,
			align: 'center',
			dataIndex: 'args',
			render: (args) => JSON.stringify(args),
		},
		{
			title: '執行時間',
			align: 'right',
			dataIndex: 'schedule',
			width: 160,
		},
	]

	return columns
}

export default useAsColumns
