import { memo } from 'react'
import { Tabs, TabsProps } from 'antd'

import General from './General'
import Cart from './Cart'

const items: TabsProps['items'] = [
	{
		key: 'general',
		label: '一般',
		children: <General />,
	},
	{
		key: 'cart',
		label: '銷售卡片',
		children: <Cart />,
	},
]

const index = () => {
	return <Tabs defaultActiveKey="general" items={items} />
}

export default memo(index)
