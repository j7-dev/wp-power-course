import React, { FC, useState } from 'react'
import Filter from './Filter'
import { useInfiniteList } from '@refinedev/core'
import { bunny_library_id } from '@/utils'
import { Button } from 'antd'
import { TVideo } from '@/bunny/MediaLibrary/types'
import { filesInQueueAtom, TMediaLibraryProps } from '@/bunny/MediaLibrary'
import { useAtomValue } from 'jotai'
import VideoInfo from './VideoInfo'
import VideoItem from './VideoItem'
import { LoadingCard } from 'antd-toolkit'
import FileEncodeProgress from './FileEncodeProgress'
import FileUploadProgress from './FileUploadProgress'

const PAGE_SIZE = 30

const VideoList: FC<TMediaLibraryProps> = ({
	selectedVideos,
	setSelectedVideos,
	selectButtonProps,
	limit,
}) => {
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

	const isSearchFetching = isFetching && !isFetchingNextPage

	return (
		<>
			<Filter
				selectedVideos={selectedVideos}
				setSelectedVideos={setSelectedVideos}
				setSearch={setSearch}
				disabled={isFetching}
				loading={isSearchFetching}
				selectButtonProps={selectButtonProps}
			/>
			<div className="flex">
				<div className="flex-1">
					<div className="flex flex-wrap gap-4">
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
									selectedVideos={selectedVideos}
									limit={limit}
									setSelectedVideos={setSelectedVideos}
								/>
							))}

						{isFetching &&
							new Array(PAGE_SIZE).fill(0).map((_, index) => (
								<div key={index} className="w-36">
									<LoadingCard ratio="aspect-video" />
									<LoadingCard ratio="h-3 !p-0 rounded-sm">&nbsp;</LoadingCard>
								</div>
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
