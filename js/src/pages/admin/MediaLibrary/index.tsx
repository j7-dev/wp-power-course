import React, { useState } from 'react'
import { Tabs, TabsProps } from 'antd'
import { FaPhotoVideo } from 'react-icons/fa'
import { CloudUploadOutlined } from '@ant-design/icons'
import { Heading } from '@/components/general'
import VideoList from './VideoList'
import UploadVideo from './UploadVideo'
import { atom } from 'jotai'
import { RcFile } from 'antd/lib/upload/interface'

const index = () => {
	const [activeKey, setActiveKey] = useState('upload-video')
	const items: TabsProps['items'] = [
		{
			key: 'upload-video',
			label: '上傳影片到 Bunny',
			children: <UploadVideo setActiveKey={setActiveKey} />,
			icon: <CloudUploadOutlined />,
		},
		{
			key: 'bunny-media-library',
			label: 'Bunny 媒體庫',
			children: <VideoList />,
			icon: <FaPhotoVideo />,
		},
	]

	return (
		<>
			<Heading>選擇或上傳影片</Heading>
			<Tabs
				activeKey={activeKey}
				onChange={setActiveKey}
				items={items}
				type="card"
			/>
		</>
	)
}

export default index

export type TUploadStatus =
	| 'active'
	| 'normal'
	| 'exception'
	| 'success'
	| undefined

export type TFileInQueue = {
	key: string
	file: RcFile
	status?: TUploadStatus
	videoId: string
	isEncoding: boolean
	encodeProgress: number
	preview?: string
}

export const filesInQueueAtom = atom<TFileInQueue[]>([])
