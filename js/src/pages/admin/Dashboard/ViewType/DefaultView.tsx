import React, { memo } from 'react'
import { Card, Form } from 'antd'
import { Line, LineConfig } from '@ant-design/plots'
import dayjs from 'dayjs'
import { TTotals, TViewTypeProps } from '../types'
import { cards, tickFilter } from '../index'

const Default = ({ revenueData, form }: TViewTypeProps) => {
	const intervals = revenueData?.intervals || []
	const watchInterval = Form.useWatch(['interval'], form)

	const config: LineConfig = {
		data: intervals,
		xField: 'interval',
		point: {
			shapeField: 'square',
			sizeField: 1,
		},
		style: {
			lineWidth: 2,
			shape: 'smooth',
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

	return (
		<>
			<div className="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
				{cards.map((card) => {
					const total = revenueData?.totals?.[card.slug as keyof TTotals] || 0
					return (
						<Card
							key={card.slug}
							title={card.title}
							extra={
								<span className="text-sm text-gray-500">
									共
									<span className="text-2xl text-primary font-semibold mx-2">
										{total.toLocaleString()}
									</span>
									{card.unit}
								</span>
							}
							bordered={false}
						>
							<Line
								{...config}
								yField={card.slug}
								height={300}
								tooltip={{
									title: ({ date_start, date_end, interval }) => {
										if ('day' === watchInterval) {
											return interval
										}

										if (date_start && date_end) {
											const dateStart = dayjs(date_start).format('YYYY-MM-DD')
											const dateEnd = dayjs(date_end).format('YYYY-MM-DD')
											return `${dateStart} ~ ${dateEnd}`
										}
									},
									items: [
										{ name: card.title, channel: 'y' },
									],
								}}
							/>
						</Card>
					)
				})}
			</div>
		</>
	)
}

export default memo(Default)
