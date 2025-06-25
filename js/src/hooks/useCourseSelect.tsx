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
					{label} <span className="text-gray-400 text-xs">#{value}</span>
				</span>
			)
		},
		value: courseIds,
		onChange: (value: string[]) => {
			setCourseIds(value)
		},
	}

	const { selectProps: refineSelectProps, query } = useSelect<TCourseRecord>({
		resource: 'courses',
		dataProviderName: 'power-course',
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

	const courses = query?.data?.data ?? []
	const options = courses?.map((course) => ({
		label: course.name,
		value: course.id,
	}))

	const mergedSelectProps: SelectProps = {
		...defaultSelectProps,
		...selectProps,
		...refineSelectProps,
		options,
	}

	return {
		selectProps: mergedSelectProps,
		courseIds,
		setCourseIds,
	}
}
