import { FC } from 'react'
import {
	TChapterRecord,
	TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { renderHTML } from 'antd-toolkit'
import { message } from 'antd'

export const ChapterName: FC<{
	record: TCourseRecord | TChapterRecord
	show: {
		showCourseDrawer: (_record: TCourseRecord | undefined) => () => void
		showChapterDrawer: (_record: TChapterRecord | undefined) => () => void
	}
	loading?: boolean
}> = ({ record, show, loading = false }) => {
	const { id, sku = '', name, type } = record
	const { showChapterDrawer, showCourseDrawer } = show

	const isChapter = type === 'chapter'

	const handleClick = () => {
		if (!loading) {
			if (isChapter) {
				showChapterDrawer({ ...record } as TChapterRecord)()
			} else {
				showCourseDrawer({ ...record } as TCourseRecord)()
			}
		} else {
			message.error('請等待儲存後再進行編輯')
		}
	}

	return (
		<>
			<p
				className="text-primary hover:text-primary/70 cursor-pointer"
				onClick={handleClick}
			>
				{renderHTML(name)}
			</p>
			<div className="flex ml-2 text-[0.675rem] text-gray-500">
				<span className="pr-3">{`ID: ${id}`}</span>
				{sku && <span className="pr-3">{`SKU: ${sku}`}</span>}
			</div>
		</>
	)
}
