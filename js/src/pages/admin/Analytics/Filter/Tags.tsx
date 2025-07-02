import React from 'react'
import { Tag, Form, FormItemProps } from 'antd'
import { uniq } from 'lodash-es'
import { TProductSelectOption } from '@/components/product/ProductTable/types'
import { useRecord } from '@/pages/admin/Courses/Edit/hooks'

const Tags = ({
	products,
	isLoading,
}: {
	products: TProductSelectOption[]
	isLoading: boolean
}) => {
	const form = Form.useFormInstance()
	const watchProducts = Form.useWatch(['products'], form) || []
	const watchBundleProducts = Form.useWatch(['bundle_products'], form) || []
	const selectedProductIds = uniq([
		...watchProducts,
		...watchBundleProducts,
	])

	const handleClick = (value: string, name: string) => () => {
		const isInclude = selectedProductIds.includes(value)
		const ids: string[] =
			name === 'products' ? watchProducts : watchBundleProducts

		if (isInclude) {
			form.setFieldValue([name], uniq(ids.filter((v) => v !== value)))
		} else {
			form.setFieldValue([name], uniq([...ids, value]))
		}
	}

	const course = useRecord()

	return (
		<div className="mb-4">
			<Tag
				className="cursor-pointer mr-2"
				color={selectedProductIds.includes(course?.id) ? 'blue' : 'default'}
				key={course?.id}
				onClick={handleClick(course?.id as string, 'products')}
				bordered={true}
			>
				{course?.name}
			</Tag>
			{products?.map(({ id, name }) => (
				<Tag
					className="cursor-pointer mr-2"
					color={selectedProductIds.includes(id) ? 'blue' : 'default'}
					key={id}
					onClick={handleClick(id as string, 'bundle_products')}
					bordered={true}
				>
					{name}
				</Tag>
			))}
		</div>
	)
}

export default Tags
