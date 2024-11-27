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
			title: 'Email 名稱',
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
					{status === 'publish' ? '啟用' : '停用'}
				</Tag>
			),
		},
		{
			title: 'Email 主旨',
			dataIndex: 'subject',
			width: 300,
		},

		// {
		// 	title: '發送時機',
		// 	width: 240,
		// 	align: 'center',
		// 	dataIndex: 'condition',
		// 	render: (condition) => (
		// 		<Space.Compact>
		// 			<Input
		// 				className="pointer-events-none"
		// 				value={condition?.trigger_at as string}
		// 			/>
		// 		</Space.Compact>
		// 	),
		// },
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
