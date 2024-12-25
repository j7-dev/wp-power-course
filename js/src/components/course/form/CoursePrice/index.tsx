import React, { useEffect, memo } from 'react'
import { Form, Input, Select, InputNumber } from 'antd'
import { FiSwitch, RangePicker } from '@/components/formItem'
import { PRODUCT_TYPE_OPTIONS } from '@/components/course/form/CourseBundles/Edit/utils'
import SubscriptionPriceFields from '@/components/course/form/CoursePrice/ProductPriceFields/Subscription'
import SimplePriceFields from '@/components/course/form/CoursePrice/ProductPriceFields/Simple'
import { TCoursesLimit } from '@/pages/admin/Courses/List/types'

const { Item } = Form

const CoursePriceComponent = () => {
	const form = Form.useFormInstance()
	const watchIsFree = Form.useWatch(['is_free'], form) === 'yes'

	useEffect(() => {
		if (watchIsFree) {
			form.setFieldsValue({
				regular_price: 0,
				sale_price: 0,
				sale_date_range: undefined,
			})
		}
	}, [watchIsFree])

	// 如果觀看期限選擇「跟隨訂閱」，則課程商品種類只能選擇「訂閱」
	const watchLimitType: TCoursesLimit['limit_type'] = Form.useWatch(
		['limit_type'],
		form,
	)
	const productTypeOptions = PRODUCT_TYPE_OPTIONS.map((option) => ({
		...option,
		disabled:
			option.value !== 'subscription' &&
			watchLimitType === 'follow_subscription',
	}))

	useEffect(() => {
		if (watchLimitType === 'follow_subscription') {
			form.setFieldsValue({ type: 'subscription' })
		}
	}, [watchLimitType])

	const watchProductType = Form.useWatch(['type'], form)
	const isSubscription = watchProductType === 'subscription'

	return (
		<>
			<div className="grid grid-cols-3 gap-6">
				<div>
					<Item
						name={['type']}
						label="課程商品種類"
						initialValue={productTypeOptions[0].value}
					>
						<Select options={productTypeOptions} />
					</Item>

					{isSubscription && <SubscriptionPriceFields />}

					<SimplePriceFields />
				</div>
				<div>
					<Item name={['purchase_note']} label="購買備註">
						<Input.TextArea rows={6} />
					</Item>
					<div className="grid grid-cols-2 gap-4">
						<FiSwitch
							formItemProps={{
								name: ['is_free'],
								label: '這是免費課程',
							}}
						/>
						<FiSwitch
							formItemProps={{
								name: ['hide_single_course'],
								label: '隱藏購買單堂課',
							}}
						/>
					</div>
				</div>
			</div>
		</>
	)
}

export const CoursePrice = memo(CoursePriceComponent)
