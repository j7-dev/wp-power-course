import React from 'react'
import { Tooltip, Button } from 'antd'

const SelectedUser = ({
	user_ids,
	onClick,
}: {
	user_ids: string[]
	onClick?: () => void
}) => {
	return (
		<>
			{!!user_ids.length && (
				<div className="mt-4">
					<Tooltip title={`包含用戶 id: ${user_ids.join(',')}`}>
						已選擇 {user_ids.length} 個用戶
					</Tooltip>
					{onClick && (
						<Button type="link" onClick={onClick}>
							清除選取
						</Button>
					)}
				</div>
			)}
			{!user_ids.length && <div className="h-8 mt-4" />}
		</>
	)
}

export default SelectedUser
