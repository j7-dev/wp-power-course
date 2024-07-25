import { FC } from 'react'
import {
	Form,
	DatePicker as AntdDatePicker,
	DatePickerProps,
	FormItemProps,
} from 'antd'
import { parseDatePickerValue } from '@/utils'
import dayjs from 'dayjs'

const { Item } = Form

export const DatePicker: FC<{
	formItemProps?: FormItemProps
	datePickerProps?: DatePickerProps
}> = ({ formItemProps, datePickerProps }) => {
	return (
		<Item
			getValueProps={(value) => ({
				value: parseDatePickerValue(value) as unknown as Record<
					string,
					unknown
				>,
			})}
			normalize={(value) => value?.unix()}
			{...formItemProps}
		>
			<AntdDatePicker
				className="w-full"
				showTime={{ defaultValue: dayjs() }}
				format="YYYY-MM-DD HH:mm"
				{...datePickerProps}
			/>
		</Item>
	)
}
