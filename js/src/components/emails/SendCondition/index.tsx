import React, { useState } from 'react'
import { Tabs } from 'antd'
import Specific from './Specific'
import Condition from './Condition'
import Variables from './Variables'

export const SendCondition = ({ email_ids }: { email_ids: string[] }) => {
	const [activeKey, setActiveKey] = useState('condition')
	return (
		<div className="grid grid-cols-1 lg:grid-cols-[1fr_32rem] gap-x-4">
			<Tabs
				activeKey={activeKey}
				onChange={(key) => {
					setActiveKey(key as string)
				}}
				items={[
					{
						label: '設定發信時機',
						key: 'condition',
						children: <Condition email_ids={email_ids} />,
					},
					{
						label: '手動發給指定用戶',
						key: 'specific',
						children: <Specific email_ids={email_ids} />,
					},
				]}
			/>
			<Tabs
				defaultActiveKey="avl_variables"
				items={[
					{
						label: '可用變數',
						key: 'Variables',
						children: <Variables activeKey={activeKey} />,
					},
				]}
			/>
		</div>
	)
}
