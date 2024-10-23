import { SelectProps } from 'antd'

export const defaultSelectProps: SelectProps = {
	className: 'w-full',
	mode: 'multiple',
	optionRender: ({ value, label }) => {
		return (
			<span>
				{label} <sub className="text-gray-500">#{value}</sub>
			</span>
		)
	},
	allowClear: true,
	showSearch: true,
	optionFilterProp: 'label',
}
