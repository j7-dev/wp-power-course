import React from 'react'
import { Statistic, Card } from 'antd'
import { Line } from '@ant-design/plots'
import Filter from './Filter'
import useRevenue from './hooks/useRevenue'

const cards = [
	{
		title: '淨營業額',
		slug: 'netRevenue',
	},
	{
		title: '實際成交營業額',
		slug: 'actualRevenue',
	},
	{
		title: '退款金額',
		slug: 'refundAmount',
	},
	{
		title: '淨訂單數',
		slug: 'netOrderCount',
	},
	{
		title: '實際成交訂單數',
		slug: 'completedOrderCount',
	},
	{
		title: '取消訂單數',
		slug: 'canceledOrderCount',
	},
	{
		title: '學員數',
		slug: 'studentCount',
	},
	{
		title: '新學員數',
		slug: 'newStudentCount',
	},
	{
		title: '單元完成數量',
		slug: 'completedUnitCount',
	},
]

const index = () => {
	const { data, isLoading } = useRevenue()
	const revenueData = data?.data
	const intervals = revenueData?.intervals || []
	console.log('⭐  intervals:', intervals)

	const config = {
		data: intervals,
		xField: 'interval',
		yField: 'net_revenue',
		point: {
			shapeField: 'square',
			sizeField: 1,
		},
		interaction: {
			tooltip: {
				marker: false,
			},
		},
		style: {
			lineWidth: 2,
		},
	}

	return (
		<>
			<div className="mb-4">
				<Filter />
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
						<Line {...config} className="aspect-video" />
					</Card>
				))}
			</div>
		</>
	)
}

export default index
