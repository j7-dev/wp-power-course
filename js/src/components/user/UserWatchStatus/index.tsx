import { FC } from 'react'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Form, Tag } from 'antd'
import dayjs from 'dayjs'

export const UserWatchStatus: FC<{
	record: TUserRecord
}> = ({ record }) => {
	const { avl_courses } = record
	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)

	const currentCourse = avl_courses?.find((course) => course?.id === watchId)

	if (!currentCourse) return <>出錯了！找不到課程</>

	const expireDate = currentCourse?.expire_date
		? Number(currentCourse?.expire_date)
		: undefined

	const currentTimestamp = dayjs().unix()

	if (!expireDate) {
		return <Tag color="blue">無期限</Tag>
	}

	if (currentTimestamp > expireDate) {
		return <Tag color="magenta">已過期</Tag>
	}

	return <Tag color="green">未過期</Tag>
}
