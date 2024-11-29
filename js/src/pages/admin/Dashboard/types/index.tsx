export type TTotals = {
	orders_count: 0
	num_items_sold: 0
	gross_sales: 0
	total_sales: 0
	coupons: 0
	coupons_count: 0
	refunds: 0
	taxes: 0
	shipping: 0
	net_revenue: 0
	avg_items_per_order: 0
	avg_order_value: 0
	total_customers: 0
	products: 0
	segments: any[]
}

export type TIntervalBase = {
	interval: string // '2022-52'
	date_start: string // '2023-01-01 00:00:00'
	date_start_gmt: string // '2022-12-31 16:00:00'
	date_end: string // '2023-01-01 23:59:59'
	date_end_gmt: string // '2023-01-01 15:59:59'
}

export type TRevenue = {
	totals: TTotals
	intervals: (TIntervalBase & {
		subtotals: TTotals
	})[]
}

export type TFormattedRevenue = {
	totals: TTotals
	intervals: (TIntervalBase & TTotals)[]
}
