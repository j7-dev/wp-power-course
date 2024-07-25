import { FC } from 'react'
import { PlusCircleFilled } from '@ant-design/icons'
import { Tooltip, Button } from 'antd'
import {
	TCourseRecord,
	TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import {
	BaseRecord,
	HttpError,
	useCreate,
	useInvalidate,
} from '@refinedev/core'

type TCreateParams = {
	post_parent: string
	post_title: string
	menu_order: number
}

const AddChapter: FC<{
	record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
	const { mutate, isLoading } = useCreate<
		BaseRecord,
		HttpError,
		TCreateParams
	>()
	const invalidate = useInvalidate()
	const { type, depth } = record
	const isChapter = type === 'chapter'
	const itemLabel = !isChapter ? '章節' : '單元'

	const expandRow = (id: string) => {
		/** 每個 tr 身上都有 data-row-key="${id}" */
		const trNode = document.querySelector(`tr[data-row-key="${id}"]`)

		/** 找到展開的 button 節點 class ant-table-row-expand-icon-collapsed */
		const expandIcon = trNode?.querySelector(
			'.ant-table-row-expand-icon-collapsed',
		) as HTMLButtonElement | null

		/** 有找到就實現點擊，這邊不用授控的方式去空展開列表是因為效能太差了 */
		if (expandIcon) {
			expandIcon.click()
		}
	}

	const handleCreate = () => {
		mutate(
			{
				resource: 'chapters',
				values: {
					post_parent: record.id,
					post_title: `新${itemLabel}`,
					menu_order: (record?.chapters || []).length + 1,
				},
			},
			{
				onSuccess: (_data, variables) => {
					const id: string = variables?.values?.post_parent
					invalidate({
						resource: 'courses',
						invalidates: ['list'],
					})

					/** 對於初次新增章節的課程來說，是沒有展開的 button 的，要等 fetch List 後才會有，所以等候 2.5 秒  */
					setTimeout(() => {
						expandRow(id)
					}, 2500)
				},
			},
		)
	}

	if (depth >= 1) return null

	return (
		<>
			<Tooltip title={`新增${itemLabel}`}>
				<Button
					loading={isLoading}
					type="link"
					size="small"
					className="mx-0"
					icon={<PlusCircleFilled className="text-gray-400 cursor-pointer" />}
					onClick={handleCreate}
				/>
			</Tooltip>
		</>
	)
}

export default AddChapter
