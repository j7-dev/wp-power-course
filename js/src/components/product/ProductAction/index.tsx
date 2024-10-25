import React, { FC } from 'react'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import ToggleVisibility from './ToggleVisibility'
import { ExportOutlined } from '@ant-design/icons'
import { Tooltip } from 'antd'
import { siteUrl, course_permalink_structure } from '@/utils'
import { SiGoogleclassroom } from 'react-icons/si'

export const ProductAction: FC<{
	record: TCourseBaseRecord
}> = ({ record }) => {
	return (
		<div className="flex gap-1">
			<Tooltip title="開啟課程教室">
				<a
					href={`${siteUrl}/classroom/${record?.slug}`}
					className="inline-block text-gray-400 w-6 text-center"
					target="_blank"
					rel="noreferrer"
				>
					<SiGoogleclassroom className="relative top-0.5" />
				</a>
			</Tooltip>
			<Tooltip title="開啟課程銷售頁">
				<a
					href={`${siteUrl}/${course_permalink_structure}/${record?.slug}`}
					className="inline-block text-gray-400 w-6 text-center"
					target="_blank"
					rel="noreferrer"
				>
					<ExportOutlined />
				</a>
			</Tooltip>
			<ToggleVisibility record={record} />
		</div>
	)
}
