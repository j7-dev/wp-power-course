import React, { FC } from 'react'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Tag } from 'antd'

export const UserName: FC<{
	record: TUserRecord
	onClick?: (_record: TUserRecord | undefined) => () => void
}> = ({ record, onClick = (_record: TUserRecord | undefined) => () => {} }) => {
	const { display_name, user_email, id, user_avatar_url, is_teacher } = record
	return (
		<div className="grid grid-cols-[2rem_1fr] gap-4 items-center">
			<img src={user_avatar_url} className="w-8 h-8 rounded-full" />
			<div>
				<p className="mb-1 cursor-pointer" onClick={onClick(record)}>
					{is_teacher ? <Tag color="magenta">講師</Tag> : ''}
					{display_name} <sub className="ml-1 text-gray-400">#{id}</sub>
				</p>
				<p className="text-xs text-gray-400">{user_email}</p>
			</div>
		</div>
	)
}
