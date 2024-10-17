import React, { useState, memo } from 'react'
import { Space, DatePicker, Button, message } from 'antd'
import { Dayjs } from 'dayjs'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

const UpdateBoundCoursesComponent = ({
	product_ids,
	course_ids,
	onSettled,
}: {
	product_ids: string[]
	course_ids: string[]
	onSettled: () => void
}) => {
	const [time, setTime] = useState<Dayjs | undefined>(undefined)
	const { mutate, isLoading } = useCustomMutation()
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const handleUpdate = () => () => {
		mutate(
			{
				url: `${apiUrl}/products/update-bound-courses`,
				method: 'post',
				values: {
					product_ids,
					course_ids,
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
						key: 'update-bound-courses',
					})
					invalidate({
						resource: 'products',
						invalidates: ['list'],
					})
					setTime(undefined)
				},
				onError: () => {
					message.error({
						content: '批量修改觀看期限失敗！',
						key: 'update-bound-courses',
					})
				},
				onSettled: () => {
					onSettled()
				},
			},
		)
	}

	return (
		<Space.Compact>
			<DatePicker
				value={time}
				showTime
				placeholder="留空為無期限"
				format="YYYY-MM-DD HH:mm"
				onChange={(value: Dayjs) => {
					setTime(value)
				}}
				disabled={isLoading}
			/>
			<Button
				type="primary"
				disabled={!product_ids.length || !course_ids.length}
				onClick={handleUpdate()}
				ghost
				loading={isLoading}
			>
				修改觀看期限
			</Button>
		</Space.Compact>
	)
}

export const UpdateBoundCourses = memo(UpdateBoundCoursesComponent)
