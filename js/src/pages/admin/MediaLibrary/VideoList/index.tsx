import React, { useState } from 'react'
import Filter from './Filter'
import { useInfiniteList } from '@refinedev/core'
import { bunny_library_id } from '@/utils'
import { Button } from 'antd'
import { TGetVideosResponse, TVideo } from '@/pages/admin/MediaLibrary/types'
import VideoInfo from './VideoInfo'
import VideoItem from './VideoItem'

const VideoList = () => {
	const {
		data,
		isError,
		isLoading,
		hasNextPage,
		fetchNextPage,
		isFetchingNextPage,
	} = useInfiniteList<TGetVideosResponse>({
		dataProviderName: 'bunny-stream',
		resource: `${bunny_library_id}/videos`,
		pagination: {
			pageSize: 4,
		},
	})

	const allVideos: TVideo[] = [].concat(
		...(data?.pages ?? []).map((page) => page?.data?.items || []),
	)

	const [selectedVideo, setSelectedVideo] = useState<TVideo | null>(null)

	return (
		<>
			<Filter />
			<div className="flex">
				<div className="flex-1">
					<div className="flex flex-wrap gap-4">
						{allVideos.map((video) => (
							<VideoItem
								key={video.guid}
								video={video}
								isSelected={selectedVideo?.guid === video.guid}
								setSelectedVideo={setSelectedVideo}
							/>
						))}
					</div>
					{hasNextPage && (
						<Button
							type="primary"
							onClick={() => fetchNextPage()}
							disabled={isFetchingNextPage}
							loading={isFetchingNextPage}
						>
							{isFetchingNextPage ? 'Loading more...' : 'Load More'}
						</Button>
					)}
				</div>
				<div className="w-[28rem]">
					{selectedVideo && <VideoInfo video={selectedVideo} />}
				</div>
			</div>
		</>
	)
}

export default VideoList
