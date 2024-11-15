import React from 'react'
import { Form, Select, InputNumber, Space, TimePicker, Input } from 'antd'
import { TriggerAt, TriggerCondition, SendingType, SendingUnit } from './enum'
import { useSelect } from '@refinedev/antd'
import {
	TCourseBaseRecord,
	TChapterRecord,
} from '@/pages/admin/Courses/List/types'
import { defaultSelectProps } from '@/utils'
import dayjs, { Dayjs } from 'dayjs'

const { Item } = Form

const Condition = ({ email_ids }: { email_ids: string[] }) => {
	const form = Form.useFormInstance()
	const watchTriggerAt = Form.useWatch([TriggerAt.FIELD_NAME], form)
	const watchTriggerCondition = Form.useWatch(
		['condition', TriggerCondition.FIELD_NAME],
		form,
	)
	const watchCourseIds = Form.useWatch(['condition', 'course_ids'], form)
	const watchSendingType = Form.useWatch(
		['condition', 'sending', SendingType.FIELD_NAME],
		form,
	)
	const watchSendingUnit = Form.useWatch(
		['condition', 'sending', SendingUnit.FIELD_NAME],
		form,
	)
	const watchSendingRange = Form.useWatch(
		['condition', 'sending', 'range'],
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

	const { selectProps: chapterSelectProps } = useSelect<TChapterRecord>({
		resource: 'chapters',
		optionLabel: 'name',
		optionValue: 'id',
		onSearch: (value) => [
			{
				field: 's',
				operator: 'eq',
				value,
			},
		],
		filters: watchCourseIds
			? [
					{
						field: 'posts_per_page',
						operator: 'eq',
						value: 100,
					},
					{
						field: 'post_parent__in',
						operator: 'eq',
						value: watchCourseIds,
					},
				]
			: [
					{
						field: 'post_parent__in',
						operator: 'eq',
						value: watchCourseIds,
					},
				],
		queryOptions: {
			enabled: watchTriggerAt === TriggerAt.CHAPTER_FINISH,
		},
	})

	return (
		<>
			<Space.Compact block>
				<Item
					label="觸發時機"
					name={[TriggerAt.FIELD_NAME]}
					initialValue={TriggerAt.COURSE_FINISH}
					className="w-32"
				>
					<Select
						options={[
							{
								label: '開通課程時',
								value: TriggerAt.COURSE_GRANTED,
							},
							{
								label: '完成課程時',
								value: TriggerAt.COURSE_FINISH,
							},
							{
								label: '課程開課時',
								value: TriggerAt.COURSE_SCHEDULE,
							},
							{
								label: '完成單元時',
								value: TriggerAt.CHAPTER_FINISH,
							},
							{
								label: '進入單元時',
								value: TriggerAt.CHAPTER_ENTER,
							},
						]}
					/>
				</Item>

				<Item
					className="flex-1"
					label="選擇課程"
					name={['condition', 'course_ids']}
					tooltip="可多選，可搜尋關鍵字"
					help="留空不填 = 全選所有課程"
				>
					<Select
						{...defaultSelectProps}
						{...courseSelectProps}
						placeholder="可多選，可搜尋關鍵字"
					/>
				</Item>

				{[TriggerAt.CHAPTER_FINISH, TriggerAt.CHAPTER_ENTER].includes(
					watchTriggerAt,
				) && (
					<Item
						label="選擇單元"
						name={['condition', 'chapter_ids']}
						className="flex-1"
						tooltip="可多選，可搜尋關鍵字"
						help="留空不填 = 全選所有單元"
					>
						<Select
							{...defaultSelectProps}
							{...chapterSelectProps}
							placeholder="可多選，可搜尋關鍵字"
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
								label: '任何一個達成時',
								value: TriggerCondition.EACH,
							},
							{
								label: '全部達成時',
								value: TriggerCondition.ALL,
							},
							{
								label: '達成指定數量時',
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
						<InputNumber min={1} className="w-20" />
					</Item>
				)}
			</Space.Compact>

			<Space.Compact block>
				<Item
					name={['condition', 'sending', SendingType.FIELD_NAME]}
					className="w-40"
					label="完成上述觸發條件後"
					initialValue={SendingType.NOW}
				>
					<Select
						options={[
							{
								label: '立即寄送',
								value: SendingType.NOW,
							},
							{
								label: '延遲寄送',
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
							initialValue={1}
						>
							<InputNumber min={1} className="w-full" />
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
								<Item label=" " className="w-20">
									<Input defaultValue="後的" className="pointer-events-none" />
								</Item>

								<Item
									name={['condition', 'sending', 'range']}
									label=" "
									className="w-60"
									normalize={(values: [Dayjs, Dayjs] | undefined) =>
										values ? values?.map((v) => v?.format('HH:mm')) : []
									}
									getValueProps={(values: [Dayjs, Dayjs] | undefined) => {
										if (!Array.isArray(values)) {
											return {
												value: undefined,
											}
										}

										// format('HH:mm') to Dayjs
										return {
											value: values?.map((v) => dayjs(v, 'HH:mm')),
										}
									}}
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
		</>
	)
}

export default Condition
