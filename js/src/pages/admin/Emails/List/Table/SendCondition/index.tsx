import React from 'react'
import { Tabs } from 'antd'
import Specific from './Specific'
import Condition from './Condition'

const SendCondition = ({ email_ids }: { email_ids: string[] }) => {
	return (
		<>
			<Tabs
				defaultActiveKey="1"
				items={[
					{
						label: '發給指定用戶',
						key: 'specific',
						children: <Specific email_ids={email_ids} />,
					},
					{
						label: '設定發信時機',
						key: 'condition',
						children: <Condition email_ids={email_ids} />,
					},
				]}
			/>
		</>
	)
}

export default SendCondition
