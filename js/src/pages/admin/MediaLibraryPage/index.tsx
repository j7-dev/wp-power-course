import React, { useState } from 'react'
import { MediaLibrary } from '@/bunny'
import { TBunnyVideo } from '@/bunny/types'

const MediaLibraryPage = () => {
	const [selectedVideos, setSelectedVideos] = useState<TBunnyVideo[]>([])
	return (
		<MediaLibrary
			selectedVideos={selectedVideos}
			setSelectedVideos={setSelectedVideos}
		/>
	)
}

export default MediaLibraryPage
