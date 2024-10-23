import React, { memo } from 'react'
import { TProductRecord } from '@/components/product/ProductTable/types'
import {
	ProductName,
	ProductPrice,
	ProductTotalSales,
} from '@/components/product'
import { getPostStatus, siteUrl, course_permalink_structure } from '@/utils'
import { Tag } from 'antd'
import { TRenderItemOptions } from '../index'
import { HolderOutlined } from '@ant-design/icons'
import { PopconfirmDelete } from '@/components/general'
import { useDelete } from '@refinedev/core'

const Item = ({
	record,
	options,
}: {
	record: TProductRecord
	options: TRenderItemOptions
}) => {
	const { id, type, slug, status } = record
	const { index, listeners } = options
	const { mutate: deleteProduct } = useDelete()

	return (
		<div className="grid gap-x-2 grid-cols-[1rem_1fr_3rem_2rem_6rem_1rem] w-full">
			<div className="self-center">
				<HolderOutlined
					className="cursor-grab hover:bg-gray-200 rounded-lg py-3 px-0.5"
					{...listeners}
				/>
			</div>

			<div className="self-center">
				<ProductName
					record={record}
					onClick={
						'variation' === type
							? undefined
							: () => {
									window.open(
										`${siteUrl}/${course_permalink_structure}/${slug}`,
										'_blank',
									)
								}
					}
				/>
			</div>

			<div className="self-center">
				<Tag color={getPostStatus(status)?.color}>
					{getPostStatus(status)?.label}
				</Tag>
			</div>

			<div className="self-center place-self-center">
				<ProductTotalSales record={record} />
			</div>

			<div className="self-center">
				<ProductPrice record={record} />
			</div>

			<div className="self-center">
				<PopconfirmDelete
					type="icon"
					popconfirmProps={{
						title: '確認刪除這個銷售方案嗎?',
						onConfirm: () =>
							deleteProduct({
								resource: 'products',
								id,
								mutationMode: 'undoable',
							}),
					}}
				/>
			</div>
		</div>
	)
}

export default memo(Item)
