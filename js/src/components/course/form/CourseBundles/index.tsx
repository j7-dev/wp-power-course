import React, { useRef, useEffect, memo } from 'react'
import { Button, Form, Empty } from 'antd'
import { SortableList, SortableListRef } from '@ant-design/pro-editor'
import { RenderItem } from '@ant-design/pro-editor/es/SortableList/type'
import { useList, useCreate, useParsed } from '@refinedev/core'
import { TProductRecord } from '@/components/product/ProductTable/types'
import Item from './Item'
import dayjs, { Dayjs } from 'dayjs'
import { toFormData } from '@/utils'

export type TRenderItemOptions = Parameters<RenderItem<TProductRecord>>[1]

const LoadingItems = () => {
	return (
		<div className="h-[4.5rem] mb-1 bg-gray-100 rounded-md animate-pulse" />
	)
}

const CourseBundlesComponent = () => {
	const { id: courseId } = useParsed()
	const form = Form.useFormInstance()

	const { data, isFetching, isLoading } = useList<TProductRecord>({
		resource: 'bundle_products',
		filters: [
			{
				field: 'meta_key',
				operator: 'eq',
				value: 'link_course_ids',
			},
			{
				field: 'meta_value',
				operator: 'eq',
				value: courseId,
			},
			{
				field: 'type',
				operator: 'eq',
				value: 'power_bundle_product',
			},
		],
		pagination: {
			pageSize: -1,
		},
		queryOptions: {
			enabled: !!courseId,
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

	const { mutate: create, isLoading: isCreating } = useCreate()
	const handleCreate = () => {
		const values = {
			name: '銷售方案',
			product_type: 'power_bundle_product', // 創建綑綁商品
			link_course_ids: [courseId],
		}

		const formData = toFormData(values)

		create({
			resource: 'bundle_products',
			values: formData,
			invalidates: ['list'],
		})
	}

	return (
		<>
			<div className="gap-6 p-6">
				<Button type="primary" onClick={handleCreate} loading={isCreating}>
					新增
				</Button>

				{isLoading &&
					new Array(4)
						.fill(null)
						.map((_, index) => <LoadingItems key={index} />)}

				{!isLoading && (
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
							renderItem={(
								item: TProductRecord,
								options: TRenderItemOptions,
							) => <Item record={item} options={options} />}
						/>
					</div>
				)}
			</div>
		</>
	)
}

export const CourseBundles = memo(CourseBundlesComponent)
