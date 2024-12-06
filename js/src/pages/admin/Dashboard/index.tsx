import React from 'react'
import { Statistic, Card, Form } from 'antd'
import { Line, LineConfig } from '@ant-design/plots'
import Filter from './Filter'
import useRevenue from './hooks/useRevenue'
import dayjs from 'dayjs'
import { TTotals } from './types'

const cards = [
	{
		title: '淨營業額',
		slug: 'net_revenue',
		unit: '元',
	},
	{
		title: '實際成交營業額',
		slug: 'total_sales',
		unit: '元',
	},
	{
		title: '退款金額',
		slug: 'refunds',
		unit: '元',
	},
	{
		title: '淨訂單數',
		slug: 'orders_count',
		unit: '個',
	},
	{
		title: '實際成交訂單數',
		slug: 'non_refunded_orders_count',
		unit: '個',
	},
	{
		title: '已退款訂單數',
		slug: 'refunded_orders_count',
		unit: '個',
	},
	{
		title: '學員數',
		slug: 'student_count',
		unit: '人',
	},
	{
		title: '單元完成數量',
		slug: 'finished_chapters_count',
		unit: '個',
	},

	// 以下為 WC 原本就有的數據
	{
		title: '售出的商品數量',
		slug: 'num_items_sold',
		unit: '個',
	},
	{
		title: '優惠券金額',
		slug: 'coupons',
		unit: '元',
	},
	{
		title: '優惠券數量',
		slug: 'coupons_count',
		unit: '個',
	},

	// {
	// 	title: '稅金',
	// 	slug: 'taxes',
	// },
	{
		title: '運費',
		slug: 'shipping',
		unit: '元',
	},
	{
		title: '平均訂單商品數量',
		slug: 'avg_items_per_order',
		unit: '個',
	},
	{
		title: '平均訂單金額',
		slug: 'avg_order_value',
		unit: '元',
	},
	{
		title: '客戶數量',
		slug: 'total_customers',
		unit: '人',
	},
]

const index = () => {
	const { result, filterProps } = useRevenue()
	const revenueData = result?.data?.data
	const intervals = revenueData?.intervals || []
	const form = filterProps.form
	const watchInterval = Form.useWatch(['interval'], form)

	console.log('⭐  intervals:', intervals)

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
	}

	return (
		<>
			<div className="mb-4">
				<Filter {...filterProps} />
			</div>
			<div className="grid grid-cols-3 gap-4">
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
						>
							<Line
								{...config}
								className="aspect-video"
								yField={card.slug}
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

export default index
