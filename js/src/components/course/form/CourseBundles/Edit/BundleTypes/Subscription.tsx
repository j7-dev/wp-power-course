import React, { memo, useEffect } from 'react'
import { Form, InputNumber, Select, Space } from 'antd'
import { useParsed } from '@refinedev/core'
import { INCLUDED_PRODUCT_IDS_FIELD_NAME } from '../utils'

const { Item } = Form

enum SUBSCRIPTION {
	PRICE = '_subscription_price', // 訂閱價格每 M 個月 [N 元] - number
	PERIOD_INTERVAL = '_subscription_period_interval', // 訂閱價格每 [M 個] 月 N 元 - number
	PERIOD = '_subscription_period', // 訂閱價格每 M 個 [月] N 元 - day | week | month | year
	LENGTH = '_subscription_length', // 續訂截止日，0 = 無期限 - number
	SIGN_UP_FEE = '_subscription_sign_up_fee', // 註冊費 - number
	TRIAL_LENGTH = '_subscription_trial_length', // 免費試用 [N] 天 - number
	TRIAL_PERIOD = '_subscription_trial_period', // 免費試用 N [天] - day | week | month | year
	// LIMIT = '_subscription_limit', // 續訂限制 - number
	// ONE_TIME_SHIPPING = '_subscription_one_time_shipping', // 一次性運費 - number
}

const PERIOD_OPTIONS = [
	{
		value: 'day',
		label: '天',
	},
	{
		value: 'week',
		label: '週',
	},
	{
		value: 'month',
		label: '月',
	},
	{
		value: 'year',
		label: '年',
	},
]

const Subscription = () => {
	const { id: courseId } = useParsed()
	const form = Form.useFormInstance()
	const watchPeriod = Form.useWatch(SUBSCRIPTION.PERIOD, form)
	const watchPeriodLabel = watchPeriod
		? PERIOD_OPTIONS.find((option) => option.value === watchPeriod)?.label
		: ''
	const lengthOptions = new Array(30).fill(0).map((_, index) => ({
		value: index,
		label: index ? `${index} ${watchPeriodLabel}` : '直到取消為止',
	}))

	const watchPrice = Form.useWatch(SUBSCRIPTION.PRICE, form)

	useEffect(() => {
		form.setFieldValue('regular_price', watchPrice)
	}, [watchPrice])

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
			<Item name={['regular_price']} label="原價" hidden />

			<div className="mb-6">
				<label className="tw-block mb-2">訂閱價格</label>
				<Space.Compact block>
					<Item name={SUBSCRIPTION.PRICE} noStyle rules={[{ required: true }]}>
						<InputNumber className="w-[37%]" addonAfter="元" />
					</Item>
					<Item name={SUBSCRIPTION.PERIOD_INTERVAL} noStyle initialValue={1}>
						<InputNumber
							className="w-[37%]"
							addonBefore="每"
							addonAfter="個"
							min={1}
						/>
					</Item>
					<Item name={SUBSCRIPTION.PERIOD} initialValue="month" noStyle>
						<Select options={PERIOD_OPTIONS} className="w-[26%]" />
					</Item>
				</Space.Compact>
			</div>

			<Item
				name={SUBSCRIPTION.LENGTH}
				label="續訂截止日(扣款期數)"
				initialValue={0}
			>
				<Select options={lengthOptions} />
			</Item>

			<Item name={SUBSCRIPTION.SIGN_UP_FEE} label="註冊費 (NT$)">
				<InputNumber className="w-full" />
			</Item>

			<div className="mb-6">
				<label className="tw-block mb-2">免費試用</label>
				<Space.Compact block>
					<Item name={SUBSCRIPTION.TRIAL_LENGTH} noStyle>
						<InputNumber className="w-[74%]" />
					</Item>
					<Item name={SUBSCRIPTION.TRIAL_PERIOD} initialValue="day" noStyle>
						<Select options={PERIOD_OPTIONS} className="w-[26%]" />
					</Item>
				</Space.Compact>
			</div>
		</>
	)
}

export default memo(Subscription)
