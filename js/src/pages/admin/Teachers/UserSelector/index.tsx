// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck

import { useState } from 'react'
import { useSelect } from '@refinedev/antd'
import { Select, Space, Button, message } from 'antd'
import { TUserRecord } from '@/pages/admin/Courses/List/types'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import { defaultSelectProps } from 'antd-toolkit'

const index = () => {
	const apiUrl = useApiUrl('power-course')
	const invalidate = useInvalidate()
	const [userIds, setUserIds] = useState<string[]>([])

	const { selectProps } = useSelect<TUserRecord>({
		resource: 'users',
		optionLabel: 'display_name',
		optionValue: 'id',
		filters: [
			{
				field: 'search',
				operator: 'eq',
				value: '',
			},
			{
				field: 'is_teacher',
				operator: 'ne',
				value: 'yes',
			},
		],
		onSearch: (value) => {
			return [
				{
					field: 'search',
					operator: 'eq',
					value,
				},
			]
		},
	})

	// add student mutation
	const { mutate: addStudent, isLoading } = useCustomMutation()

	const handleAdd = () => {
		addStudent(
			{
				url: `${apiUrl}/users/add-teachers`,
				method: 'post',
				values: {
					user_ids: userIds,
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
						content: '新增講師成功！',
						key: 'add-teachers',
					})
					invalidate({
						resource: 'users',
						invalidates: ['list'],
					})
					setUserIds([])
				},
				onError: () => {
					message.error({
						content: '新增講師失敗！',
						key: 'add-teachers',
					})
				},
			},
		)
	}

	return (
		<Space.Compact className="w-full">
			<Button
				type="primary"
				onClick={handleAdd}
				loading={isLoading}
				disabled={!userIds.length}
			>
				從 WordPress User 新增
			</Button>
			<Select
				{...defaultSelectProps}
				{...selectProps}
				placeholder="試試看搜尋 Email, 名稱, ID"
				onChange={(value: string[]) => {
					setUserIds(value)
				}}
				value={userIds}
			/>
		</Space.Compact>
	)
}

export default index
