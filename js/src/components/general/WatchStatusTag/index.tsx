import React, { memo } from 'react'
import dayjs from 'dayjs'
import { Tag } from 'antd'

const WatchStatusTagComponent = ({ expireDate }: { expireDate: number }) => {
	const currentTimestamp = dayjs().unix()

	if (!expireDate) {
		return <Tag color="blue">無期限</Tag>
	}

	if (currentTimestamp > expireDate) {
		return <Tag color="magenta">已過期</Tag>
	}

	return <Tag color="green">未過期</Tag>
}

export const WatchStatusTag = memo(WatchStatusTagComponent)
