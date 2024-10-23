import React, { useRef, useEffect, memo, useState } from 'react'
import { Button, Form, Empty } from 'antd'
import { SortableList, SortableListRef } from '@ant-design/pro-editor'
import { RenderItem } from '@ant-design/pro-editor/es/SortableList/type'
import { useList, useCreate, useParsed, useOne } from '@refinedev/core'
import { TProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import ListItem from './ListItem'
import { toFormData } from '@/utils'
import { EditBundle } from './Edit'

export type TRenderItemOptions = Parameters<RenderItem<TProductRecord>>[1]

const { Item } = Form
const LoadingItems = ({ className }: { className?: string }) => (
	<div>
		{new Array(4).fill(null).map((_, index) => (
			<div
				key={index}
				className={`h-[4.5rem] mb-1 bg-gray-100 rounded-md animate-pulse ${className}`}
			/>
		))}
	</div>
)

const CourseBundlesComponent = () => {
	const { id: courseId } = useParsed()
	const { data: courseData } = useOne<TCourseRecord>({
		resource: 'courses',
		id: courseId,
		meta: {
			label: '課程列表',
			id: courseId,
		},
	})
	const course = courseData?.data

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

	// 創建
	const { mutate: create, isLoading: isCreating } = useCreate()
	const handleCreate = () => {
		const values = {
			status: 'draft',
			bundle_type: 'bundle',
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

	// 選中的商品
	const [selectedProduct, setSelectedProduct] = useState<TProductRecord | null>(
		null,
	)

	return (
		<>
			<div className="gap-6 p-6">
				<Item name={['bundle_ids']} hidden />
				<div className="mb-8">
					<Button type="primary" onClick={handleCreate} loading={isCreating}>
						新增
					</Button>
				</div>

				<div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
					{isLoading && <LoadingItems />}
					{!isLoading && (
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
							) => (
								<ListItem
									record={item}
									options={options}
									setSelectedProduct={setSelectedProduct}
								/>
							)}
						/>
					)}

					{selectedProduct && course && (
						<EditBundle record={selectedProduct} course={course} />
					)}
				</div>
			</div>
		</>
	)
}

export const CourseBundles = memo(CourseBundlesComponent)
