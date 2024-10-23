import React, { memo } from 'react'
import { Button, Form, Drawer } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
import { useBundleFormDrawer } from '@/hooks'
import BundleForm from './BundleForm'
import { useList } from '@refinedev/core'
import { TProductRecord } from '@/components/product/ProductTable/types'
import ProductCheckCard from './ProductCheckCard'
import { coursesAtom } from '@/pages/admin/Courses/List'
import { useAtomValue } from 'jotai'

/**
 * TODO
 * 改版
 */
const CourseBundleComponent = () => {
	const form = Form.useFormInstance()
	const watchCourseId: string = Form.useWatch(['id'], form) || []
	const courses = useAtomValue(coursesAtom)
	const selectedCourse = courses.find(({ id }) => id === watchCourseId)
	const bundleIds: string[] = Form.useWatch(['bundle_ids'], form) || []
	const [bundleProductForm] = Form.useForm()
	const { drawerProps, show, open, record } = useBundleFormDrawer({
		form: bundleProductForm,
		resource: 'bundle_products',
	})

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
				value: '100',
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

	return (
		<>
			<Button type="primary" icon={<PlusOutlined />} onClick={show()}>
				新增銷售方案
			</Button>
			<div className="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-x-4">
				{!isFetching &&
					!!bundleIds.length &&
					bundleProducts?.map((bundleProduct) => (
						<ProductCheckCard
							key={bundleProduct.id}
							product={bundleProduct}
							show={show}
						/>
					))}

				{isFetching &&
					bundleIds.map((id) => (
						<div
							key={id}
							className="p-4 border border-solid border-gray-200 rounded-md animate-pulse"
						>
							<div className="aspect-video w-full rounded bg-slate-300 mb-2" />
							<div className="mb-2 h-3 bg-slate-300 w-3/4" />
							<div className="mb-2 h-2 bg-slate-300 w-12" />
							<div className="mb-2 h-3 bg-slate-300 w-1/2" />
							<div className="mb-2 h-3 bg-slate-300 w-full" />
						</div>
					))}
			</div>

			<Drawer {...drawerProps}>
				<BundleForm
					form={bundleProductForm}
					course={selectedCourse}
					record={record}
				/>
			</Drawer>
		</>
	)
}

export const CourseBundle = memo(CourseBundleComponent)
