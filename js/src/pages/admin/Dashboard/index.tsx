import React from 'react'
import { Statistic, Card, Form } from 'antd'
import { Line, LineConfig } from '@ant-design/plots'
import Filter from './Filter'
import useRevenue from './hooks/useRevenue'
import dayjs from 'dayjs'

const cards = [
	{
		title: '淨營業額',
		slug: 'net_revenue',
	},
	{
		title: '實際成交營業額',
		slug: 'total_sales',
	},
	{
		title: '退款金額',
		slug: 'refunds',
	},
	{
		title: '淨訂單數',
		slug: 'orders_count',
	},
	{
		title: '實際成交訂單數',
		slug: 'non_refunded_orders_count',
	},
	{
		title: '已退款訂單數',
		slug: 'refunded_orders_count',
	},
	{
		title: '學員數',
		slug: 'student_count',
	},
	{
		title: '單元完成數量',
		slug: 'finished_chapters_count',
	},

	// 以下為 WC 原本就有的數據
	{
		title: '售出的商品數量',
		slug: 'num_items_sold',
	},
	{
		title: '優惠券金額',
		slug: 'coupons',
	},
	{
		title: '優惠券數量',
		slug: 'coupons_count',
	},

	// {
	// 	title: '稅金',
	// 	slug: 'taxes',
	// },
	{
		title: '運費',
		slug: 'shipping',
	},
	{
		title: '平均訂單商品數量',
		slug: 'avg_items_per_order',
	},
	{
		title: '平均訂單金額',
		slug: 'avg_order_value',
	},
	{
		title: '客戶數量',
		slug: 'total_customers',
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
		tooltip: {
			items: [
				{ name: '值', channel: 'y' },
			],
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
				<Card>
					<Statistic title="課程總數" value={revenueData?.totals?.products} />
				</Card>

				<Card>
					<Statistic title="學員總數" value={112893} />
				</Card>

				<Card>
					<Statistic
						title="淨營業額"
						value={revenueData?.totals?.net_revenue}
					/>
				</Card>

				{cards.map((card) => (
					<Card key={card.slug} title={card.title}>
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
				))}
			</div>
		</>
	)
}

export default index
