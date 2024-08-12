import React, { useState } from 'react'
import Filter from './Filter'
import { useInfiniteList } from '@refinedev/core'
import { bunny_library_id } from '@/utils'
import { Button } from 'antd'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import VideoInfo from './VideoInfo'
import VideoItem from './VideoItem'
import { LoadingCard } from 'antd-toolkit'

const PAGE_SIZE = 30

const VideoList = () => {
	const {
		data,
		isError,
		isLoading,
		hasNextPage,
		fetchNextPage,
		isFetchingNextPage,
	} = useInfiniteList<TVideo>({
		dataProviderName: 'bunny-stream',
		resource: `${bunny_library_id}/videos`,
		pagination: {
			pageSize: PAGE_SIZE,
		},
	})

	const allVideos = ([] as TVideo[]).concat(
		...(data?.pages ?? []).map((page) => page?.data || []),
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

						{(isLoading || isFetchingNextPage) &&
							new Array(PAGE_SIZE)
								.fill(0)
								.map((_, index) => (
									<LoadingCard ratio="w-36 aspect-video" key={index} />
								))}
					</div>
					{hasNextPage && (
						<div className="text-center mt-8">
							<Button
								type="link"
								onClick={() => fetchNextPage()}
								disabled={isFetchingNextPage}
							>
								顯示更多
							</Button>
						</div>
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
