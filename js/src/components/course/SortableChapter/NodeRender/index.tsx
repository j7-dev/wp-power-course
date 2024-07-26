import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { getPostStatus } from '@/utils'
import { Tag } from 'antd'
import { FlattenNode } from '@ant-design/pro-editor'
import { ChapterName, ChapterLength } from '@/components/course'
import AddChapter from '@/components/product/ProductAction/AddChapter'

const NodeRender: FC<{
	node: FlattenNode<TChapterRecord>
	record: TChapterRecord
	show: (_record: TChapterRecord | undefined) => () => void
	loading: boolean
}> = ({ node, record, show, loading }) => {
	const depth = node?.depth || 0
	const showPlaceholder = node?.children?.length === 0
	return (
		<div className="flex gap-4 justify-start items-center">
			<div className="flex items-end">
				{showPlaceholder && <div className="w-[28px] h-[28px]"></div>}
				<ChapterName record={record} show={show} loading={loading} />
			</div>
			<div>
				<Tag color={getPostStatus(record?.status)?.color}>
					{getPostStatus(record?.status)?.label}
				</Tag>
			</div>
			<div>
				<ChapterLength record={record} />
			</div>
			{depth === 0 && <AddChapter record={record} />}
			{/* <ProductType record={record} /> */}
			{/* <ProductPrice record={record} />
      <ProductTotalSales record={record} />
      <ProductCat record={record} />
      <ProductStock record={record} /> */}
			{/* <ProductAction record={record} /> */}
		</div>
	)
}

export default NodeRender
