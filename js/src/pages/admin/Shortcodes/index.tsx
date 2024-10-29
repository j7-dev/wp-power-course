import { memo } from 'react'
import { Tabs, TabsProps } from 'antd'

import General from './General'

const items: TabsProps['items'] = [
	{
		key: 'general',
		label: '一般',
		children: <General />,
	},
]

const index = () => {
	return <Tabs defaultActiveKey="general" items={items} />
}

export default memo(index)
