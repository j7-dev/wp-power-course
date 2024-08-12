import React, { useState } from 'react'
import Filter from './Filter'
import { useInfiniteList } from '@refinedev/core'
import { bunny_library_id } from '@/utils'
import { Button } from 'antd'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import { filesInQueueAtom } from '@/pages/admin/MediaLibrary'
import { useAtomValue } from 'jotai'
import VideoInfo from './VideoInfo'
import VideoItem from './VideoItem'
import { LoadingCard } from 'antd-toolkit'
import { FileEncodeProgress, FileUploadProgress } from '@/components/general'

const PAGE_SIZE = 30

const VideoList = () => {
	const [search, setSearch] = useState('')
	const filesInQueue = useAtomValue(filesInQueueAtom)
	console.log('⭐  filesInQueue:', filesInQueue)
	const {
		data,
		isError,
		isLoading,
		hasNextPage,
		fetchNextPage,
		isFetchingNextPage,
		isFetching,
	} = useInfiniteList<TVideo>({
		dataProviderName: 'bunny-stream',
		resource: `${bunny_library_id}/videos`,
		pagination: {
			pageSize: PAGE_SIZE,
		},
		filters: [
			{
				field: 'search',
				operator: 'eq',
				value: search,
			},
		],
	})

	const allVideos = ([] as TVideo[]).concat(
		...(data?.pages ?? []).map((page) => page?.data || []),
	)

	const [selectedVideos, setSelectedVideos] = useState<TVideo[]>([])

	const isSearchFetching = isFetching && !isFetchingNextPage

	const fakes = [
		// {
		// 	key: 'rc-upload-1723454876057-3',
		// 	file: {},
		// 	status: 'active',
		// 	videoId: '',
		// 	isEncoding: false,
		// 	encodeProgress: 0,
		// },
		// {
		// 	key: 'rc-upload-1723454876057-4',
		// 	file: {},
		// 	status: 'active',
		// 	videoId: '',
		// 	isEncoding: true,
		// 	encodeProgress: 0,
		// },
	]

	return (
		<>
			<Filter
				selectedVideos={selectedVideos}
				setSelectedVideos
				setSearch={setSearch}
				disabled={isFetching}
				loading={isSearchFetching}
			/>
			<div className="flex">
				<div className="flex-1">
					<div className="flex flex-wrap gap-4">
						{fakes.map((fileInQueue) =>
							fileInQueue?.isEncoding ? (
								<div
									key={fileInQueue?.key}
									className="w-36 aspect-video bg-gray-200 rounded-md px-4 py-2 flex flex-col justify-center items-center"
								>
									<FileEncodeProgress fileInQueue={fileInQueue} />
								</div>
							) : (
								<div
									key={fileInQueue?.key}
									className="w-36 aspect-video bg-gray-200 rounded-md px-4 py-2 flex flex-col justify-center items-center"
								>
									<FileUploadProgress
										key={fileInQueue?.key}
										fileInQueue={fileInQueue}
									/>
								</div>
							),
						)}

						{filesInQueue.map((fileInQueue) =>
							fileInQueue?.isEncoding ? (
								<div
									key={fileInQueue?.key}
									className="w-36 aspect-video bg-gray-200 rounded-md px-4 py-2 flex flex-col justify-center items-center"
								>
									<FileEncodeProgress fileInQueue={fileInQueue} />
								</div>
							) : (
								<div
									key={fileInQueue?.key}
									className="w-36 aspect-video bg-gray-200 rounded-md px-4 py-2 flex flex-col justify-center items-center"
								>
									<FileUploadProgress
										key={fileInQueue?.key}
										fileInQueue={fileInQueue}
									/>
								</div>
							),
						)}

						{!isSearchFetching &&
							allVideos.map((video) => (
								<VideoItem
									key={video.guid}
									video={video}
									isSelected={selectedVideos?.some(
										(selectedVideo) => selectedVideo.guid === video.guid,
									)}
									setSelectedVideos={setSelectedVideos}
								/>
							))}

						{isFetching &&
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
								disabled={isFetching}
							>
								顯示更多
							</Button>
						</div>
					)}
				</div>
				<div className="w-[28rem]">
					{selectedVideos?.length > 0 && (
						<VideoInfo video={selectedVideos.slice(-1)[0]} />
					)}
				</div>
			</div>
		</>
	)
}

export default VideoList
