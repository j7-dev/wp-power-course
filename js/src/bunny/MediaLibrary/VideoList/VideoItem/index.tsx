import React, { useState } from 'react'
import { TVideo } from '@/bunny/MediaLibrary/types'
import { bunny_cdn_hostname } from '@/utils'
import { SimpleImage } from '@/components/general'
import { Typography, message } from 'antd'

const PREVIEW_FILENAME = 'preview.webp'
const { Text } = Typography

const VideoItem = ({
	children,
	video,
	selectedVideos,
	setSelectedVideos,
	limit,
}: {
	children?: React.ReactNode
	video: TVideo
	selectedVideos: TVideo[]
	setSelectedVideos:
		| React.Dispatch<React.SetStateAction<TVideo[]>>
		| ((
				_videosOrFunction: TVideo[] | ((_videos: TVideo[]) => TVideo[]),
		  ) => void)
	limit: number | undefined
}) => {
	const [filename, setFilename] = useState(video?.thumbnailFileName)
	const isSelected = selectedVideos?.some(
		(selectedVideo) => selectedVideo.guid === video.guid,
	)

	const handleClick = () => {
		if (isSelected) {
			setSelectedVideos((prev) => prev.filter((v) => v.guid !== video.guid))
		} else {
			if (limit && selectedVideos.length >= limit) {
				message.warning({
					key: 'limit',
					content: `最多只能選取${limit}個影片`,
				})
				setSelectedVideos((prev) => [...prev.slice(1), video])
				return
			}
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
