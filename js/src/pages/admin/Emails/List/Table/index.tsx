import { memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, Button, TableProps, Card } from 'antd'
import { useRowSelection } from 'antd-toolkit'
import { HttpError, useCreate } from '@refinedev/core'
import { TEmailRecord } from '@/pages/admin/Emails/List/types'
import { TFilterProps } from '@/components/product/ProductTable/types'
import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import useColumns from '@/pages/admin/Emails/List/hooks/useColumns'
import { PlusOutlined } from '@ant-design/icons'
import DeleteButton from './DeleteButton'

const Main = () => {
	const { tableProps } = useTable<TEmailRecord, HttpError, TFilterProps>({
		resource: 'emails',
		dataProviderName: 'power-email',
	})

	const { rowSelection, selectedRowKeys, setSelectedRowKeys } =
		useRowSelection<TEmailRecord>()

	const columns = useColumns()

	const { mutate: create, isLoading: isCreating } = useCreate({
		resource: 'emails',
		dataProviderName: 'power-email',
		invalidates: ['list'],
		meta: {
			headers: { 'Content-Type': 'multipart/form-data;' },
		},
	})

	const createEmail = () => {
		create({
			values: {},
		})
	}

	return (
		<Card>
			<div className="mb-4 flex justify-between">
				<Button
					loading={isCreating}
					type="primary"
					icon={<PlusOutlined />}
					onClick={createEmail}
				>
					新增 Email
				</Button>
				<DeleteButton
					selectedRowKeys={selectedRowKeys}
					setSelectedRowKeys={setSelectedRowKeys}
				/>
			</div>
			<Table
				{...(defaultTableProps as unknown as TableProps<TEmailRecord>)}
				{...tableProps}
				pagination={{
					...tableProps.pagination,
					...getDefaultPaginationProps({ label: 'Email' }),
				}}
				rowSelection={rowSelection}
				columns={columns}
				rowKey={(record) => record.id.toString()}
			/>
		</Card>
	)
}

export default memo(Main)
