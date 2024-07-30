import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { renderHTML } from 'antd-toolkit'
import { message } from 'antd'

export const ChapterName: FC<{
	record: TChapterRecord
	show: (_record: TChapterRecord | undefined) => () => void
	loading?: boolean
}> = ({ record, show, loading = false }) => {
	const { id, name } = record

	const handleClick = () => {
		if (!loading) {
			show({ ...record } as TChapterRecord)()
		} else {
			message.error('請等待儲存後再進行編輯')
		}
	}

	return (
		<>
			<p
				className="text-primary hover:text-primary/70 cursor-pointer !mb-0"
				onClick={handleClick}
			>
				{renderHTML(name)}
			</p>
			<div className="flex ml-2 text-[0.675rem] text-gray-500">
				<span className="pr-3">{`ID: ${id}`}</span>
			</div>
		</>
	)
}
