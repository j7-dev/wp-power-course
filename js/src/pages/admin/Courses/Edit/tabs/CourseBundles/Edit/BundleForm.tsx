import {
	CheckOutlined,
	PlusOutlined,
	ExclamationCircleOutlined,
} from '@ant-design/icons'
import { useList } from '@refinedev/core'
import { Form, Input, Tag, List, Select, Switch, InputNumber } from 'antd'
import { renderHTML } from 'antd-toolkit'
import { useAtomValue, useAtom } from 'jotai'
import React, { useState, memo, useEffect } from 'react'

import defaultImage from '@/assets/images/defaultImage.jpg'
import { PopconfirmDelete, Heading } from '@/components/general'
import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { productTypes } from '@/utils'

import {
	courseAtom,
	selectedProductsAtom,
	bundleProductAtom,
	TSelectedProduct,
} from './atom'
import Gallery from './Gallery'
import ProductPriceFields from './ProductPriceFields'
import {
	BUNDLE_TYPE_OPTIONS,
	INCLUDED_PRODUCT_IDS_FIELD_NAME,
	PRODUCT_QUANTITIES_FIELD_NAME,
	PRODUCT_TYPE_OPTIONS,
	getPrice,
} from './utils'

const { Search } = Input
const { Item } = Form

const BundleForm = () => {
	const course = useAtomValue(courseAtom)
	const record = useAtomValue(bundleProductAtom)
	const [selectedProducts, setSelectedProducts] = useAtom(selectedProductsAtom)

	const { id: courseId } = course as TCourseRecord

	const [searchKeyWord, setSearchKeyWord] = useState<string>('')
	const [showList, setShowList] = useState<boolean>(false)
	const bundleProductForm = Form.useFormInstance()

	const onSearch = (value: string) => {
		setSearchKeyWord(value)
		setShowList(true)
	}

	const searchProductsResult = useList<TBundleProductRecord>({
		dataProviderName: 'power-course',
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
				field: 'type',
				operator: 'in',
				value: ['simple', 'subscription'],
			},
			{
				field: 'meta_key',
				operator: 'eq',
				value: 'link_course_ids',
			},
			{
				field: 'meta_compare',
				operator: 'eq',
				value: 'NOT EXISTS',
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
				selectedProducts.filter(({ id }) => id !== product.id)
			)
		} else {
			// 當前列表中沒有這個商品，所以要加入（預設數量 1）
			setSelectedProducts([...selectedProducts, { ...product, qty: 1 }])
		}
	}

	// 更新指定商品的數量
	const handleQtyChange = (productId: string, newQty: number | null) => {
		const qty = Math.max(1, Math.min(999, Math.floor(newQty || 1)))
		setSelectedProducts(
			selectedProducts.map((p) =>
				p.id === productId ? { ...p, qty } : p
			)
		)
	}

	// 取得除了目前課程以外的已選商品 IDs（用於初始化 fetch）
	const recordProductIds = record?.[INCLUDED_PRODUCT_IDS_FIELD_NAME] || []
	const recordQuantities = record?.pbp_product_quantities || {}
	const initPIdsExcludedCourseId = recordProductIds.filter(
		(id) => id !== courseId
	)

	// 初始狀態
	const { data: initProductsData, isFetching: initIsFetching } =
		useList<TBundleProductRecord>({
			dataProviderName: 'power-course',
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
			// 初始化商品，附上數量
			const productsWithQty: TSelectedProduct[] = []

			// 如果 record 中 pbp_product_ids 包含 courseId，將 course 加入
			const courseIncluded = recordProductIds.includes(courseId)
			if (courseIncluded && course) {
				productsWithQty.push({
					...(course as unknown as TBundleProductRecord),
					qty: Number(recordQuantities[courseId]) || 1,
				})
			}

			// 加入其他商品
			includedProducts.forEach((product) => {
				productsWithQty.push({
					...product,
					qty: Number(recordQuantities[product.id]) || 1,
				})
			})

			setSelectedProducts(productsWithQty)
		}
	}, [initIsFetching])

	useEffect(() => {
		// 選擇商品改變時，同步更新到表單上
		const productIds = selectedProducts.map(({ id }) => id)
		bundleProductForm.setFieldValue(
			[INCLUDED_PRODUCT_IDS_FIELD_NAME],
			productIds
		)

		// 同步 quantities
		const quantities: Record<string, number> = {}
		selectedProducts.forEach(({ id, qty }) => {
			quantities[id] = qty || 1
		})
		bundleProductForm.setFieldValue(
			[PRODUCT_QUANTITIES_FIELD_NAME],
			JSON.stringify(quantities)
		)

		bundleProductForm.setFieldValue(
			['regular_price'],
			getPrice({
				type: 'regular_price',
				products: selectedProducts,
			})
		)
	}, [
		selectedProducts.length,
		// 當數量改變時也需要重新計算
		selectedProducts.map((p) => p.qty).join(','),
	])

	const bundlePrices = {
		regular_price: getPrice({
			isFetching: initIsFetching,
			type: 'regular_price',
			products: selectedProducts,
			returnType: 'string',
		}),
		sale_price: getPrice({
			isFetching: initIsFetching,
			type: 'sale_price',
			products: selectedProducts,
			returnType: 'string',
		}),
	}

	// 判斷目前課程是否在選中列表中
	const isCourseSelected = selectedProducts.some(({ id }) => id === courseId)

	return (
		<>
			<Item name={['id']} hidden />
			<Gallery limit={1} />
			<Item
				name={['bundle_type']}
				label="銷售方案種類"
				initialValue={BUNDLE_TYPE_OPTIONS[0].value}
				hidden={false}
			>
				<Select options={BUNDLE_TYPE_OPTIONS} />
			</Item>
			<Item
				name={['type']}
				label="銷售方案商品種類"
				initialValue={PRODUCT_TYPE_OPTIONS[0].value}
			>
				<Select options={PRODUCT_TYPE_OPTIONS} />
			</Item>
			<Item
				name={['bundle_type_label']}
				label="銷售方案種類顯示文字"
				tooltip="銷售方案名稱上方的紅色小字"
			>
				<Input />
			</Item>
			<Item
				name={['name']}
				label="銷售方案名稱"
				rules={[
					{
						required: true,
						message: '請輸入銷售方案名稱',
					},
				]}
			>
				<Input />
			</Item>
			<Item name={['purchase_note']} label="銷售方案說明">
				<Input.TextArea rows={8} />
			</Item>

			<Item name={[INCLUDED_PRODUCT_IDS_FIELD_NAME]} initialValue={[]} hidden />
			<Item name={[PRODUCT_QUANTITIES_FIELD_NAME]} hidden />

			<Heading className="mb-3">自由搭配你的銷售方案，選擇要加入的商品</Heading>

			<div className="border-2 border-dashed rounded-xl p-4 mb-8 border-blue-500">
				<div className="text-primary mb-2">
					<ExclamationCircleOutlined className="mr-2" />
					您也可以選擇不加入產品，單純創建課程的定期定額銷售方案
				</div>
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
						className={`absolute border border-solid border-gray-200 rounded-md shadow-lg top-[100%] w-full bg-white z-50 max-h-[30rem] overflow-y-auto ${showList ? 'tw-block' : 'tw-hidden'}`}
						onMouseLeave={() => setShowList(false)}
					>
						<List
							rowKey="id"
							dataSource={searchProducts}
							renderItem={(product) => {
								const { id, images, name, price_html } = product
								const isInclude = selectedProducts?.some(
									({ id: theId }) => theId === product.id
								)
								const tag = productTypes.find(
									(productType) => productType.value === product.type
								)
								return (
									<div
										key={id}
										className={`flex items-center justify-between gap-4 p-2 mb-0 cursor-pointer hover:bg-blue-100 ${isInclude ? 'bg-blue-100' : 'bg-white'}`}
										onClick={handleClick(product)}
									>
										<img
											alt=""
											src={images?.[0]?.url || defaultImage}
											className="h-9 w-16 rounded object-cover"
										/>
										<div className="w-full">
											<span className="text-gray-400 text-xs">#{id}</span>
											{name}
											<br />
											{renderHTML(price_html)}
										</div>
										<div>
											<Tag bordered={false} color={tag?.color} className="m-0">
												{tag?.label}
											</Tag>
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

				{/* 已選商品列表（包含目前課程） */}
				{!initIsFetching &&
					selectedProducts?.map(({ id, images, name, price_html, type, qty }) => {
						const tag = productTypes.find(
							(productType) => productType.value === type
						)
						const isCurrentCourse = id === courseId

						return (
							<div
								key={id}
								className="flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md mb-2 bundle-selected-item"
							>
								<div className="rounded aspect-video w-16 overflow-hidden">
									<img
										alt=""
										src={images?.[0]?.url || defaultImage}
										className="w-full h-full rounded object-cover"
									/>
								</div>
								<div className="flex-1">
									{name} #{id} {renderHTML(price_html)}
								</div>
								{isCurrentCourse && (
									<div>
										<Tag color="blue">目前課程</Tag>
									</div>
								)}
								{!isCurrentCourse && (
									<div>
										<Tag bordered={false} color={tag?.color} className="m-0">
											{tag?.label}
										</Tag>
									</div>
								)}
								<div className="flex items-center gap-2">
									<InputNumber
										min={1}
										max={999}
										precision={0}
										value={qty}
										size="small"
										style={{ width: 60 }}
										onChange={(value) => handleQtyChange(id, value)}
									/>
								</div>
								<div className="w-8 text-right">
									<PopconfirmDelete
										popconfirmProps={{
											onConfirm: () => {
												setSelectedProducts(
													selectedProducts?.filter(
														({ id: productId }) => productId !== id
													)
												)
											},
										}}
									/>
								</div>
							</div>
						)
					})}

				{/* 如果目前課程不在選中列表中，顯示加入按鈕 */}
				{!isCourseSelected && !initIsFetching && (
					<div
						className="flex items-center justify-center gap-2 border border-dashed border-gray-300 p-2 rounded-md mb-2 cursor-pointer hover:bg-blue-50 text-gray-400 hover:text-blue-500"
						onClick={() => {
							if (course) {
								setSelectedProducts([
									{
										...(course as unknown as TBundleProductRecord),
										qty: 1,
									},
									...selectedProducts,
								])
							}
						}}
					>
						<PlusOutlined />
						<span>加入目前課程</span>
					</div>
				)}

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
								<div className="bg-slate-300 size-32" />
							</div>
						</div>
					))}
			</div>

			<ProductPriceFields bundlePrices={bundlePrices} />

			<div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
				<Item name={['virtual']} label="虛擬商品">
					<Switch />
				</Item>
				<Item name={['status']} hidden />
			</div>
		</>
	)
}

export default memo(BundleForm)
