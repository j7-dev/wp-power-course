import React, { FC } from 'react'
import {
	TCourseRecord,
	TChapterRecord,
} from '@/pages/admin/Courses/CourseTable/types'

import { TTerm, TProductRecord } from '@/components/product/ProductTable/types'
import { Tag } from 'antd'

export const ProductCat: FC<{
	record: TProductRecord | TCourseRecord | TChapterRecord
}> = ({ record }) => {
	const { categories = [], tags = [] } = record as TCourseRecord

	return (
		<>
			<div>
				{(categories as TTerm[])?.map(({ id, name }) => {
					return (
						<Tag key={id} color="blue" bordered={false} className="mb-1 mr-1">
							{name}
						</Tag>
					)
				})}
			</div>
			<div>
				{(tags as TTerm[])?.map(({ id, name }) => {
					return (
						<span key={id} className="text-gray-400 text-xs mr-1 mb-1">
							#{name}
						</span>
					)
				})}
			</div>
		</>
	)
}
