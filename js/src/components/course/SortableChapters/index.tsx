import { useState, useEffect, memo } from 'react'
import { SortableTree, TreeData } from '@ant-design/pro-editor'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { Form, message } from 'antd'
import NodeRender from './NodeRender'
import { chapterToTreeNode, treeToParams } from './utils'
import {
	useCustomMutation,
	useApiUrl,
	useInvalidate,
	useList,
	HttpError,
} from '@refinedev/core'
import { isEqual as _isEqual } from 'lodash-es'
import { ChapterEdit } from '@/components/chapters'
import AddChapters from './AddChapters'

const LoadingChapters = () => (
	<div className="pl-3">
		{new Array(10).fill(0).map((_, index) => (
			<div
				key={index}
				className=" bg-gray-100 h-7 rounded-sm mb-1 animate-pulse"
			/>
		))}
	</div>
)

const SortableChaptersComponent = () => {
	const form = Form.useFormInstance()
	const courseId = form?.getFieldValue('id')
	const { data: chaptersData, isFetching: isListFetching } = useList<
		TChapterRecord,
		HttpError
	>({
		resource: 'chapters',
		filters: [
			{
				field: 'post_parent',
				operator: 'eq',
				value: courseId,
			},
		],
		pagination: {
			current: 1,
			pageSize: -1,
		},
	})

	const chapters = chaptersData?.data || []

	const [treeData, setTreeData] = useState<TreeData<TChapterRecord>>([])
	const [originTree, setOriginTree] = useState<TreeData<TChapterRecord>>([])
	const invalidate = useInvalidate()

	const apiUrl = useApiUrl()
	const { mutate, isLoading } = useCustomMutation()

	useEffect(() => {
		if (!isListFetching) {
			const chapterTree = chapters?.map(chapterToTreeNode)
			setTreeData((prev) => {
				// 維持原本的開合狀態
				const newChapterTree = chapterTree.map((item) => ({
					...item,
					collapsed:
						prev?.find((prevItem) => prevItem.id === item.id)?.collapsed ??
						true,
				}))

				return newChapterTree
			})
			setOriginTree(chapterTree)
		}
	}, [isListFetching])

	const handleSave = (data: TreeData<TChapterRecord>) => {
		// 這個儲存只存新增，不存章節的細部資料
		message.loading({
			content: '排序儲存中...',
			key: 'chapter-sorting',
		})
		const from_tree = treeToParams(originTree, courseId)
		const to_tree = treeToParams(data, courseId)

		mutate(
			{
				url: `${apiUrl}/chapters/sort`,
				method: 'post',
				values: {
					from_tree,
					to_tree,
				},
			},
			{
				onSuccess: () => {
					message.success({
						content: '排序儲存成功',
						key: 'chapter-sorting',
					})
				},
				onError: () => {
					message.loading({
						content: '排序儲存失敗',
						key: 'chapter-sorting',
					})
				},
				onSettled: () => {
					invalidate({
						resource: 'courses',
						invalidates: ['list'],
					})
				},
			},
		)
	}

	const [selectedChapter, setSelectedChapter] = useState<TChapterRecord | null>(
		null,
	)

	return (
		<>
			<div className="mb-8">
				<AddChapters records={chapters} />
			</div>
			<div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
				{isListFetching && <LoadingChapters />}
				{!isListFetching && (
					<SortableTree
						hideAdd
						treeData={treeData}
						onTreeDataChange={(data: TreeData<TChapterRecord>) => {
							const from = data?.map((item) => ({
								id: item?.id,
								children: item?.children?.map((child) => child?.id),
								collapsed: false,
							}))
							const to = treeData?.map((item) => ({
								id: item?.id,
								children: item?.children?.map((child) => child?.id),
								collapsed: false,
							}))
							const isEqual = _isEqual(from, to)
							setTreeData(data)
							if (!isEqual) {
								handleSave(data)
							}
						}}
						renderContent={(node) => {
							return (
								<NodeRender
									node={node}
									setSelectedChapter={setSelectedChapter}
								/>
							)
						}}
						indentationWidth={48}
						sortableRule={({ activeNode, projected }) => {
							const activeNodeHasChild = !!activeNode.children.length
							const sortable = projected?.depth <= (activeNodeHasChild ? 0 : 1)
							if (!sortable) message.error('超過最大深度，無法執行')
							return sortable
						}}
					/>
				)}

				{selectedChapter && <ChapterEdit record={selectedChapter} />}
			</div>
		</>
	)
}

export const SortableChapters = memo(SortableChaptersComponent)
