import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { getPostStatus } from '@/utils'
import { FlattenNode } from '@ant-design/pro-editor'
import { ChapterName } from '@/components/course'
import { SecondToStr } from '@/components/general'

const NodeRender: FC<{
	node: FlattenNode<TChapterRecord>
	setSelectedChapter: React.Dispatch<
		React.SetStateAction<TChapterRecord | null>
	>
}> = ({ node, setSelectedChapter }) => {
	const record = node.content
	if (!record) {
		return <div>{`ID: ${node.id}`} 找不到章節資料</div>
	}

	const showPlaceholder = node?.children?.length === 0
	return (
		<div className="grid grid-cols-[1fr_3rem_7rem] gap-4 justify-start items-center">
			<div className="flex items-end">
				{showPlaceholder && <div className="w-[28px] h-[28px]"></div>}
				<ChapterName record={record} setSelectedChapter={setSelectedChapter} />
			</div>
			<div className="text-xs text-gray-400">
				{getPostStatus(record?.status || '')?.label}
			</div>
			<div>
				<SecondToStr second={record?.chapter_length} />
			</div>
		</div>
	)
}

export default NodeRender
