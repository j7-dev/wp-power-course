import React, { useEffect, memo } from 'react'
import { Form, InputNumber, Input, DatePickerProps, Select } from 'antd'
import { FiSwitch, DatePicker, RangePicker } from '@/components/formItem'
import dayjs from 'dayjs'
import { PRODUCT_TYPE_OPTIONS } from '@/components/course/form/CourseBundles/Edit/utils'
import ProductPriceFields from './ProductPriceFields'

const { Item } = Form

const CoursePriceComponent = () => {
	const form = Form.useFormInstance()
	const watchIsFree = Form.useWatch(['is_free'], form) === 'yes'
	const watchDateOnSaleFrom = Form.useWatch(['date_on_sale_from'], form)

	const disabledDate: DatePickerProps['disabledDate'] = (current) => {
		if (watchDateOnSaleFrom) {
			return current && current < dayjs.unix(watchDateOnSaleFrom).startOf('day')
		}
		return false
	}

	useEffect(() => {
		if (watchIsFree) {
			form.setFieldsValue({
				regular_price: 0,
				sale_price: 0,
				date_on_sale_from: undefined,
				date_on_sale_to: undefined,
			})
		}
	}, [watchIsFree])
	return (
		<>
			<div className="grid grid-cols-3 gap-6">
				<div>
					<Item
						name={['type']}
						label="課程商品種類"
						initialValue={PRODUCT_TYPE_OPTIONS[0].value}
					>
						<Select options={PRODUCT_TYPE_OPTIONS} />
					</Item>
					<ProductPriceFields />
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
