import { useState, useEffect, memo } from 'react'
import { SortableTree, TreeData } from '@ant-design/pro-editor'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { Form, message, Button } from 'antd'
import NodeRender from './NodeRender'
import { chapterToTreeNode, treeToParams } from './utils'
import {
	useCustomMutation,
	useApiUrl,
	useInvalidate,
	useList,
	HttpError,
	useDeleteMany,
} from '@refinedev/core'
import { isEqual as _isEqual } from 'lodash-es'
import { ChapterEdit } from '@/components/chapters'
import { PopconfirmDelete } from '@/components/general'

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
	const {
		data: chaptersData,
		isFetching: isListFetching,
		isLoading: isListLoading,
	} = useList<TChapterRecord, HttpError>({
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
	const { mutate } = useCustomMutation()

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
						resource: 'chapters',
						invalidates: ['list'],
					})
				},
			},
		)
	}

	const [selectedChapter, setSelectedChapter] = useState<TChapterRecord | null>(
		null,
	)

	const [selectedIds, setSelectedIds] = useState<string[]>([]) // 批量刪除選中的 ids

	const { mutate: deleteMany, isLoading: isDeleteManyLoading } = useDeleteMany()

	useEffect(() => {
		// 每次重新排序後，重新取得章節後，重新 set 選擇的章節
		if (!isListFetching) {
			const flattenChapters = chapters.reduce((acc, c) => {
				acc.push(c)
				if (c?.chapters) {
					acc.push(...c?.chapters)
				}
				return acc
			}, [] as TChapterRecord[])

			setSelectedChapter(
				flattenChapters.find((c) => c.id === selectedChapter?.id) || null,
			)
		}
	}, [isListFetching])

	return (
		<>
			<div className="mb-8 flex gap-x-4 justify-between items-center">
				<AddChapters records={chapters} />
				<Button
					type="default"
					className="relative top-1"
					disabled={!selectedIds.length}
					onClick={() => setSelectedIds([])}
				>
					清空選取
				</Button>
				<PopconfirmDelete
					popconfirmProps={{
						onConfirm: () =>
							deleteMany(
								{
									resource: 'chapters',
									ids: selectedIds,
									mutationMode: 'optimistic',
								},
								{
									onSuccess: () => {
										setSelectedIds([])
									},
								},
							),
					}}
					buttonProps={{
						type: 'primary',
						danger: true,
						className: 'relative top-1',
						loading: isDeleteManyLoading,
						disabled: !selectedIds.length,
						children: `批量刪除 ${selectedIds.length ? `(${selectedIds.length})` : ''}`,
					}}
				/>
			</div>
			<div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
				{isListLoading && <LoadingChapters />}
				{!isListLoading && (
					<SortableTree
						hideAdd
						hideRemove
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
						renderContent={(node) => (
							<NodeRender
								node={node}
								selectedChapter={selectedChapter}
								setSelectedChapter={setSelectedChapter}
								selectedIds={selectedIds}
								setSelectedIds={setSelectedIds}
							/>
						)}
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
