import React from 'react'
import { Drawer, Timeline } from 'antd'
import { useAtom } from 'jotai'
import { historyDrawerAtom } from '../atom'
import { TimelineItemAdapter } from './adapter'
import { TimelineLogType } from './types'
import { useList } from '@refinedev/core'

type TStudentLog = any

const index = () => {
	const [historyDrawerProps, setHistoryDrawerProps] = useAtom(historyDrawerAtom)
	const { user_id, course_id, drawerProps } = historyDrawerProps

	const { data, isLoading } = useList<TStudentLog>({
		resource: 'courses/student-logs',
		filters: [
			{
				field: 'user_id',
				operator: 'eq',
				value: user_id,
			},
			{
				field: 'course_id',
				operator: 'eq',
				value: course_id,
			},
		],
		pagination: {
			pageSize: 20,
		},
		queryOptions: {
			enabled: !!user_id && !!course_id,
		},
	})

	const logs = data?.data || []

	const items = logs.map(({ log_type, title }) => {
		return new TimelineItemAdapter(log_type as TimelineLogType, title).itemProps
	})

	return (
		<Drawer
			title="課程紀錄"
			onClose={() =>
				setHistoryDrawerProps((prev) => ({
					...prev,
					drawerProps: {
						...prev.drawerProps,
						open: false,
					},
				}))
			}
			{...drawerProps}
			open
		>
			<Timeline items={items} />
		</Drawer>
	)
}

export default index
