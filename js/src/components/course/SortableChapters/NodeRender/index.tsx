import { FC } from 'react'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { getPostStatus } from '@/utils'
import { FlattenNode } from '@ant-design/pro-editor'
import { ChapterName } from '@/components/course'
import { SecondToStr } from '@/components/general'
import AddChapter from '@/components/product/ProductAction/AddChapter'

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

	const depth = node?.depth || 0
	const showPlaceholder = node?.children?.length === 0
	return (
		<div className="flex gap-4 justify-start items-center">
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
			{depth === 0 && (
				<AddChapter
					record={record}
					buttonProps={{
						type: 'primary',
						children: '新增單元',
						icon: null,
					}}
				/>
			)}
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
