import React from 'react'
import Simple from '@/components/course/form/CoursePrice/ProductPriceFields/Simple'
import Subscription from '@/components/course/form/CoursePrice/ProductPriceFields/Subscription'
import { Form } from 'antd'
import { useParsed } from '@refinedev/core'
import { INCLUDED_PRODUCT_IDS_FIELD_NAME } from '../utils'

const { Item } = Form

const ProductPriceFields = ({
	bundlePrices,
}: {
	bundlePrices: { regular_price: React.ReactNode; sale_price: React.ReactNode }
}) => {
	const bundleProductForm = Form.useFormInstance()
	const watchProductType = Form.useWatch(['type'], bundleProductForm)

	const watchRegularPrice = Number(
		Form.useWatch(['regular_price'], bundleProductForm),
	)
	const watchSalePrice = Number(
		Form.useWatch(['sale_price'], bundleProductForm),
	)

	const { id: courseId } = useParsed()

	if ('subscription' === watchProductType) {
		return (
			<>
				<Item
					name="bind_course_ids"
					label="綁定課程"
					initialValue={[courseId]}
					hidden
				/>
				<Item
					name={INCLUDED_PRODUCT_IDS_FIELD_NAME}
					label="連接商品"
					initialValue={[]}
					hidden
				/>
				<Subscription />
			</>
		)
	}

	const { regular_price: bundleRegularPrice, sale_price: bundleSalePrice } =
		bundlePrices

	return (
		<Simple
			regularPriceItemProps={{
				hidden: true,
				label: '此銷售組合原價',
			}}
			salePriceItemProps={{
				label: '方案折扣價',
				help: (
					<div className="mb-4">
						<div className="grid grid-cols-2 gap-x-4">
							<div>此銷售組合原訂原價</div>
							<div className="text-right pr-0">{bundleRegularPrice}</div>
							<div>此銷售組合原訂折扣價</div>
							<div className="text-right pr-0">{bundleSalePrice}</div>
						</div>
						{watchSalePrice > watchRegularPrice && (
							<p className="text-red-500 m-0">折扣價超過原價</p>
						)}
					</div>
				),
				rules: [
					{
						required: true,
						message: '請輸入折扣價',
					},
				],
			}}
		/>
	)
}

export default ProductPriceFields
