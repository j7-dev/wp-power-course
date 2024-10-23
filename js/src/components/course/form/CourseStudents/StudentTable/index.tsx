import React, { useState, memo } from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/List/types'
import {
	Table,
	Form,
	message,
	DatePicker,
	Space,
	Button,
	TableProps,
} from 'antd'
import useColumns from '@/pages/admin/Students/StudentTable/hooks/useColumns'
import { useRowSelection } from 'antd-toolkit'
import { PopconfirmDelete } from '@/components/general'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import { Dayjs } from 'dayjs'
import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import AddOtherCourse from '../AddOtherCourse'

const StudentTable = () => {
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()
	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)
	const columns = useColumns()
	const { tableProps } = useTable<TUserRecord>({
		resource: 'users/students',
		filters: {
			permanent: [
				{
					field: 'meta_key',
					operator: 'eq',
					value: 'avl_course_ids',
				},
				{
					field: 'meta_value',
					operator: 'eq',
					value: watchId,
				},
			],
		},
		pagination: {
			pageSize: 20,
		},
		queryOptions: {
			enabled: !!watchId,
		},
	})

	// 多選
	const { rowSelection, setSelectedRowKeys, selectedRowKeys } =
		useRowSelection<TUserRecord>({
			onChange: (currentSelectedRowKeys: React.Key[]) => {
				setSelectedRowKeys(currentSelectedRowKeys)
			},
		})

	// remove student mutation
	const { mutate, isLoading } = useCustomMutation()

	const handleRemove = () => {
		mutate(
			{
				url: `${apiUrl}/courses/remove-students`,
				method: 'post',
				values: {
					user_ids: selectedRowKeys,
					course_ids: [watchId],
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
						content: '移除學員成功！',
						key: 'remove-students',
					})
					invalidate({
						resource: 'users/students',
						invalidates: ['list'],
					})
					setSelectedRowKeys([])
				},
				onError: () => {
					message.error({
						content: '移除學員失敗！',
						key: 'remove-students',
					})
				},
			},
		)
	}

	// update student mutation
	const [time, setTime] = useState<Dayjs | undefined>(undefined)

	const handleUpdate = () => {
		mutate(
			{
				url: `${apiUrl}/courses/update-students`,
				method: 'post',
				values: {
					user_ids: selectedRowKeys,
					course_ids: [watchId],
					timestamp: time ? time?.unix() : 0,
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
						content: '批量修改觀看期限成功！',
						key: 'update-students',
					})
					invalidate({
						resource: 'users/students',
						invalidates: ['list'],
					})
					setSelectedRowKeys([])
					setTime(undefined)
				},
				onError: () => {
					message.error({
						content: '批量修改觀看期限失敗！',
						key: 'update-students',
					})
				},
			},
		)
	}

	return (
		<>
			<div className="mb-4 flex justify-between gap-4">
				<Space.Compact>
					<DatePicker
						value={time}
						placeholder="留空為無期限"
						showTime
						format="YYYY-MM-DD HH:mm"
						onChange={(value: Dayjs) => {
							setTime(value)
						}}
					/>
					<Button
						type="primary"
						disabled={!selectedRowKeys.length}
						onClick={handleUpdate}
					>
						修改觀看期限
					</Button>
				</Space.Compact>
				<PopconfirmDelete
					type="button"
					popconfirmProps={{
						title: '確認移除這些學員嗎?',
						onConfirm: handleRemove,
					}}
					buttonProps={{
						children: '移除課程(移除學員)',
						disabled: !selectedRowKeys.length,
						loading: isLoading,
					}}
				/>
			</div>

			<AddOtherCourse user_ids={selectedRowKeys as string[]} />

			<Table
				{...(defaultTableProps as unknown as TableProps<TUserRecord>)}
				{...tableProps}
				columns={columns}
				rowSelection={rowSelection}
				expandable={undefined}
				pagination={{
					...tableProps.pagination,
					...getDefaultPaginationProps({ label: '學員' }),
				}}
			/>
		</>
	)
}

export default memo(StudentTable)
