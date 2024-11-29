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

const { RangePicker } = DatePicker

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
	{ label: '最近 7 天', value: [dayjs().add(-7, 'd'), dayjs()] },
	{ label: '最近 14 天', value: [dayjs().add(-14, 'd'), dayjs()] },
	{ label: '最近 30 天', value: [dayjs().add(-30, 'd'), dayjs()] },
	{ label: '最近 90 天', value: [dayjs().add(-90, 'd'), dayjs()] },
	{ label: '月初至今', value: [dayjs().startOf('month'), dayjs()] },
	{ label: '年初至今', value: [dayjs().startOf('year'), dayjs()] },
]

const index = () => {
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
		<Form>
			<div className="flex items-center gap-x-4 mb-4">
				<RangePicker
					presets={rangePresets}
					onChange={onRangeChange}
					disabledDate={(current) => current && current > dayjs().endOf('day')}
					allowClear
					placeholder={['開始日期', '結束日期']}
				/>
				<Select
					{...defaultSelectProps}
					{...courseSelectProps}
					placeholder="可多選，可搜尋關鍵字"
				/>
				<Select
					defaultValue="day"
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
				<Button type="primary">查詢</Button>
			</div>

			<div className="flex items-center gap-x-4">
				<Checkbox>只顯示課程</Checkbox>
				<Checkbox>與去年同期比較</Checkbox>
			</div>
		</Form>
	)
}

export default index
