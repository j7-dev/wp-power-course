import React, { useState } from 'react'
import { MediaLibrary } from '@/bunny'
import { TVideo } from '@/bunny/MediaLibrary/types'

const MediaLibraryPage = () => {
	const [selectedVideos, setSelectedVideos] = useState<TVideo[]>([])
	return (
		<MediaLibrary
			selectedVideos={selectedVideos}
			setSelectedVideos={setSelectedVideos}
			limit={1}
		/>
	)
}

export default MediaLibraryPage
