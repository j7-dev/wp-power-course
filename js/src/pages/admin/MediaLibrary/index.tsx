import React from 'react'
import { Tabs, TabsProps } from 'antd'
import { FaPhotoVideo } from 'react-icons/fa'
import { CloudUploadOutlined } from '@ant-design/icons'
import { Heading } from '@/components/general'
import VideoList from './VideoList'

const items: TabsProps['items'] = [
	{
		key: 'upload-video',
		label: '上傳影片到 Bunny',
		children: 'Content of Tab Pane 1',
		icon: <CloudUploadOutlined />,
	},
	{
		key: 'bunny-media-library',
		label: 'Bunny 媒體庫',
		children: <VideoList />,
		icon: <FaPhotoVideo />,
	},
]

const index = () => {
	return (
		<>
			<Heading>選擇或上傳影片</Heading>
			<Tabs defaultActiveKey="bunny-media-library" items={items} type="card" />
		</>
	)
}

export default index
