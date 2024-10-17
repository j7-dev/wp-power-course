import React, { memo, useState } from 'react'
import { Select, Button, Space, DatePicker, message } from 'antd'
import { Dayjs } from 'dayjs'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import { useCourseSelect } from '@/hooks'

const BindCoursesComponent = ({
	product_ids,
	label,
}: {
	product_ids: string[]
	label?: string
}) => {
	const { selectProps, courseIds: course_ids } = useCourseSelect()

	const [time, setTime] = useState<Dayjs | undefined>(undefined)

	const { mutate, isLoading } = useCustomMutation()
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const handleClick = () => {
		mutate(
			{
				url: `${apiUrl}/products/bind-courses`,
				method: 'post',
				values: {
					product_ids,
					course_ids,
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
						content: '綁定課程成功！',
						key: 'bind-courses',
					})
					invalidate({
						resource: 'products',
						invalidates: ['list'],
					})
				},
				onError: () => {
					message.error({
						content: '綁定課程失敗！',
						key: 'bind-courses',
					})
				},
			},
		)
	}

	return (
		<>
			{label && <label className="block mb-2">{label}</label>}
			<Space.Compact className="w-full">
				<Select {...selectProps} />
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
					disabled={!product_ids.length || !course_ids.length}
					onClick={handleClick}
				>
					綁定其他課程
				</Button>
			</Space.Compact>
		</>
	)
}

export const BindCourses = memo(BindCoursesComponent)
