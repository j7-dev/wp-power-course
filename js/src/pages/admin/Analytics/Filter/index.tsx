import React, { useState, useEffect } from 'react'
import { DatePicker, Button, Select, Form, Checkbox, Tooltip, Tag } from 'antd'
import dayjs from 'dayjs'
import { useSelect } from '@refinedev/antd'
import { TProductSelectOption } from '@/components/product/ProductTable/types'
import { useRevenueContext } from '@/pages/admin/Analytics/hooks'
import { AreaChartOutlined, LineChartOutlined } from '@ant-design/icons'
import { EViewType } from '@/pages/admin/Analytics/types'
import { RANGE_PRESETS, maxDateRange } from '@/pages/admin/Analytics/utils'
import { productTypes } from '@/utils'
import { defaultSelectProps } from 'antd-toolkit'
import { objToCrudFilters } from 'antd-toolkit/refine'

const { RangePicker } = DatePicker
const { Item } = Form

const index = () => {
	const { viewType, setViewType, form, query, setQuery, context, isFetching } =
		useRevenueContext()

	// 需要這個 state 是因為，需要知道用戶選了哪些課程/商品(需要挑出 is_course 為 true 的)，才能在查詢時帶入
	const [selectedCourseProducts, setSelectedCourseProducts] = useState<
		TProductSelectOption[]
	>([])

	const { selectProps: productSelectProps, query: productQuery } =
		useSelect<TProductSelectOption>({
			resource: 'products/select',
			dataProviderName: 'power-course',
			optionLabel: 'name',
			optionValue: 'id',
			onSearch: (value) => [
				{
					field: 's',
					operator: 'eq',
					value,
				},
			],
			filters: objToCrudFilters({
				meta_key: 'link_course_ids',
				meta_compare: 'NOT EXISTS',
			}),
		})

	const productSelectOptions = productQuery?.data?.data || []

	const { selectProps: bundleProductSelectProps } =
		useSelect<TProductSelectOption>({
			resource: 'products/select',
			dataProviderName: 'power-course',
			optionLabel: 'name',
			optionValue: 'id',
			filters: objToCrudFilters({
				meta_key: 'link_course_ids',
				meta_compare: 'IN',
				meta_value: query?.product_includes?.length
					? query?.product_includes
					: selectedCourseProducts.map((product) => product.id),
			}),
			queryOptions: {
				enabled:
					!!selectedCourseProducts?.length || !!query?.product_includes?.length,
			},
		})

	const handleSubmit = () => {
		const {
			date_range,
			products = [],
			bundle_products = [],
			...rest
		} = form.getFieldsValue()
		const query = {
			...rest,
			after: date_range?.[0]?.format('YYYY-MM-DDTHH:mm:ss'),
			before: date_range?.[1]?.format('YYYY-MM-DDTHH:mm:ss'),
			per_page: 10000,
			order: 'asc',
			_locale: 'user',
			product_includes: [...products, ...bundle_products],
		}
		setQuery(query)
	}

	const watchProductIds = Form.useWatch(['products'], form) || []

	useEffect(() => {
		const selectedCourseProductOptions = (productSelectOptions?.filter(
			(option) => watchProductIds?.includes(option?.id) && option?.is_course,
		) || []) as TProductSelectOption[]
		setSelectedCourseProducts(selectedCourseProductOptions)
	}, [watchProductIds?.length])

	return (
		<Form form={form} onFinish={handleSubmit} layout="vertical">
			<div className="flex items-center gap-x-4 mb-4">
				<Item
					label="日期範圍"
					name={['date_range']}
					tooltip="最大選取範圍為 1 年"
					initialValue={[
						dayjs().add(-7, 'd').startOf('day'),
						dayjs().endOf('day'),
					]}
					rules={[
						{
							required: true,
							message: '請選擇日期範圍',
						},
					]}
				>
					<RangePicker
						presets={RANGE_PRESETS}
						disabledDate={maxDateRange}
						placeholder={['開始日期', '結束日期']}
						allowClear={false}
						className="w-[16rem]"
					/>
				</Item>

				<Item
					name={['products']}
					className="w-full"
					label="查看特定課程/商品"
					hidden={context === 'detail'}
				>
					<Select
						{...defaultSelectProps}
						{...productSelectProps}
						placeholder="可多選，可搜尋關鍵字"
						optionRender={({ value, label }) => {
							const option = productSelectOptions.find(
								(productOption) => productOption?.id === value,
							)
							const productType = productTypes.find(
								(pt) => pt?.value === option?.type,
							)
							return (
								<span>
									<span className="text-gray-400 text-xs">#{value}</span>{' '}
									{label}{' '}
									<Tag color={productType?.color}>{productType?.label}</Tag>
									{option?.is_course && <Tag color="gold">課程</Tag>}
								</span>
							)
						}}
					/>
				</Item>

				<Item
					name={['bundle_products']}
					className="w-full"
					label="查看銷售方案"
				>
					<Select
						{...defaultSelectProps}
						{...bundleProductSelectProps}
						placeholder="可多選"
						disabled={!bundleProductSelectProps?.options?.length}
					/>
				</Item>
				<Item name={['interval']} initialValue={'day'} label="時間間格">
					<Select
						className="w-24"
						options={[
							{
								label: '依天',
								value: 'day',
							},
							{
								label: '依週',
								value: 'week',
							},
							{
								label: '依月',
								value: 'month',
							},
							{
								label: '依季度',
								value: 'quarter',
							},
						]}
					/>
				</Item>
				<Item label=" ">
					<Button type="primary" htmlType="submit" loading={isFetching}>
						查詢
					</Button>
				</Item>
			</div>

			<div className="flex justify-between">
				<div className="flex items-center gap-x-4">
					<Item
						name={['compare_last_year']}
						initialValue={false}
						noStyle
						valuePropName="checked"
					>
						<Checkbox>與去年同期比較</Checkbox>
					</Item>
				</div>
				<div className="flex items-center gap-x-2">
					<Tooltip title="分開顯示">
						<LineChartOutlined
							className={`text-xl ${EViewType.DEFAULT === viewType ? 'text-primary' : 'text-gray-400'}`}
							onClick={() => setViewType(EViewType.DEFAULT)}
						/>
					</Tooltip>
					<Tooltip title="堆疊比較">
						<AreaChartOutlined
							className={`text-xl ${EViewType.AREA === viewType ? 'text-primary' : 'text-gray-400'}`}
							onClick={() => setViewType(EViewType.AREA)}
						/>
					</Tooltip>
				</div>
			</div>
		</Form>
	)
}

export default index
