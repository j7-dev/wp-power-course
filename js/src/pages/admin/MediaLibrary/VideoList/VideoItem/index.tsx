import React, { useState } from 'react'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import { bunny_cdn_hostname } from '@/utils'
import { SimpleImage } from '@/components/general'

const PREVIEW_FILENAME = 'preview.webp'

const VideoItem = ({
	video,
	isSelected,
	setSelectedVideo,
}: {
	video: TVideo
	isSelected: boolean
	setSelectedVideo: React.Dispatch<React.SetStateAction<TVideo | null>>
}) => {
	const [filename, setFilename] = useState(video?.thumbnailFileName)
	const handleClick = () => {
		if (isSelected) {
			setSelectedVideo(null)
		} else {
			setSelectedVideo(video)
		}
	}
	return (
		<SimpleImage
			onClick={handleClick}
			onMouseEnter={() => {
				setFilename(PREVIEW_FILENAME)
			}}
			onMouseLeave={() => {
				setFilename(video?.thumbnailFileName)
			}}
			className={`w-36 rounded-md overflow-hidden cursor-pointer ${
				isSelected
					? 'outline outline-4 outline-yellow-300 outline-offset-1'
					: ''
			}`}
			loadingClassName="text-sm text-gray-500 font-bold"
			src={`https://${bunny_cdn_hostname}/${video.guid}/${filename}`}
		/>
	)
}

export default VideoItem
