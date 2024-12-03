import React from 'react'
import {
	DatePicker,
	TimeRangePickerProps,
	Button,
	Select,
	Form,
	Checkbox,
} from 'antd'
import { useSelect } from '@refinedev/antd'
import dayjs, { Dayjs } from 'dayjs'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { defaultSelectProps } from '@/utils'
import { TQuery } from '../hooks/useRevenue'

const { RangePicker } = DatePicker
const { Item } = Form

const onRangeChange = (
	dates: null | (Dayjs | null)[],
	dateStrings: string[],
) => {
	if (dates) {
		console.log('From: ', dates[0], ', to: ', dates[1])
		console.log('From: ', dateStrings[0], ', to: ', dateStrings[1])
	} else {
		console.log('Clear')
	}
}

const rangePresets: TimeRangePickerProps['presets'] = [
	{
		label: '最近 7 天',
		value: [dayjs().add(-7, 'd').startOf('day'), dayjs().endOf('day')],
	},
	{
		label: '最近 14 天',
		value: [dayjs().add(-14, 'd').startOf('day'), dayjs().endOf('day')],
	},
	{
		label: '最近 30 天',
		value: [dayjs().add(-30, 'd').startOf('day'), dayjs().endOf('day')],
	},
	{
		label: '最近 90 天',
		value: [dayjs().add(-90, 'd').startOf('day'), dayjs().endOf('day')],
	},
	{
		label: '月初至今',
		value: [dayjs().startOf('month'), dayjs().endOf('day')],
	},
	{ label: '年初至今', value: [dayjs().startOf('year'), dayjs().endOf('day')] },
]

export type TFilterProps = {
	isFetching: boolean
	isLoading: boolean
	setQuery: React.Dispatch<React.SetStateAction<TQuery>>
	query: TQuery
	totalPages: number
	total: number
}

const index = ({ setQuery, isFetching }: TFilterProps) => {
	const [form] = Form.useForm()
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

	const handleSubmit = () => {
		const { date_range, ...rest } = form.getFieldsValue()
		const query = {
			...rest,
			after: date_range?.[0]?.format('YYYY-MM-DDTHH:mm:ss'),
			before: date_range?.[1]?.format('YYYY-MM-DDTHH:mm:ss'),
			per_page: 100,
			order: 'asc',
			_locale: 'user',
		}
		setQuery(query)
	}

	return (
		<Form form={form} onFinish={handleSubmit}>
			<div className="flex items-center gap-x-4 mb-4">
				<Item
					name={['date_range']}
					noStyle
					initialValue={[
						dayjs().add(-7, 'd').startOf('day'),
						dayjs().endOf('day'),
					]}
					rules={[
						{
							required: true,
							message: '請選擇日期範圍',
						},
					]}
				>
					<RangePicker
						presets={rangePresets}
						onChange={onRangeChange}
						disabledDate={(current) =>
							current && current > dayjs().endOf('day')
						}
						placeholder={['開始日期', '結束日期']}
						allowClear={false}
						className="w-[20rem]"
					/>
				</Item>
				<Item name={['included_ids']} className="w-full" noStyle>
					<Select
						{...defaultSelectProps}
						{...courseSelectProps}
						placeholder="可多選，可搜尋關鍵字"
					/>
				</Item>
				<Item name={['interval']} initialValue={'day'} noStyle>
					<Select
						className="w-32"
						options={[
							{
								label: '依天',
								value: 'day',
							},
							{
								label: '依週',
								value: 'week',
							},
							{
								label: '依月',
								value: 'month',
							},
							{
								label: '依季度',
								value: 'quarter',
							},
						]}
					/>
				</Item>
				<Button type="primary" htmlType="submit" loading={isFetching}>
					查詢
				</Button>
			</div>

			<div className="flex items-center gap-x-4">
				<Checkbox>只顯示課程</Checkbox>
				<Checkbox>與去年同期比較</Checkbox>
			</div>
		</Form>
	)
}

export default index
