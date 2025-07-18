import React, { memo } from 'react'
import { Card, Form } from 'antd'
import dayjs from 'dayjs'
import { TTotals, TIntervalBase } from '@/pages/admin/Analytics/types'
import { useRevenueContext } from '@/pages/admin/Analytics/hooks'
import { cards, tickFilter } from '@/pages/admin/Analytics/utils'
import { Area, AreaConfig } from '@ant-design/plots'
import { nanoid } from 'nanoid'

type TData = {
	interval: string
	date_start: string
	date_end: string
	value: number
	key: string
}

function formatStackedAreaData(
	intervals: (TIntervalBase & TTotals)[],
): TData[] {
	if (!Array.isArray(intervals)) return []
	if (!intervals.length) return []

	const data = cards.reduce((acc, card) => {
		// 取其中一個 interval 的查看是否有 key 為 card.slug 的值
		if (undefined === intervals?.[0]?.[card.slug as keyof TTotals]) {
			return acc
		}

		const slugItems: TData[] = intervals.map((interval) => {
			return {
				interval: interval.interval,
				date_start: interval.date_start,
				date_end: interval.date_end,
				value: interval?.[card.slug as keyof TTotals] as number,
				key: card.title,
			}
		})

		return [
			...acc,
			...slugItems,
		]
	}, [] as TData[])

	return data
}

const AreaView = () => {
	const { viewTypeProps, form, isFetching } = useRevenueContext()
	const { revenueData } = viewTypeProps
	const intervals = revenueData?.intervals || []
	const data = formatStackedAreaData(intervals)
	const watchInterval = Form.useWatch(['interval'], form)

	const config: AreaConfig = {
		data,
		xField: 'interval',
		yField: 'value',
		colorField: 'key',
		shapeField: 'smooth',
		stack: true, // Try to remove this line.
		legend: {
			titlePosition: 'right',
			color: {
				position: 'right',
			},
		},
		axis: {
			y: {
				grid: true,
				gridLineWidth: 2,
				gridStroke: '#555',
			},

			x: {
				tickFilter,
				labelFilter: tickFilter,
			},
		},
	}

	if (isFetching) {
		const randomArray = Array.from(
			{ length: 12 },
			() => Math.floor(Math.random() * 8) + 2,
		)
		return (
			<Card variant="borderless">
				<div className="aspect-video grid grid-cols-12 gap-x-4 items-end animate-pulse">
					{randomArray.map((n) => (
						<div
							key={nanoid(4)}
							className="w-full bg-gray-200"
							style={{ height: `${n * 10}%` }}
						></div>
					))}
				</div>
			</Card>
		)
	}

	return (
		<>
			<Card variant="borderless">
				<Area
					{...config}
					height={720}
					tooltip={{
						title: ({ date_start, date_end, interval }: TData) => {
							if ('day' === watchInterval) {
								return interval
							}

							if (date_start && date_end) {
								const dateStart = dayjs(date_start).format('YYYY-MM-DD')
								const dateEnd = dayjs(date_end).format('YYYY-MM-DD')
								return `${dateStart} ~ ${dateEnd}`
							}
						},
					}}
				/>
			</Card>
		</>
	)
}

export default memo(AreaView)
