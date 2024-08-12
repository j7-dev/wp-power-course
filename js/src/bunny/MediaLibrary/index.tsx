import React, { FC, useState } from 'react'
import { Tabs, TabsProps, ButtonProps } from 'antd'
import { FaPhotoVideo } from 'react-icons/fa'
import { CloudUploadOutlined } from '@ant-design/icons'
import { Heading } from '@/components/general'
import VideoList from './VideoList'
import UploadVideo from './UploadVideo'
import { atom } from 'jotai'
import { RcFile } from 'antd/lib/upload/interface'
import { TVideo } from '@/bunny/MediaLibrary/types'

export type TMediaLibraryProps = {
	selectedVideos: TVideo[]
	setSelectedVideos: React.Dispatch<React.SetStateAction<TVideo[]>>
	limit?: number
	selectButtonProps?: ButtonProps
}

export const MediaLibrary: FC<TMediaLibraryProps> = (props) => {
	const [activeKey, setActiveKey] = useState('bunny-media-library')
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
			children: <VideoList {...props} />,
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
