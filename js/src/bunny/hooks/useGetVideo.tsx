import { useOne } from '@refinedev/core'
import { UseQueryOptions, QueryKey } from '@tanstack/react-query'
import {
	GetOneResponse,
	BaseRecord,
	HttpError,
} from '@refinedev/core/dist/contexts/data/types'
import { useVideoLibrary } from '@/bunny'

type TUseGetVideoParams<T = BaseRecord> = {
	videoId: string
	queryOptions?:
		| UseQueryOptions<GetOneResponse<T>, HttpError, GetOneResponse<T>, QueryKey>
		| undefined
}

export type TGetVideoResponse = {
	videoLibraryId: number
	guid: string
	title: string
	dateUploaded: string
	views: number
	isPublic: false
	length: number
	status: number
	framerate: number
	rotation: number
	width: number
	height: number
	availableResolutions: string
	thumbnailCount: number
	encodeProgress: number
	storageSize: number
	captions: Array<any> //TYPE
	hasMP4Fallback: true
	collectionId: string
	thumbnailFileName: string
	averageWatchTime: number
	totalWatchTime: number
	category: string
	chapters: Array<any> //TYPE
	moments: Array<any> //TYPE
	metaTags: Array<any> //TYPE
	transcodingMessages: {
		timeStamp: string
		level: number
		issueCode: number
		message: string
		value: string
	}[]
}

export const useGetVideo = ({
	videoId,
	queryOptions,
}: TUseGetVideoParams<TGetVideoResponse>) => {
	const { libraryId } = useVideoLibrary()
	const result = useOne<TGetVideoResponse>({
		resource: `${libraryId}/videos`,
		id: videoId,
		dataProviderName: 'bunny-stream',
		queryOptions,
	})

	return result
}
