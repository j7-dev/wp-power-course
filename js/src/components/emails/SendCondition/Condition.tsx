import React from 'react'
import { Form, Select, InputNumber, Space, TimePicker, Input } from 'antd'
import { TriggerAt, TriggerCondition, SendingType, SendingUnit } from './enum'
import { useSelect } from '@refinedev/antd'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { defaultSelectProps } from '@/utils'
import dayjs from 'dayjs'

const { Item } = Form

const Condition = ({ email_ids }: { email_ids: string[] }) => {
	const [form] = Form.useForm()
	const watchTriggerAt = Form.useWatch(
		['condition', TriggerAt.FIELD_NAME],
		form,
	)
	const watchTriggerCondition = Form.useWatch(
		['condition', TriggerCondition.FIELD_NAME],
		form,
	)
	const watchSendingType = Form.useWatch(
		['condition', 'sending', SendingType.FIELD_NAME],
		form,
	)
	const watchSendingUnit = Form.useWatch(
		['condition', 'sending', SendingUnit.FIELD_NAME],
		form,
	)

	const { selectProps: courseSelectProps } = useSelect<TCourseBaseRecord>({
		resource: 'courses',
		optionLabel: 'name',
		optionValue: 'id',
		onSearch: (value) => [
			{
				field: 's',
				operator: 'eq',
				value,
			},
		],
	})

	return (
		<Form layout="vertical" form={form}>
			<Space.Compact block>
				<Item
					label="觸發時機"
					name={['condition', TriggerAt.FIELD_NAME]}
					initialValue={TriggerAt.COURSE_FINISH}
					className="w-32"
				>
					<Select
						options={[
							{
								label: '完成課程時',
								value: TriggerAt.COURSE_FINISH,
							},
							{
								label: '完成章節時',
								value: TriggerAt.CHAPTER_FINISH,
							},
						]}
					/>
				</Item>

				<Item
					className="flex-1"
					label="選擇課程"
					name={['condition', 'course_ids']}
				>
					<Select
						{...defaultSelectProps}
						{...courseSelectProps}
						placeholder="可多選"
					/>
				</Item>

				{watchTriggerAt === TriggerAt.CHAPTER_FINISH && (
					<Item
						label="選擇章節"
						name={['condition', 'chapter_ids']}
						className="flex-1"
					>
						<Select
							options={[
								{
									label: '完成課程時',
									value: 'course_finish',
								},
								{
									label: '完成章節時',
									value: 'chapter_finish',
								},
							]}
						/>
					</Item>
				)}

				<Item
					className="w-[10rem]"
					label="觸發條件"
					name={['condition', TriggerCondition.FIELD_NAME]}
					initialValue="each"
				>
					<Select
						options={[
							{
								label: '每一個完成時',
								value: TriggerCondition.EACH,
							},
							{
								label: '全部完成時',
								value: TriggerCondition.ALL,
							},
							{
								label: '完成任意數量時',
								value: TriggerCondition.QUANTITY_GREATER_THAN,
							},
						]}
					/>
				</Item>

				{watchTriggerCondition === TriggerCondition.QUANTITY_GREATER_THAN && (
					<Item
						className="w-20"
						label="數量"
						name={['condition', 'qty']}
						initialValue={1}
					>
						<InputNumber className="w-20" />
					</Item>
				)}
			</Space.Compact>

			<Space.Compact block>
				<Item
					name={['condition', 'sending', SendingType.FIELD_NAME]}
					className="w-48"
					label="寄送"
					initialValue={SendingType.NOW}
				>
					<Select
						options={[
							{
								label: '完成條件後立即寄送',
								value: SendingType.NOW,
							},
							{
								label: '完成條件後延遲寄送',
								value: SendingType.LATER,
							},
						]}
					/>
				</Item>

				{watchSendingType === SendingType.LATER && (
					<>
						<Item
							name={['condition', 'sending', 'value']}
							label=" "
							className="w-20"
						>
							<InputNumber className="w-full" />
						</Item>

						<Item
							name={['condition', 'sending', SendingUnit.FIELD_NAME]}
							label=" "
							className="w-16"
							initialValue={SendingUnit.DAY}
						>
							<Select
								options={[
									{
										label: '天',
										value: SendingUnit.DAY,
									},
									{
										label: '時',
										value: SendingUnit.HOUR,
									},
									{
										label: '分',
										value: SendingUnit.MINUTE,
									},
								]}
							/>
						</Item>

						{watchSendingUnit === SendingUnit.DAY && (
							<>
								<Item label=" " className="w-[2.5rem]">
									<Input defaultValue="的" className="pointer-events-none" />
								</Item>

								<Item
									name={['condition', 'sending', 'range']}
									label=" "
									className="w-60"
								>
									<TimePicker.RangePicker
										defaultValue={[
											dayjs('08:00', 'HH:mm'),
											dayjs('12:00', 'HH:mm'),
										]}
										format="HH:mm"
										placeholder={['開始時間', '結束時間']}
									/>
								</Item>
							</>
						)}
					</>
				)}
			</Space.Compact>
		</Form>
	)
}

export default Condition
