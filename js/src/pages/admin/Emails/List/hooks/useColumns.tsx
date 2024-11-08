import React from 'react'
import { Table, TableProps, Input, Tag, Space } from 'antd'
import { TEmailListRecord } from '@/pages/admin/Emails/types'
import { useNavigation } from '@refinedev/core'
import { getPostStatus } from '@/utils'
import { ProductName } from '@/components/product'

const useColumns = () => {
	const { edit } = useNavigation()
	const onClick = (record: TEmailListRecord) => () => {
		edit('emails', record.id)
	}
	const columns: TableProps<TEmailListRecord>['columns'] = [
		Table.SELECTION_COLUMN,
		{
			title: '主旨',
			dataIndex: 'name',
			width: 300,
			render: (name: string, record) => (
				<ProductName<TEmailListRecord>
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
			dataIndex: 'status',
			render: (status: string) => (
				<Tag color={getPostStatus(status)?.color}>
					{getPostStatus(status)?.label}
				</Tag>
			),
		},
		{
			title: '發送時機',
			width: 240,
			align: 'center',
			dataIndex: 'action_name',
			render: (_value: string, record, index) => (
				<Space.Compact>
					<Input className="pointer-events-none" value={record.action_name} />
					<Input className="pointer-events-none" value={record.days} />
					<Input className="pointer-events-none" value={record.operator} />
				</Space.Compact>
			),
		},
		{
			title: '上次修改時間',
			align: 'right',
			dataIndex: 'date_modified',
			width: 160,
		},
	]

	return columns
}

export default useColumns
