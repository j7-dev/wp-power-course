import { memo } from 'react'
import { Typography } from 'antd'
import { SimpleImage, Heading } from '@/components/general'
import shortcode_courses from '@/assets/images/shortcode_courses.jpg'

const { Text } = Typography

const EXAMPLES = [
	{
		code: '[pc_courses]',
		title: '預設',
		description: '預設 3 欄顯示，抓取 12 個課程，由最新排序到最舊',
	},
	{
		code: '[pc_courses columns="4"]',
		title: '4 欄顯示',
		description: '範例為顯示 4 欄佈局，支援 2,3,4 欄顯示',
	},
	{
		code: '[pc_courses include="2030,2035,2066"]',
		title: '只顯示包含的課程 id',
		description: '範例為只顯示 2030,2035,2066 的課程',
	},
	{
		code: '[pc_courses include="2030,2035,2066"]',
		title: '只顯示包含的課程 id',
		description: '範例為只顯示 2030,2035,2066 的課程',
	},
	{
		code: '[pc_courses limit="4"]',
		title: '抓取指定數量課程',
		description: '範例為緊抓取 4 個課程，limit="-1" 時為抓取所有課程',
	},
	{
		code: '[pc_courses order="ASC" orderby="modified"]',
		title: '排序調整',
		description:
			'範例為由修改時間最新排序到最舊，order 支援 ASC, DESC，orderby 支援 none, ID, name, type, rand, date, modified',
	},

	{
		code: '[pc_courses tag="tag1,tag2" category="category1,category2"]',
		title: '篩選指定標籤或分類的課程',
		description:
			'範例為篩選 tag1,tag2 標籤，或 category1,category2 分類的課程，其中 tag1, tag2 與 category1, category2 皆為 slug',
	},
]

const index = () => {
	return (
		<div className="grid grid-cols-[1fr_3fr] gap-8">
			<div>
				<Heading className="mt-8">課程列表</Heading>

				{EXAMPLES.map(({ code, title, description }) => (
					<div key="code" className="mb-8">
						<p className="font-bold text-base mb-2">{title}</p>
						<p className="text-xs mb-2">{description}</p>
						<Text className="text-base" copyable code>
							{code}
						</Text>
					</div>
				))}
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto">
				<p className="font-bold mb-4">外觀</p>
				<div className="mb-4">
					<SimpleImage
						src={shortcode_courses}
						ratio="aspect-[3.1]"
						className="w-full"
					/>
				</div>
			</div>
		</div>
	)
}

export default memo(index)
