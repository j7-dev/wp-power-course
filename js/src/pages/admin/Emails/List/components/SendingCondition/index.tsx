import React, { useEffect } from 'react'
import { EmailComponentProps } from '@/pages/AdminApp/Dashboard/EmailSetting/types'
import { Select, Form, InputNumber, Space, Tooltip } from 'antd'
import { InfoCircleFilled } from '@ant-design/icons'
import { focusEmailIndexAtom } from '@/pages/AdminApp/Dashboard/EmailSetting/atom'
import { useSetAtom } from 'jotai'

const { Item } = Form

// @param string $date_type 'date_created', 'trial_end', 'next_payment', 'last_order_date_created', 'end' or 'end_of_prepaid_term'

const actions = [
	{
		label: '下單開站後',
		value: 'site_sync',
		helper: '下單開站後馬上發送',
	},
	{
		label: '客戶續訂失敗後',
		value: 'subscription_failed',
		helper: '續訂失敗後 N 天發送',
	},
	{
		label: '客戶續訂成功後',
		value: 'subscription_success',
		helper: '續訂成功後 N 天發送',
	},
	{
		label: '下次付款(含成功&失敗)',
		value: 'next_payment',
		helper: '續訂不論成功或失敗 前/後 N 天發送',
	},
	{
		label: '訂閱成立(即第一個訂單成立)',
		value: 'date_created',
		helper: '訂閱成立後 N 天發送',
	},
	{
		label: '試用期結束',
		value: 'trial_end',
		helper: '試用期結束 前/後 N 天發送',
	},
	{
		label: '上次續訂訂單日期',
		value: 'last_order_date_created',
		helper: '上次續訂訂單日期後 N 天發送',
	},
]

const actionNameOptions = actions.map(({ label, value }) => ({
	label,
	value,
}))

export const SendingCondition = ({
	record,
	index,
	containerRef,
}: EmailComponentProps) => {
	const form = Form.useFormInstance()
	const setFocusEmailIndex = useSetAtom(focusEmailIndexAtom)
	const actionNameName = ['emails', index, 'action_name']
	const daysName = ['emails', index, 'days']
	const operatorName = ['emails', index, 'operator']

	const watchActionName = Form.useWatch(actionNameName, form)

	const operatorOptions = [
		{ label: '天後發出', value: 'after' },
		{
			label: '天前發出',
			value: 'before',
			disabled: [
				'site_sync',
				'subscription_failed',
				'subscription_success',
				'date_created',
				'last_order_date_created',
			].includes(watchActionName),
		},
	]

	useEffect(() => {
		if ('site_sync' === watchActionName) {
			form.setFieldValue(daysName, 0)
			form.setFieldValue(operatorName, 'after')
		}
		setFocusEmailIndex({
			index,
			actionName: watchActionName,
		})
	}, [watchActionName])

	return (
		<div className="flex">
			<Tooltip
				title={
					actions.find((action) => action.value === watchActionName)?.helper
				}
				getPopupContainer={() => containerRef?.current || document.body}
			>
				<InfoCircleFilled className="text-primary mr-2" />
			</Tooltip>
			<Space.Compact block>
				<Item
					name={actionNameName}
					initialValue={record?.action_name || actionNameOptions?.[0]?.value}
					className="mb-0"
					shouldUpdate
				>
					<Select
						className="!w-[200px]"
						options={actionNameOptions}
						getPopupContainer={() => containerRef?.current || document.body}
					/>
				</Item>
				<Item
					name={daysName}
					initialValue={record?.days || 0}
					className="mb-0"
					shouldUpdate
				>
					<InputNumber
						className="w-16"
						min={0}
						disabled={'site_sync' === watchActionName}
					/>
				</Item>
				<Item
					name={operatorName}
					initialValue={record?.operator || operatorOptions?.[0]?.value}
					className="mb-0"
					shouldUpdate
				>
					<Select
						className="w-32"
						options={operatorOptions}
						getPopupContainer={() => containerRef?.current || document.body}
					/>
				</Item>
			</Space.Compact>
		</div>
	)
}
