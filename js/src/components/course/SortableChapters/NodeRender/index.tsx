import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { getPostStatus } from '@/utils'
import { FlattenNode, useSortableTree } from '@ant-design/pro-editor'
import { ChapterName } from '@/components/course'
import {
	SecondToStr,
	DuplicateButton,
	PopconfirmDelete,
} from '@/components/general'
import { Checkbox, CheckboxProps, Tooltip } from 'antd'

const NodeRender: FC<{
	node: FlattenNode<TChapterRecord>
	selectedChapter: TChapterRecord | null
	setSelectedChapter: React.Dispatch<
		React.SetStateAction<TChapterRecord | null>
	>
	selectedIds: string[]
	setSelectedIds: React.Dispatch<React.SetStateAction<string[]>>
}> = ({
	node,
	selectedChapter,
	setSelectedChapter,
	selectedIds,
	setSelectedIds,
}) => {
	const { removeNode } = useSortableTree()
	const record = node.content
	if (!record) {
		return <div>{`ID: ${node.id}`} 找不到章節資料</div>
	}

	const handleDelete = () => {
		removeNode(node.id)
	}

	const handleCheck: CheckboxProps['onChange'] = (e) => {
		if (e.target.checked) {
			setSelectedIds((prev) => [...prev, node.id as string])
		} else {
			setSelectedIds((prev) => prev.filter((id) => id !== node.id))
		}
	}
	const isChecked = selectedIds.includes(node.id as string)
	const isSelectedChapter = selectedChapter?.id === node.id

	const showPlaceholder = node?.children?.length === 0
	return (
		<div
			className={`grid grid-cols-[1fr_3rem_7rem_4rem] gap-4 justify-start items-center ${isSelectedChapter ? 'bg-[#e6f4ff]' : ''}`}
		>
			<div className="flex items-center">
				{showPlaceholder && <div className="w-[28px] h-[28px]"></div>}
				<Checkbox className="mr-2" onChange={handleCheck} checked={isChecked} />
				<ChapterName record={record} setSelectedChapter={setSelectedChapter} />
			</div>
			<div className="text-xs text-gray-400">
				{getPostStatus(record?.status || '')?.label}
			</div>
			<div>
				<SecondToStr second={record?.chapter_length} />
			</div>

			<div className="flex gap-2">
				<DuplicateButton
					id={record?.id}
					invalidateProps={{
						resource: 'chapters',
					}}
					tooltipProps={{ title: '複製章節/單元' }}
				/>

				<PopconfirmDelete
					tooltipProps={{ title: '刪除' }}
					popconfirmProps={{
						description: '刪除會連同子單元也一起刪除',
						onConfirm: handleDelete,
					}}
				/>
			</div>
		</div>
	)
}

export default NodeRender
