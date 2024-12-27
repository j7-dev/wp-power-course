import React from 'react'
import { Drawer, Timeline } from 'antd'
import { useAtom } from 'jotai'
import { historyDrawerAtom } from '../atom'
import { TimelineItemAdapter } from './adapter'
import { TimelineSlug } from './types'
import { TriggerAt } from '@/components/emails/SendCondition/enum'

const index = () => {
	const [historyDrawerProps, setHistoryDrawerProps] = useAtom(historyDrawerAtom)
	const { user_id, course_id, drawerProps } = historyDrawerProps

	const rawItems = [
		{
			slug: TriggerAt.ORDER_CREATED,
			label: '2024-12-01 10:53 購買課程 #1234',
		},
		{
			slug: TriggerAt.COURSE_GRANTED,
			label: '2024-12-04 16:28 獲得課程權限 AAAAAA',
		},
		{
			slug: TriggerAt.CHAPTER_ENTER,
			label: '2024-12-04 16:28 進入章節 OOOOOO',
		},
		{
			slug: TriggerAt.CHAPTER_FINISH,
			label: '2024-12-04 16:28 完成章節 AAAAAA',
		},
		{
			slug: TriggerAt.COURSE_FINISH,
			label: '2024-12-04 16:28 完成課程 AAAAAA',
		},
	]

	const items = rawItems.map(({ slug, label }) => {
		return new TimelineItemAdapter(slug as TimelineSlug, label).itemProps
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
		>
			<Timeline items={items} />
		</Drawer>
	)
}

export default index
