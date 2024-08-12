import { Button } from 'antd'
import { Link } from 'react-router-dom'
import { DoubleRightOutlined } from '@ant-design/icons'

const NoLibraryId = ({
	bunny_library_id,
	bunny_stream_api_key,
}: {
	bunny_library_id?: string
	bunny_stream_api_key?: string
}) => {
	return (
		<div className="aspect-video shadow rounded-lg border border-dashed border-gray-300 flex flex-col items-center justify-center">
			{!bunny_library_id && (
				<div className="text-lg font-bold">缺少 Bunny Library Id</div>
			)}
			{!bunny_stream_api_key && (
				<div className="text-lg font-bold">缺少 Bunny Stream Api Key</div>
			)}
			<Link to="/settings">
				<Button type="link" icon={<DoubleRightOutlined />} iconPosition="end">
					前往設定
				</Button>
			</Link>
		</div>
	)
}

export default NoLibraryId
