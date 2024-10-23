import React, { useRef, useEffect, memo } from 'react'
import { Button, Form, Empty } from 'antd'
import { SortableList, SortableListRef } from '@ant-design/pro-editor'
import { RenderItem } from '@ant-design/pro-editor/es/SortableList/type'
import { HolderOutlined, DeleteOutlined } from '@ant-design/icons'
import { useList } from '@refinedev/core'
import { TProductRecord } from '@/components/product/ProductTable/types'
import Item from './Item'

export type TRenderItemOptions = Parameters<RenderItem<TProductRecord>>[1]

const CourseBundlesComponent = () => {
	const form = Form.useFormInstance()
	const bundleIds: string[] = form.getFieldValue(['bundle_ids']) || []

	const { data, isFetching } = useList<TProductRecord>({
		resource: 'products',
		filters: [
			{
				field: 'include',
				operator: 'eq',
				value: bundleIds,
			},

			{
				field: 'status',
				operator: 'eq',
				value: 'any',
			},
			{
				field: 'posts_per_page',
				operator: 'eq',
				value: '-1',
			},
			{
				field: 'type',
				operator: 'eq',
				value: 'power_bundle_product',
			},
		],
		queryOptions: {
			enabled: !!bundleIds.length,
			staleTime: 0,
			cacheTime: 0,
		},
	})

	const bundleProducts = data?.data || []

	const ref = useRef<SortableListRef>(null)

	useEffect(() => {
		if (!isFetching) {
			form.setFieldValue(
				['bundle_ids'],
				bundleProducts.map(({ id }) => id),
			)
		}
	}, [isFetching])

	return (
		<>
			<div className="gap-6 p-6">
				<Button type="primary">新增</Button>

				<div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
					<SortableList<TProductRecord>
						value={bundleProducts}
						ref={ref}
						onChange={(newList) => {
							console.log('⭐  newList:', newList)

							// TODO 修改每個 bundle product 的 menu order
						}}
						getItemStyles={() => ({ padding: '16px' })}
						renderEmpty={() => <Empty description="目前沒有銷售方案" />}
						renderItem={(item: TProductRecord, options: TRenderItemOptions) => (
							<Item record={item} options={options} />
						)}
					/>
				</div>
			</div>
		</>
	)
}

export const CourseBundles = memo(CourseBundlesComponent)
