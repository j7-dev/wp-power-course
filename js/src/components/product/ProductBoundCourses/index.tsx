import React, { memo, FC } from 'react'
import { TProductRecord } from '@/components/product/ProductTable/types'
import { Typography, Tag } from 'antd'
import dayjs from 'dayjs'

const { Text } = Typography

const LIMIT_UNIT_LABEL = {
	day: '日',
	month: '月',
	year: '年',
}

const getLimitLabel = (
	limit_type: string,
	limit_value: number,
	limit_unit: string,
) => {
	switch (limit_type) {
		case 'unlimited':
			return '無期限'
		case 'fixed':
			return `訂單完成後 ${limit_value} ${LIMIT_UNIT_LABEL?.[limit_unit as keyof typeof LIMIT_UNIT_LABEL] || ''}`
		case 'assigned':
			return `至 ${dayjs.unix(limit_value).format('YYYY/MM/DD HH:mm')}`
	}
}

const ProductBoundCoursesComponent: FC<{
	record: TProductRecord
}> = ({ record }) => {
	const { bind_courses_data = [] } = record
	return bind_courses_data.map(
		({ id, name, limit_type, limit_value, limit_unit }) => {
			return (
				<div key={id} className="grid grid-cols-[12rem_8rem] gap-1 my-1">
					<div>
						<Text
							ellipsis={{
								tooltip: (
									<>
										<sub className="text-gray-500">#{id}</sub>{' '}
										{name || '未知的課程名稱'}
									</>
								),
							}}
						>
							<sub className="text-gray-500">#{id}</sub>{' '}
							{name || '未知的課程名稱'}
						</Text>
					</div>

					<div>
						<Tag>{getLimitLabel(limit_type, limit_value, limit_unit)}</Tag>
					</div>
				</div>
			)
		},
	)
}

export const ProductBoundCourses = memo(ProductBoundCoursesComponent)
