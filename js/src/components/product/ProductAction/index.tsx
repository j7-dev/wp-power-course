import React, { FC } from 'react'
import { TCourseRecord, TChapterRecord } from '@/pages/admin/Courses/List/types'
import AddChapter from '@/components/product/ProductAction/AddChapter'
import ToggleVisibility from './ToggleVisibility'
import { ExportOutlined } from '@ant-design/icons'
import { Tooltip } from 'antd'
import { siteUrl, course_permalink_structure } from '@/utils'
import { SiGoogleclassroom } from 'react-icons/si'

export const ProductAction: FC<{
	record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
	const countPublishSubChapter = record?.chapters
		?.filter((chapter) => chapter?.status === 'publish')
		?.reduce((acc, chapter) => {
			const count =
				chapter?.chapters?.reduce(
					(subAcc, subChapter) =>
						subAcc + (subChapter?.status === 'publish' ? 1 : 0),
					0,
				) || 0
			return acc + count
		}, 0)

	return (
		<div className="flex gap-1">
			<Tooltip
				title={
					countPublishSubChapter
						? '開啟課程教室'
						: '需要有已發佈的單元才能開啟教室'
				}
			>
				{!!countPublishSubChapter && (
					<a
						href={`${siteUrl}/classroom/${record?.slug}`}
						className="inline-block text-gray-400 w-6 text-center"
						target="_blank"
						rel="noreferrer"
					>
						<SiGoogleclassroom className="relative top-0.5" />
					</a>
				)}
				{!countPublishSubChapter && (
					<div className="inline-block text-gray-200 w-6 text-center">
						<SiGoogleclassroom className="relative top-0.5 cursor-not-allowed" />
					</div>
				)}
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
