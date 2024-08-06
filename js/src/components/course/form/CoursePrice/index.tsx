import React, { useEffect } from 'react'
import { Form, InputNumber, Input, DatePickerProps } from 'antd'
import { FiSwitch, DatePicker } from '@/components/formItem'
import dayjs from 'dayjs'

const { Item } = Form

// TODO 把日期改成 Range Picker

export const CoursePrice = () => {
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
		<div className="grid grid-cols-2 gap-4">
			<Item
				name={['regular_price']}
				label="原價"
				initialValue={0}
				rules={[
					{
						required: true,
						message: '請輸入原價',
					},
				]}
			>
				<InputNumber className="w-full" min={0} disabled={watchIsFree} />
			</Item>
			<Item name={['sale_price']} label="折扣價">
				<InputNumber className="w-full" min={0} disabled={watchIsFree} />
			</Item>

			<DatePicker
				formItemProps={{
					name: ['date_on_sale_from'],
					label: '折扣價開始時間',
				}}
				datePickerProps={{
					disabled: watchIsFree,
				}}
			/>

			<DatePicker
				formItemProps={{
					name: ['date_on_sale_to'],
					label: '折扣價結束時間',
				}}
				datePickerProps={{
					disabled: watchIsFree,
					disabledDate,
				}}
			/>

			<FiSwitch
				formItemProps={{
					name: ['is_free'],
					label: '這是免費課程',
				}}
			/>

			<Item name={['purchase_note']} label="購買備註">
				<Input.TextArea rows={4} />
			</Item>
		</div>
	)
}
