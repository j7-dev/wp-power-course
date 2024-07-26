import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/CourseSelector/types'

export const ChapterLength: FC<{
	record: TChapterRecord
}> = ({ record }) => {
	const { chapter_length } = record

	if (!chapter_length)
		return <div className="text-gray-400 text-xs">未設定</div>

	const hours = Math.floor(chapter_length / 60 / 60)
	const minutes = Math.floor((chapter_length / 60) % 60)
	const seconds = Math.floor(chapter_length % 60)

	return (
		<div className="text-gray-400 text-xs">
			{`${hours > 99 ? hours : hours.toString().padStart(2, '0')} 時 ${minutes.toString().padStart(2, '0')} 分 ${seconds.toString().padStart(2, '0')} 秒`}
		</div>
	)
}
