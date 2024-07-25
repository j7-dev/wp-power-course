import React from 'react'
import { Button } from 'antd'
import { Link } from 'react-router-dom'
import { DoubleRightOutlined } from '@ant-design/icons'

const NoLibraryId = () => {
	return (
		<div className="aspect-video shadow rounded-lg border border-dashed border-gray-300 flex flex-col items-center justify-center">
			<div className="text-lg font-bold">缺少 Bunny Library Id</div>
			<Link to="/settings">
				<Button type="link" icon={<DoubleRightOutlined />} iconPosition="end">
					前往設定
				</Button>
			</Link>
		</div>
	)
}

export default NoLibraryId
