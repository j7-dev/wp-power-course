import { memo } from 'react'
import { Form, Button, Tabs, TabsProps } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'
import General from './General'
import Appearance from './Appearance'

const items: TabsProps['items'] = [
	{
		key: 'general',
		label: '一般設定',
		children: <General />,
	},
	{
		key: 'appearance',
		label: '外觀設定',
		children: <Appearance />,
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
