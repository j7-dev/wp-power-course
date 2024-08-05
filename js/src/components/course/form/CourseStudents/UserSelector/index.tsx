// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck

import { useState } from 'react'
import { useSelect } from '@refinedev/antd'
import { Select, Space, Button, Form, message } from 'antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

const index = () => {
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()
	const [userIds, setUserIds] = useState<string[]>([])
	const [keyword, setKeyword] = useState<string>('')

	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)

	const { selectProps } = useSelect<TUserRecord>({
		resource: 'users/students',
		optionLabel: 'display_name',
		optionValue: 'id',
		filters: [
			{
				field: 'search',
				operator: 'eq',
				value: '',
			},
			{
				field: 'posts_per_page',
				operator: 'eq',
				value: '30',
			},
			{
				field: 'meta_key',
				operator: 'eq',
				value: 'avl_course_ids',
			},
			{
				field: 'meta_value',
				operator: 'ne',
				value: watchId,
			},
		],
		onSearch: (value) => {
			setKeyword(value)
			return [
				{
					field: 'search',
					operator: 'eq',
					value,
				},
			]
		},
		queryOptions: {
			enabled: !!watchId && !!keyword,
		},
	})

	// add student mutation
	const { mutate: addStudent, isLoading } = useCustomMutation()

	const handleAdd = () => {
		addStudent(
			{
				url: `${apiUrl}/courses/${watchId}/add-students`,
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
						content: '新增學員成功！',
						key: 'add-students',
					})
					invalidate({
						resource: 'users/students',
						invalidates: ['list'],
					})
					setUserIds([])
				},
				onError: () => {
					message.error({
						content: '新增學員失敗！',
						key: 'add-students',
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
				新增學員
			</Button>
			<Select
				{...selectProps}
				className="w-full"
				placeholder="試試看搜尋 Email, 名稱, ID"
				mode="multiple"
				allowClear
				onChange={(value: string[]) => {
					setUserIds(value)
				}}
				value={userIds}
			/>
		</Space.Compact>
	)
}

export default index
