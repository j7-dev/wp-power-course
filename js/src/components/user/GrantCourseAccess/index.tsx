import React, { memo, useState } from 'react'
import { Select, Button, Space, DatePicker, SelectProps, message } from 'antd'
import { useSelect } from '@refinedev/antd'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Dayjs } from 'dayjs'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

const GrantCourseAccessComponent = ({ user_ids }: { user_ids: string[] }) => {
	const { selectProps } = useSelect<TCourseRecord>({
		resource: 'courses',
		optionLabel: 'name',
		optionValue: 'id',
		debounce: 500,
		pagination: {
			pageSize: 20,
			mode: 'server',
		},
		onSearch: (value) => [
			{
				field: 's',
				operator: 'contains',
				value,
			},
		],
	})

	const [time, setTime] = useState<Dayjs | undefined>(undefined)
	const [courseIds, setCourseIds] = useState<string[]>([])

	const { mutate: addStudent, isLoading } = useCustomMutation()
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const handleGrant = () => {
		addStudent(
			{
				url: `${apiUrl}/courses/add-students`,
				method: 'post',
				values: {
					user_ids,
					course_ids: courseIds,
					expire_date: time ? time.unix() : 0,
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
						resource: 'users',
						invalidates: ['list'],
					})
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
			<Select
				{...(selectProps as SelectProps)}
				placeholder="搜尋課程關鍵字"
				className="w-full"
				allowClear
				mode="multiple"
				optionRender={({ value, label }) => {
					return (
						<span>
							{label} <sub className="text-gray-500">#{value}</sub>
						</span>
					)
				}}
				value={courseIds}
				onChange={(value) => {
					setCourseIds(value)
				}}
			/>
			<DatePicker
				placeholder="留空為無期限"
				value={time}
				showTime
				format="YYYY-MM-DD HH:mm"
				onChange={(value: Dayjs) => {
					setTime(value)
				}}
			/>
			<Button
				type="primary"
				loading={isLoading}
				disabled={!user_ids.length || !courseIds.length}
				onClick={handleGrant}
			>
				添加其他課程權限
			</Button>
		</Space.Compact>
	)
}

export const GrantCourseAccess = memo(GrantCourseAccessComponent)
