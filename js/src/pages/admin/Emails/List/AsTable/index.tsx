import { memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, TableProps, Card } from 'antd'
import { HttpError } from '@refinedev/core'
import { TAsRecord } from '@/pages/admin/Emails/types'
import { TFilterProps } from '@/components/product/ProductTable/types'
import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import useAsColumns from '@/pages/admin/Emails/List/hooks/useAsColumns'
const Main = () => {
	const { tableProps } = useTable<TAsRecord, HttpError, TFilterProps>({
		resource: 'emails/scheduled-actions',
		dataProviderName: 'power-email',
		pagination: {
			pageSize: 20,
		},
	})

	const columns = useAsColumns()

	return (
		<Card>
			<Table
				{...(defaultTableProps as unknown as TableProps<TAsRecord>)}
				{...tableProps}
				pagination={{
					...tableProps.pagination,
					...getDefaultPaginationProps({ label: '排程紀錄' }),
				}}
				columns={columns}
				rowKey={(record) => record.id.toString()}
			/>
		</Card>
	)
}

export default memo(Main)
