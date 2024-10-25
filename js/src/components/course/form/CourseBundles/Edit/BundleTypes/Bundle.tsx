import React, { useState, memo, useEffect } from 'react'
import { Form, Input, Tag, List, InputNumber } from 'antd'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { PopconfirmDelete, Heading } from '@/components/general'
import { FiSwitch, RangePicker } from '@/components/formItem'
import {
	CheckOutlined,
	PlusOutlined,
	ExclamationCircleOutlined,
} from '@ant-design/icons'
import { useAtomValue, useAtom } from 'jotai'
import { courseAtom, selectedProductsAtom, bundleProductAtom } from '../atom'
import { INCLUDED_PRODUCT_IDS_FIELD_NAME, getPrice } from '../utils'
import { useList } from '@refinedev/core'

const { Search } = Input
const { Item } = Form

const Bundle = () => {
	const course = useAtomValue(courseAtom)
	const record = useAtomValue(bundleProductAtom)
	const [selectedProducts, setSelectedProducts] = useAtom(selectedProductsAtom)

	const {
		id: courseId,
		name: courseName,
		price_html: coursePrice,
	} = course as TCourseRecord

	const [searchKeyWord, setSearchKeyWord] = useState<string>('')
	const [showList, setShowList] = useState<boolean>(false)
	const bundleProductForm = Form.useFormInstance()
	const watchExcludeMainCourse =
		Form.useWatch(['exclude_main_course'], bundleProductForm) === 'yes'
	const watchRegularPrice = Number(
		Form.useWatch(['regular_price'], bundleProductForm),
	)
	const watchSalePrice = Number(
		Form.useWatch(['sale_price'], bundleProductForm),
	)

	const onSearch = (value: string) => {
		setSearchKeyWord(value)
	}

	const searchProductsResult = useList<TBundleProductRecord>({
		resource: 'products',
		filters: [
			{
				field: 's',
				operator: 'eq',
				value: searchKeyWord,
			},
			{
				field: 'status',
				operator: 'eq',
				value: 'publish',
			},
			{
				field: 'exclude',
				operator: 'eq',
				value: [courseId],
			},
			{
				field: 'product_type',
				operator: 'eq',
				value: 'simple',
			},
		],
		pagination: {
			pageSize: 20,
		},
	})

	const searchProducts = searchProductsResult.data?.data || []

	// 處理點擊商品，有可能是加入也可能是移除
	const handleClick = (product: TBundleProductRecord) => () => {
		const isInclude = selectedProducts?.some(({ id }) => id === product.id)
		if (isInclude) {
			// 當前列表中已經有這個商品，所以要移除

			setSelectedProducts(
				selectedProducts.filter(({ id }) => id !== product.id),
			)
		} else {
			// 當前列表中沒有這個商品，所以要加入

			setSelectedProducts([...selectedProducts, product])
		}
	}

	// 將當前商品移除
	const initPIdsExcludedCourseId = (
		record?.[INCLUDED_PRODUCT_IDS_FIELD_NAME] || []
	).filter((id) => id !== courseId)

	// 初始狀態
	const { data: initProductsData, isFetching: initIsFetching } =
		useList<TBundleProductRecord>({
			resource: 'products',
			filters: [
				{
					field: 'include',
					operator: 'eq',
					value: initPIdsExcludedCourseId,
				},
			],
			queryOptions: {
				// 剛進來的時候才需要 fetch
				enabled: !!initPIdsExcludedCourseId?.length,
			},
		})

	const includedProducts = initProductsData?.data || []

	useEffect(() => {
		if (!initIsFetching) {
			// 初始化商品
			setSelectedProducts(includedProducts)
		}
	}, [initIsFetching])

	useEffect(() => {
		// 選擇商品改變時，同步更新到表單上
		const productIds = watchExcludeMainCourse
			? selectedProducts.map(({ id }) => id)
			: [
					courseId,
					...selectedProducts.map(({ id }) => id),
				]
		bundleProductForm.setFieldValue(
			[INCLUDED_PRODUCT_IDS_FIELD_NAME],
			productIds,
		)

		bundleProductForm.setFieldValue(
			['regular_price'],
			getPrice({
				type: 'regular_price',
				products: selectedProducts,
				course,
				excludeMainCourse: watchExcludeMainCourse,
			}),
		)
	}, [selectedProducts.length, watchExcludeMainCourse])

	return (
		<>
			<Heading className="mb-3">搭配你的銷售方案，請選擇要加入的商品</Heading>
			<FiSwitch
				formItemProps={{
					name: ['exclude_main_course'],
					label: '排除目前課程',
				}}
				switchProps={{
					size: 'small',
				}}
			/>

			<div
				className={`border-2 border-dashed rounded-xl p-4 mb-8 ${selectedProducts.length ? 'border-blue-500' : 'border-red-500'}`}
			>
				{/* 當前課程方案 */}
				<div
					className={`flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md ${watchExcludeMainCourse ? 'opacity-20 saturate-0' : ''}`}
				>
					<img
						src={course?.images?.[0]?.url || defaultImage}
						className="h-9 w-16 rounded object-cover"
					/>
					<div className="w-full">
						{courseName} #{courseId} {renderHTML(coursePrice || '')}
					</div>
					<div>
						<Tag color="blue">目前課程</Tag>
					</div>
				</div>
				{/* END 當前課程方案 */}
				<div
					className={`text-center my-2 ${watchExcludeMainCourse ? 'opacity-0' : ''}`}
				>
					<PlusOutlined />
				</div>
				{!selectedProducts.length && !initIsFetching && (
					<div className="text-red-500">
						<ExclamationCircleOutlined className="mr-2" />
						請至少加入一款產品
					</div>
				)}
				<div className="relative mb-2">
					<Search
						placeholder="請輸入關鍵字後按下 ENTER 搜尋，每次最多返回 20 筆資料"
						allowClear
						onSearch={onSearch}
						enterButton
						loading={searchProductsResult.isFetching}
						onClick={() => setShowList(!showList)}
					/>
					<div
						className={`absolute border border-solid border-gray-200 rounded-md shadow-lg top-[100%] w-full bg-white z-50 h-[30rem] overflow-y-auto ${showList ? 'tw-block' : 'tw-hidden'}`}
						onMouseLeave={() => setShowList(false)}
					>
						<List
							rowKey="id"
							dataSource={searchProducts}
							renderItem={(product) => {
								const { id, images, name, price_html } = product
								const isInclude = selectedProducts?.some(
									({ id: theId }) => theId === product.id,
								)
								return (
									<div
										key={id}
										className={`flex items-center justify-between gap-4 p-2 mb-0 cursor-pointer hover:bg-blue-100 ${isInclude ? 'bg-blue-100' : 'bg-white'}`}
										onClick={handleClick(product)}
									>
										<img
											src={images?.[0]?.url || defaultImage}
											className="h-9 w-16 rounded object-cover"
										/>
										<div className="w-full">
											{name} #{id} {renderHTML(price_html)}
										</div>
										<div className="w-8 text-center">
											{isInclude && <CheckOutlined className="text-blue-500" />}
										</div>
									</div>
								)
							}}
						/>
					</div>
				</div>

				{!initIsFetching &&
					selectedProducts?.map(({ id, images, name, price_html }) => (
						<div
							key={id}
							className="flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md mb-2"
						>
							<div className="rounded aspect-video w-16 overflow-hidden">
								<img
									src={images?.[0]?.url || defaultImage}
									className="w-full h-full rounded object-cover"
								/>
							</div>
							<div className="flex-1">
								{name} #{id} {renderHTML(price_html)}
							</div>
							<div className="w-8 text-right">
								<PopconfirmDelete
									popconfirmProps={{
										onConfirm: () => {
											setSelectedProducts(
												selectedProducts?.filter(
													({ id: productId }) => productId !== id,
												),
											)
										},
									}}
								/>
							</div>
						</div>
					))}

				{/* Loading */}
				{initIsFetching &&
					initPIdsExcludedCourseId.map((id) => (
						<div
							key={id}
							className="flex items-center justify-start gap-4 border border-solid border-gray-200 p-2 rounded-md mb-2 animate-pulse"
						>
							<div className="bg-slate-300 h-9 w-16 rounded object-cover" />
							<div>
								<div className="bg-slate-300 h-3 w-20 mb-1" />
								<div className="bg-slate-300 h-3 w-32" />
							</div>
						</div>
					))}
			</div>

			<Item name={['regular_price']} label="此銷售組合原價" hidden>
				<InputNumber
					addonBefore="NT$"
					className="w-full [&_input]:text-right [&_.ant-input-number]:bg-white [&_.ant-input-number-group-addon]:bg-[#fafafa]  [&_.ant-input-number-group-addon]:text-[#1f1f1f]"
					min={0}
					disabled
				/>
			</Item>
			<Item
				name={['sale_price']}
				label="方案折扣價"
				help={
					<div className="mb-4">
						<div className="grid grid-cols-2 gap-x-4">
							<div>此銷售組合原訂原價</div>
							<div className="text-right pr-0">
								{getPrice({
									isFetching: initIsFetching,
									type: 'regular_price',
									products: selectedProducts,
									course,
									returnType: 'string',
									excludeMainCourse: watchExcludeMainCourse,
								})}
							</div>
							<div>此銷售組合原訂折扣價</div>
							<div className="text-right pr-0">
								{getPrice({
									isFetching: initIsFetching,
									type: 'sale_price',
									products: selectedProducts,
									course,
									returnType: 'string',
									excludeMainCourse: watchExcludeMainCourse,
								})}
							</div>
						</div>
						{watchSalePrice > watchRegularPrice && (
							<p className="text-red-500 m-0">折扣價超過原價</p>
						)}
					</div>
				}
				rules={[
					{
						required: true,
						message: '請輸入折扣價',
					},
				]}
			>
				<InputNumber
					addonBefore="NT$"
					className="w-full [&_input]:text-right"
					min={0}
					controls={false}
				/>
			</Item>

			<RangePicker
				formItemProps={{
					name: ['sale_date_range'],
					label: '銷售期間',
				}}
			/>
		</>
	)
}

export default memo(Bundle)
