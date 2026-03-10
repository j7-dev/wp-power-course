import React, { useEffect, useMemo, useCallback, memo } from 'react'
import { Form, Radio, Space, InputNumber, Select, Input, Alert } from 'antd'
import { DatePicker } from '@/components/formItem'
import { useLink } from '@refinedev/core'
import { TCoursesLimit } from '@/pages/admin/Courses/List/types'

const { Item } = Form
const RESET_BY_LIMIT_TYPE: Record<string, { value: unknown; unit: unknown }> = {
	unlimited: { value: '', unit: '' },
	fixed: { value: 1, unit: 'day' },
	assigned: { value: undefined, unit: 'timestamp' },
	follow_subscription: { value: '', unit: '' },
}

type TWatchLimitProps = {
	namePrefix?: (string | number)[]
	showFollowSubscription?: boolean
}

const WatchLimitComponent = ({
	namePrefix = [],
	showFollowSubscription = true,
}: TWatchLimitProps) => {
	const form = Form.useFormInstance()
	const limitTypeName = useMemo(() => [...namePrefix, 'limit_type'], [namePrefix])
	const limitValueName = useMemo(() => [...namePrefix, 'limit_value'], [namePrefix])
	const limitUnitName = useMemo(() => [...namePrefix, 'limit_unit'], [namePrefix])
	const watchLimitType: TCoursesLimit['limit_type'] = Form.useWatch(
		limitTypeName,
		form,
	)

	const handleReset = useCallback((value: string) => {
		const resetValue = RESET_BY_LIMIT_TYPE[value]
		if (!resetValue) {
			return
		}
		form.setFieldValue(limitValueName, resetValue.value)
		form.setFieldValue(limitUnitName, resetValue.unit)
	}, [form, limitValueName, limitUnitName])

	const watchProductType = Form.useWatch(['type'], form)

	useEffect(() => {
		if (
			watchProductType === 'simple' &&
			watchLimitType === 'follow_subscription'
		) {
			form.setFieldValue(limitTypeName, 'unlimited')
		}
	}, [watchProductType, watchLimitType, form, limitTypeName])

	useEffect(() => {
		if (!showFollowSubscription && watchLimitType === 'follow_subscription') {
			form.setFieldValue(limitTypeName, 'unlimited')
			handleReset('unlimited')
		}
	}, [showFollowSubscription, watchLimitType, form, limitTypeName, handleReset])

	const Link = useLink()
	const limitTypeOptions = [
		{ label: '無期限', value: 'unlimited' },
		{ label: '固定天數', value: 'fixed' },
		{ label: '指定時間', value: 'assigned' },
		{
			label: '跟隨訂閱',
			value: 'follow_subscription',
			disabled: watchProductType === 'simple',
		},
	]

	return (
		<div>
			<Item label="觀看期限" name={limitTypeName} initialValue={'unlimited'}>
				<Radio.Group
					className="w-full w-avg"
					options={showFollowSubscription ? limitTypeOptions : limitTypeOptions.slice(0, 3)}
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
					<Item name={limitValueName} initialValue="" hidden />
					<Item name={limitUnitName} initialValue="" hidden />
				</>
			)}
			{'fixed' === watchLimitType && (
				<Space.Compact block>
					<Item name={limitValueName} initialValue={1} className="w-full">
						<InputNumber className="w-full" min={1} />
					</Item>
					<Item name={limitUnitName} initialValue="day">
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
							name: limitValueName,
							className: 'mb-0',
							rules: [
								{
									required: true,
									message: '請填寫指定時間',
								},
							],
						}}
					/>
					<Item name={limitUnitName} initialValue="timestamp" hidden>
						<Input />
					</Item>
				</>
			)}
			{'follow_subscription' === watchLimitType && showFollowSubscription && (
				<>
					<Alert
						className="my-4"
						message="注意事項"
						description={
							<ol className="pl-4">
								<li>選擇跟隨訂閱，課程就必須是訂閱商品</li>
								<li>
									你也可以選擇不跟隨訂閱，讓課程維持簡單商品，使用銷售方案創建定期定額銷售方案，再去
									<Link to="/products"> 課程權限綁定 </Link>
									調整課程觀看期限為跟隨訂閱
								</li>
							</ol>
						}
						type="warning"
						showIcon
					/>
					<Item name={limitValueName} initialValue="" hidden />
					<Item name={limitUnitName} initialValue="" hidden />
				</>
			)}
		</div>
	)
}

export const WatchLimit = memo(WatchLimitComponent)
