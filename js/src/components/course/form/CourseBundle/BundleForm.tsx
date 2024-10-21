import { useEffect, useState, FC, memo } from 'react'
import { Form, InputNumber, Select, Input, FormInstance, List, Tag } from 'antd'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import dayjs from 'dayjs'
import { TProductRecord } from '@/components/product/ProductTable/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { useList } from '@refinedev/core'
import { PopconfirmDelete, Heading } from '@/components/general'
import {
	CheckOutlined,
	PlusOutlined,
	ExclamationCircleOutlined,
	LinkOutlined,
	DisconnectOutlined,
} from '@ant-design/icons'
import { TCourseRecord } from '@/pages/admin/Courses/CourseTable/types'
import { FiSwitch, RangePicker } from '@/components/formItem'
import { FileUpload } from '@/components/post'

// TODO 目前只支援簡單商品
// TODO 如何結合可變商品?

// dayjs.extend(customParseFormat)

const { Item } = Form
const { Search } = Input

const OPTIONS = [
	{ label: '合購優惠', value: 'bundle' },
	{ label: '🚧 團購優惠 (開發中...)', value: 'groupbuy', disabled: true },
]

export const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids

const BundleForm: FC<{
	form: FormInstance
	course: TCourseRecord | undefined // 課程
	record: TProductRecord | undefined // 銷售方案
}> = ({ form: bundleProductForm, course: selectedCourse, record }) => {
	const [selectedProducts, setSelectedProducts] = useState<TProductRecord[]>([])
	const [searchKeyWord, setSearchKeyWord] = useState<string>('')
	const [showList, setShowList] = useState<boolean>(false)
	const watchRegularPrice = Number(
		Form.useWatch(['regular_price'], bundleProductForm),
	)
	const watchSalePrice = Number(
		Form.useWatch(['sale_price'], bundleProductForm),
	)
	const watchBundleType = Form.useWatch(['bundle_type'], bundleProductForm)
	const watchExcludeMainCourse =
		Form.useWatch(['exclude_main_course'], bundleProductForm) === 'yes'

	const onSearch = (value: string) => {
		setSearchKeyWord(value)
	}

	const searchProductsResult = useList<TProductRecord>({
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
				field: 'posts_per_page',
				operator: 'eq',
				value: '20',
			},
			{
				field: 'exclude',
				operator: 'eq',
				value: [selectedCourse?.id],
			},
			{
				field: 'product_type',
				operator: 'eq',
				value: 'simple',
			},
		],
	})

	const searchProducts = searchProductsResult.data?.data || []

	// 處理點擊商品，有可能是加入也可能是移除

	const handleClick = (product: TProductRecord) => () => {
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

	useEffect(() => {
		// 選擇商品改變時，同步更新到表單上
		const productIds = watchExcludeMainCourse
			? selectedProducts.map(({ id }) => id)
			: [
					selectedCourse?.id,
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
				selectedCourse,
				excludeMainCourse: watchExcludeMainCourse,
			}),
		)
	}, [selectedProducts.length, watchExcludeMainCourse])

	// 將當前商品移除
	const initPIdsExcludedCourseId = (
		record?.[INCLUDED_PRODUCT_IDS_FIELD_NAME] || []
	).filter((id) => id !== selectedCourse?.id)

	// 初始狀態
	const { data: initProductsData, isFetching: initIsFetching } =
		useList<TProductRecord>({
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
				enabled: !!initPIdsExcludedCourseId,
			},
		})

	const includedProducts = initProductsData?.data || []

	useEffect(() => {
		// 有 id = 編輯方案，要將資料填入表單
		console.log('⭐  record:', { record, initIsFetching, watchBundleType })
		if (!!record && !initIsFetching) {
			// 初始化商品
			setSelectedProducts(includedProducts)
		}

		if (!record) {
			// 新增方案，清空選擇商品
			setSelectedProducts([])
			bundleProductForm.setFieldValue(
				['bundle_type_label'],
				OPTIONS.find((o) => o.value === watchBundleType)?.label,
			)
		}
	}, [record, initIsFetching, watchBundleType])

	return (
		<Form form={bundleProductForm} layout="vertical">
			{/* <Item name={['id']} hidden normalize={() => undefined}>
				<Input />
			</Item> */}
			<Item
				name={['link_course_ids']}
				initialValue={[selectedCourse?.id]}
				hidden
			/>
			<Item
				name={['bundle_type']}
				label="銷售方案種類"
				initialValue={OPTIONS[0].value}
			>
				<Select options={OPTIONS} />
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
			<Item
				name={['description']}
				label="銷售方案說明"
				normalize={(value) => value.replace(/\n/g, '<br>')}
			>
				<Input.TextArea rows={8} />
			</Item>

			<Item name={[INCLUDED_PRODUCT_IDS_FIELD_NAME]} initialValue={[]} hidden />

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

			<div className="border-2 border-dashed border-blue-500 rounded-xl p-4 mb-8">
				{/* 當前課程方案 */}
				<div
					className={`flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md ${watchExcludeMainCourse ? 'opacity-20 saturate-0' : ''}`}
				>
					<img
						src={selectedCourse?.images?.[0]?.url || defaultImage}
						className="h-9 w-16 rounded object-cover"
					/>
					<div className="w-full">
						{selectedCourse?.name} #{selectedCourse?.id}{' '}
						{renderHTML(selectedCourse?.price_html || '')}
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
						className={`absolute border border-solid border-gray-200 rounded-md shadow-lg top-[100%] w-full bg-white z-50 h-[30rem] overflow-y-auto ${showList ? 'block' : 'tw-hidden'}`}
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
				tooltip="折扣價不能超過原價"
				help={
					<div className="mb-4">
						<div className="grid grid-cols-2 gap-x-4">
							<div>此銷售組合原訂原價</div>
							<div className="text-right pr-0">
								{getPrice({
									isFetching: initIsFetching,
									type: 'regular_price',
									products: selectedProducts,
									selectedCourse,
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
									selectedCourse,
									returnType: 'string',
									excludeMainCourse: watchExcludeMainCourse,
								})}
							</div>
						</div>
						{watchSalePrice > watchRegularPrice && (
							<p className="text-red-500 m-0">折扣價不能超過原價</p>
						)}
					</div>
				}
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
			<div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
				<div>
					<p className="mb-3">課程封面圖</p>
					<div className="mb-8">
						<FileUpload />
						<Item hidden name={['files']} label="課程封面圖">
							<Input />
						</Item>
						<Item hidden name={['images']}>
							<Input />
						</Item>
					</div>
				</div>
			</div>

			<div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
				<FiSwitch
					formItemProps={{
						name: ['virtual'],
						label: '虛擬商品',
						initialValue: 'yes',
					}}
				/>

				<FiSwitch
					formItemProps={{
						name: ['status'],
						label: '發佈',
						initialValue: 'publish',
						getValueProps: (value) => ({ value: value === 'publish' }),
						normalize: (value) => (value ? 'publish' : 'draft'),
					}}
					switchProps={{
						checkedChildren: '發佈',
						unCheckedChildren: '草稿',
					}}
				/>
			</div>
		</Form>
	)
}

// 取得總金額
function getPrice({
	isFetching = false,
	type,
	products,
	selectedCourse,
	returnType = 'number',
	excludeMainCourse = false,
}: {
	isFetching?: boolean
	type: 'regular_price' | 'sale_price'
	products: TProductRecord[] | undefined
	selectedCourse: TCourseRecord | undefined
	returnType?: 'string' | 'number'
	excludeMainCourse?: boolean
}) {
	if (isFetching) {
		return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
	}

	const coursePrice = Number(
		selectedCourse?.[type] || selectedCourse?.regular_price || 0,
	)
	const total =
		Number(
			products?.reduce(
				(acc, product) =>
					acc + Number(product?.[type] || product.regular_price),
				0,
			),
		) + (excludeMainCourse ? 0 : coursePrice)

	if ('number' === returnType) return total
	return `NT$ ${total?.toLocaleString()}`
}

export default memo(BundleForm)
