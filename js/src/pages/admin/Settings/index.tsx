import { memo } from 'react'
import { Form, Button, Tabs, TabsProps } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'

import Bunny from './Bunny'
import General from './General'
import CsvUpload from './CsvUpload'

const items: TabsProps['items'] = [
	{
		key: 'general',
		label: '一般設定',
		children: <General />,
	},
	{
		key: 'bunny',
		label: 'Bunny 整合',
		children: <Bunny />,
	},
	{
		key: 'csv-upload',
		label: 'CSV 批次上傳學員權限',
		children: <CsvUpload />,
	},
]

const index = () => {
	const [form] = Form.useForm()
	const { handleSave, mutation } = useSave({ form })
	const { isLoading: isSaveLoading } = mutation
	const { isLoading: isGetLoading } = useOptions({ form })

	return (
		<Form layout="vertical" form={form} onFinish={handleSave}>
			<Tabs
				tabBarExtraContent={{
					left: (
						<Button
							className="mr-8"
							type="primary"
							htmlType="submit"
							loading={isSaveLoading}
							disabled={isGetLoading}
						>
							儲存
						</Button>
					),
				}}
				defaultActiveKey="general"
				items={items}
			/>
		</Form>
	)
}

export default memo(index)
