import React, { useState } from 'react'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import { bunny_cdn_hostname } from '@/utils'
import { SimpleImage } from '@/components/general'
import { Typography } from 'antd'

const PREVIEW_FILENAME = 'preview.webp'
const { Text } = Typography

const VideoItem = ({
	children,
	video,
	isSelected,
	setSelectedVideos,
}: {
	children?: React.ReactNode
	video: TVideo
	isSelected: boolean
	setSelectedVideos: React.Dispatch<React.SetStateAction<TVideo[]>>
}) => {
	const [filename, setFilename] = useState(video?.thumbnailFileName)
	const handleClick = () => {
		if (isSelected) {
			setSelectedVideos((prev) => prev.filter((v) => v.guid !== video.guid))
		} else {
			setSelectedVideos((prev) => [...prev, video])
		}
	}
	return (
		<div className="w-36">
			<SimpleImage
				onClick={handleClick}
				onMouseEnter={() => {
					setFilename(PREVIEW_FILENAME)
				}}
				onMouseLeave={() => {
					setFilename(video?.thumbnailFileName)
				}}
				className={`rounded-md overflow-hidden cursor-pointer ${
					isSelected
						? 'outline outline-4 outline-yellow-300 outline-offset-1'
						: ''
				}`}
				loadingClassName="text-sm text-gray-500 font-bold"
				src={`https://${bunny_cdn_hostname}/${video.guid}/${filename}`}
			>
				{children}
			</SimpleImage>
			<Text className=" text-xs" ellipsis>
				{video.title}
			</Text>
		</div>
	)
}

export default VideoItem
