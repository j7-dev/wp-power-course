import React, { useState, memo } from 'react'
import { GrantCourseAccess } from '@/components/user'
import { ExportOutlined } from '@ant-design/icons'
import { siteUrl } from '@/utils'

const AddOtherCourse = ({ user_ids }: { user_ids: string[] }) => {
	const [show, setShow] = useState(false)
	return (
		<div className="mb-4 -mt-2">
			<p>
				<span
					onClick={() => setShow(true)}
					className="cursor-pointer text-primary m-0"
				>
					添加其他課程
				</span>{' '}
				或{' '}
				<a
					href={`${siteUrl}/wp-admin/admin.php?page=power-course#/students`}
					target="_blank"
					className="cursor-pointer text-primary"
					rel="noreferrer"
				>
					前往學員管理
					<ExportOutlined className="ml-1" />
				</a>
			</p>
			{show && <GrantCourseAccess user_ids={user_ids as string[]} />}
		</div>
	)
}

export default memo(AddOtherCourse)
