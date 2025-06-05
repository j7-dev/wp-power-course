import { SelectProps } from 'antd'
import dayjs, { Dayjs } from 'dayjs'

/**
 * @deprecated
 */
export const defaultSelectProps: SelectProps = {
	className: 'w-full',
	mode: 'multiple',
	optionRender: ({ value, label }) => {
		return (
			<span>
				<span className="text-gray-400 text-xs">#{value}</span> {label}
			</span>
		)
	},
	allowClear: true,
	showSearch: true,
	optionFilterProp: 'label',
}

/**
 * 處理日期欄位 sale_date_range
 * 將表單中的 date Range
 * 轉換成個別的 開始、結束 property
 * 例如
 * 表單中的 sale_date_range 是 [null, null]
 * 轉換成 { date_on_sale_from: null, date_on_sale_to: null }
 * @param values
 * @param fromProperty
 * @param toProperty

 * @return
 */
export const formatDateRangeData = (
	values: {
		[key: string]: any
	},
	fromProperty: string,
	toProperty: [string, string],
) => {
	const sale_date_range = values?.[fromProperty] || [null, null]

	// 處理日期欄位 sale_date_range

	const date_on_sale_from =
		(sale_date_range[0] as any) instanceof dayjs
			? (sale_date_range[0] as Dayjs).unix()
			: sale_date_range[0]
	const date_on_sale_to =
		(sale_date_range[1] as any) instanceof dayjs
			? (sale_date_range[1] as Dayjs).unix()
			: sale_date_range[1]

	const toPropertyFrom = toProperty[0]
	const toPropertyTo = toProperty[1]

	const formattedValues = {
		...values,
		[toPropertyFrom]: date_on_sale_from,
		[toPropertyTo]: date_on_sale_to,
		sale_date_range: undefined,
	}

	return formattedValues
}
