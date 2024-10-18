import React, { memo } from 'react'
import { Form, Radio, Space, InputNumber, Select, Input } from 'antd'
import { DatePicker } from '@/components/formItem'
const { Item } = Form

const WatchLimitComponent = () => {
	const form = Form.useFormInstance()
	const watchLimitType: string = Form.useWatch(['limit_type'], form)

	const handleReset = (value: string) => {
		if ('unlimited' === value) {
			form.setFieldsValue({ limit_value: '', limit_unit: '' })
		}
		if ('fixed' === value) {
			form.setFieldsValue({ limit_value: 1, limit_unit: 'day' })
		}
		if ('assigned' === value) {
			form.setFieldsValue({
				limit_value: undefined,
				limit_unit: 'timestamp',
			})
		}
	}

	return (
		<div>
			<Item label="觀看期限" name={['limit_type']} initialValue={'unlimited'}>
				<Radio.Group
					className="w-full w-avg"
					options={[
						{ label: '無期限', value: 'unlimited' },
						{ label: '固定天數', value: 'fixed' },
						{ label: '指定時間', value: 'assigned' },
					]}
					optionType="button"
					buttonStyle="solid"
					onChange={(e) => {
						const value = e?.target?.value || ''
						handleReset(value)
					}}
				/>
			</Item>
			{'unlimited' === watchLimitType && (
				<>
					<Item name={['limit_value']} initialValue="" hidden />
					<Item name={['limit_unit']} initialValue="" hidden />
				</>
			)}
			{'fixed' === watchLimitType && (
				<Space.Compact block>
					<Item name={['limit_value']} initialValue={1} className="w-full">
						<InputNumber className="w-full" min={1} />
					</Item>
					<Item name={['limit_unit']} initialValue="day">
						<Select
							options={[
								{ label: '日', value: 'day' },
								{ label: '月', value: 'month' },
								{ label: '年', value: 'year' },
							]}
							className="w-16"
						/>
					</Item>
				</Space.Compact>
			)}
			{'assigned' === watchLimitType && (
				<>
					<DatePicker
						formItemProps={{
							name: ['limit_value'],
							className: 'mb-0',
							rules: [
								{
									required: true,
									message: '請填寫指定時間',
								},
							],
						}}
					/>
					<Item name={['limit_unit']} initialValue="timestamp" hidden>
						<Input />
					</Item>
				</>
			)}
		</div>
	)
}

export const WatchLimit = memo(WatchLimitComponent)
