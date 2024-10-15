import React from 'react'
import { TableProps, Typography } from 'antd'
import {
	TUserRecord,
	TAVLCourse,
} from '@/pages/admin/Courses/CourseSelector/types'
import { UserName } from '@/components/user'
import { WatchStatusTag } from '@/components/general'
import dayjs from 'dayjs'

type TUseColumnsParams = {
	onClick?: (_record: TUserRecord | undefined) => () => void
}

const { Text } = Typography

const useColumns = (params?: TUseColumnsParams) => {
	const show = params?.onClick
	const columns: TableProps<TUserRecord>['columns'] = [
		{
			title: '會員',
			dataIndex: 'id',
			width: 180,
			render: (_, record) => <UserName record={record} onClick={show} />,
		},
		{
			title: '已開通課程',
			dataIndex: 'avl_courses',
			width: 240,
			render: (avl_courses: TAVLCourse[]) => {
				return avl_courses.map(({ id, name, expire_date }) => (
					<div key={id} className="grid grid-cols-[12rem_4rem_8rem] gap-1 my-1">
						<div>
							<Text
								ellipsis={{
									tooltip: (
										<>
											{name || '未知的課程名稱'} <sub>#{id}</sub>
										</>
									),
								}}
							>
								{name || '未知的課程名稱'} <sub>#{id}</sub>
							</Text>
						</div>

						<div className="text-center">
							<WatchStatusTag expireDate={expire_date} />
						</div>

						<div className="text-center">
							{expire_date
								? dayjs.unix(expire_date).format('YYYY/MM/DD HH:mm')
								: ''}
						</div>
					</div>
				))
			},
		},
		{
			title: '註冊時間',
			dataIndex: 'user_registered',
			width: 180,
			render: (user_registered, record) => (
				<>
					<p className="m-0">已註冊 {record?.user_registered_human}</p>
					<p className="m-0 text-gray-500 text-xs">{user_registered}</p>
				</>
			),
		},
	]

	return columns
}

export default useColumns
