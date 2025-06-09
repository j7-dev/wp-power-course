import { Form, InputNumber, Select } from 'antd'
import { FiSwitch as Switch } from '@/components/formItem'
import { useEnv } from '@/hooks'
import { PRODUCT_STOCK_STATUS } from 'antd-toolkit/wp'

const { Item } = Form

const StockFields = () => {
	const { NOTIFY_LOW_STOCK_AMOUNT } = useEnv()
	const form = Form.useFormInstance()
	const manageStockName = ['manage_stock']
	const watchManageStock = Form.useWatch(manageStockName, form)
	const enableStockManagement = watchManageStock === 'yes'
	return (
		<>
			<Item name={['backorders']} label="允許無庫存下單">
				<Select
					className="w-full"
					options={[
						{ label: '是，且通知顧客', value: 'notify' },
						{ label: '是', value: 'yes' },
						{ label: '否', value: 'no' },
					]}
					allowClear
				/>
			</Item>

			<Switch
				formItemProps={{
					name: ['manage_stock'],
					label: '管理庫存',
				}}
			/>
			{!enableStockManagement && (
				<Item name={['stock_status']} label="庫存狀態">
					<Select
						className="w-full"
						options={PRODUCT_STOCK_STATUS}
						allowClear
					/>
				</Item>
			)}

			{enableStockManagement && (
				<>
					<Item name={['stock_quantity']} label="庫存數量">
						<InputNumber className="w-full" />
					</Item>
					<Item name={['low_stock_amount']} label="低庫存臨界值">
						<InputNumber
							placeholder={`全店門檻(${NOTIFY_LOW_STOCK_AMOUNT})`}
							className="w-full"
						/>
					</Item>
				</>
			)}

			<Switch
				formItemProps={{
					name: ['sold_individually'],
					label: '限購一件',
					help: '限制每筆訂單購買一項商品',
					tooltip:
						'勾選即可讓顧客在一筆訂單中僅能購買一項商品。 此功能對於限量商品非常實用，例如藝術品或手工商品。',
				}}
			/>
		</>
	)
}

export default StockFields
