import React, { useState } from 'react'
import { useSelect } from '@refinedev/antd'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { SelectProps } from 'antd'

type TUseCourseSelectParams = {
	selectProps?: SelectProps
}

export const useCourseSelect = (params?: TUseCourseSelectParams) => {
	const selectProps = params?.selectProps
	const [courseIds, setCourseIds] = useState<string[]>([])

	const defaultSelectProps: SelectProps = {
		placeholder: '搜尋課程關鍵字',
		className: 'w-full',
		allowClear: true,
		mode: 'multiple',
		optionRender: ({ value, label }) => {
			return (
				<span>
					{label} <sub className="text-gray-500">#{value}</sub>
				</span>
			)
		},
		value: courseIds,
		onChange: (value: string[]) => {
			setCourseIds(value)
		},
	}

	const { selectProps: refineSelectProps } = useSelect<TCourseRecord>({
		resource: 'courses',
		optionLabel: 'name',
		optionValue: 'id',
		debounce: 500,
		pagination: {
			pageSize: 20,
			mode: 'server',
		},
		onSearch: (value) => [
			{
				field: 's',
				operator: 'contains',
				value,
			},
		],
	})

	const mergedSelectProps: SelectProps = {
		...refineSelectProps,
		...defaultSelectProps,
		...selectProps,
	}

	return {
		selectProps: mergedSelectProps,
		courseIds,
		setCourseIds,
	}
}
