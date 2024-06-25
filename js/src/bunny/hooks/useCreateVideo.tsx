/**
 * DELETE
 */

import React, { useEffect } from 'react'
import { useCreate, useCustomMutation, useApiUrl } from '@refinedev/core'
import { Progress } from 'antd'
import { NotificationInstance } from 'antd/es/notification/interface'
import { nanoid } from 'nanoid'
import { useVideoLibrary } from '@/bunny/hooks'

type TCreateVideoResponse = {
  videoLibraryId: number
  guid: string
  title: string
  dateUploaded: string
  views: number
  isPublic: boolean
  length: number
  status: number
  framerate: number
  rotation: null //TODO
  width: number
  height: number
  availableResolutions: null //TODO
  thumbnailCount: number
  encodeProgress: number
  storageSize: number
  captions: Array<any> //TODO
  hasMP4Fallback: boolean
  collectionId: string
  thumbnailFileName: string
  averageWatchTime: number
  totalWatchTime: number
  category: string
  chapters: Array<any> //TODO
  moments: Array<any> //TODO
  metaTags: Array<any> //TODO
  transcodingMessages: Array<any> //TODO
}

type TCreateVideoParams = {
  title: string
  collectionId?: string
  thumbnailTime?: number
}

export const useCreateVideo = () => {
  const { libraryId } = useVideoLibrary()
  const apiUrl = useApiUrl('bunny-stream')
  const result = useCreate<TCreateVideoResponse>()
  const { mutate: create } = result
  const { mutate: upload } = useCustomMutation()

  const createVideo = ({
    values,
    binary,
    notificationApi,
  }: {
    values: TCreateVideoParams
    binary: Blob
    notificationApi: NotificationInstance
  }) => {
    const key = nanoid()
    const fileSize = binary.size

    const notification = ({
      percent = 0,
      status = 'active',
    }: {
      percent?: number
      status?: 'active' | 'exception' | 'success' | 'normal' | undefined
    }) => {
      let label = '上傳中'
      switch (status) {
        case 'exception':
          label = '上傳失敗'
          break
        case 'success':
          label = '上傳已完成'
          break
        default:
          break
      }

      notificationApi.info({
        key,
        message: `影片${label} - ${values?.title}`,
        description: <Progress percent={percent} status={status} />,
      })
    }

    // 創建影片

    notification({})

    create(
      {
        resource: `${libraryId}/videos`,
        dataProviderName: 'bunny-stream',
        values,
        meta: {
          headers: { 'Content-Type': 'application/json' },
        },
      },
      {
        onSuccess: (data) => {
          const { guid: videoId } = data?.data || {}

          // 上傳影片

          notification({
            percent: 22,
          })

          upload(
            {
              url: `${apiUrl}/${libraryId}/videos/${videoId}?enabledResolutions=720p%2C1080p`,
              method: 'put',
              dataProviderName: 'bunny-stream',
              values: binary,
              config: {
                headers: {
                  'Content-Type': 'video/*',
                },
              },
            },
            {
              onSuccess: () => {
                notification({
                  percent: 100,
                  status: 'success',
                })
              },
              onError: (error) => {
                console.log('upload error', error)
                notification({
                  percent: 90,
                  status: 'exception',
                })
              },
            },
          )
        },
        onError: (error) => {
          console.log('createVideo error', error)
          notification({
            percent: 18,
            status: 'exception',
          })
        },
      },
    )
  }

  return {
    ...result,
    createVideo,
  }
}
