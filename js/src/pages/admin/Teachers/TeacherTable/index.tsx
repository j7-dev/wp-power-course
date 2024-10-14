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
import UserSelector from '../UserSelector'
import { PopconfirmDelete } from '@/components/general'
import { useUserFormDrawer } from '@/hooks'
import { PlusOutlined } from '@ant-design/icons'
import { UserDrawer } from '@/components/user'

const index = () => {
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const { tableProps } = useTable<TUserRecord>({
		resource: 'users',
		filters: {
			permanent: [
				{
					field: 'meta_key',
					operator: 'eq',
					value: 'is_teacher',
				},
				{
					field: 'meta_value',
					operator: 'eq',
					value: 'yes',
				},
			],
		},
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

	// remove teacher mutation
	const { mutate, isLoading } = useCustomMutation()

	const handleRemove = () => {
		mutate(
			{
				url: `${apiUrl}/users/remove-teachers`,
				method: 'post',
				values: {
					user_ids: selectedRowKeys,
				},
				config: {
					headers: {
						'Content-Type': 'multipart/form-data;',
					},
				},
			},
			{
				onSuccess: () => {
					message.success({
						content: '移除講師成功！',
						key: 'remove-teachers',
					})
					invalidate({
						resource: 'users',
						invalidates: ['list'],
					})
					setSelectedRowKeys([])
				},
				onError: () => {
					message.error({
						content: '移除講師失敗！',
						key: 'remove-teachers',
					})
				},
			},
		)
	}

	const [form] = Form.useForm()
	const { show, drawerProps } = useUserFormDrawer({ form, resource: 'users' })
	const columns = useColumns({
		onClick: show,
	})

	return (
		<>
			<div className="flex gap-4 mb-4">
				<Button
					type="primary"
					className="mb-4"
					icon={<PlusOutlined />}
					onClick={show()}
				>
					創建講師
				</Button>
				<div className="flex-1">
					<UserSelector />
				</div>
				<PopconfirmDelete
					type="button"
					popconfirmProps={{
						title: '確認移除這些用戶的講師身分嗎?',
						onConfirm: handleRemove,
					}}
					buttonProps={{
						children: '移除講師身分',
						disabled: !selectedRowKeys.length,
						loading: isLoading,
					}}
				/>
			</div>
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
