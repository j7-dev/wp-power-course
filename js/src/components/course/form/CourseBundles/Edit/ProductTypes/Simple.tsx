import React, { memo } from 'react'
import { Form, InputNumber } from 'antd'
import { RangePicker } from '@/components/formItem'

const { Item } = Form

const Simple = ({
	bundlePrices,
}: {
	bundlePrices: { regular_price: React.ReactNode; sale_price: React.ReactNode }
}) => {
	const bundleProductForm = Form.useFormInstance()
	const watchRegularPrice = Number(
		Form.useWatch(['regular_price'], bundleProductForm),
	)
	const watchSalePrice = Number(
		Form.useWatch(['sale_price'], bundleProductForm),
	)
	const { regular_price: bundleRegularPrice, sale_price: bundleSalePrice } =
		bundlePrices
	return (
		<>
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
							<div className="text-right pr-0">{bundleRegularPrice}</div>
							<div>此銷售組合原訂折扣價</div>
							<div className="text-right pr-0">{bundleSalePrice}</div>
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

export default memo(Simple)
