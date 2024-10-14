import React from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Table, message, Button, Form, TableProps } from 'antd'
import useColumns from './hooks/useColumns'
import { useRowSelection } from 'antd-toolkit'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import {
	defaultPaginationProps,
	defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'
import { PopconfirmDelete } from '@/components/general'
import { useUserFormDrawer } from '@/hooks'
import { PlusOutlined } from '@ant-design/icons'
import { UserDrawer } from '@/components/user'

const index = () => {
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const { tableProps } = useTable<TUserRecord>({
		resource: 'users',
		pagination: {
			pageSize: 20,
		},
	})

	// 多選
	const { rowSelection, setSelectedRowKeys, selectedRowKeys } =
		useRowSelection<TUserRecord>({
			onChange: (currentSelectedRowKeys: React.Key[]) => {
				setSelectedRowKeys(currentSelectedRowKeys)
			},
		})

	const [form] = Form.useForm()
	const { show, drawerProps } = useUserFormDrawer({ form, resource: 'users' })
	const columns = useColumns({
		onClick: show,
	})

	return (
		<>
			<div className="flex gap-4 mb-4"></div>
			<Table
				{...(defaultTableProps as unknown as TableProps<TUserRecord>)}
				{...tableProps}
				columns={columns}
				rowSelection={rowSelection}
				pagination={{
					...tableProps.pagination,
					...defaultPaginationProps,
				}}
			/>
			<Form layout="vertical" form={form}>
				<UserDrawer {...drawerProps} />
			</Form>
		</>
	)
}

export default index
