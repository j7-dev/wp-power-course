/**
 * TODO
 */

import React from 'react'
import { useOne } from '@refinedev/core'
import { UseQueryOptions, QueryKey } from '@tanstack/react-query'
import {
  GetOneResponse,
  BaseRecord,
  HttpError,
} from '@refinedev/core/dist/contexts/data/types'

type TUseGetVideoParams<T = BaseRecord> = {
  libraryId: number
  videoId: string
  queryOptions?:
    | UseQueryOptions<GetOneResponse<T>, HttpError, GetOneResponse<T>, QueryKey>
    | undefined
}

type TGetVideoResponse = {
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
  captions: Array<any> //TODO
  hasMP4Fallback: true
  collectionId: string
  thumbnailFileName: string
  averageWatchTime: number
  totalWatchTime: number
  category: string
  chapters: Array<any> //TODO
  moments: Array<any> //TODO
  metaTags: Array<any> //TODO
  transcodingMessages: {
    timeStamp: string
    level: number
    issueCode: number
    message: string
    value: string
  }[]
}

export const useGetVideo = ({
  libraryId,
  videoId,
  queryOptions,
}: TUseGetVideoParams<TGetVideoResponse>) => {
  const result = useOne<TGetVideoResponse>({
    resource: `${libraryId}/videos`,
    id: videoId,
    dataProviderName: 'bunny-stream',
    queryOptions,
  })

  return result
}
