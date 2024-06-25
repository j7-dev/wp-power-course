import { useState } from 'react'
import { GetProp, UploadFile, UploadProps } from 'antd'
import { useVideoLibrary } from './useVideoLibrary'
import { NotificationInstance } from 'antd/es/notification/interface'
import { bunnyStreamAxios } from '@/rest-data-provider/bunny-stream'
import { RcFile } from 'antd/lib/upload/interface'
import { nanoid } from 'nanoid'
import { useGetVideo } from '@/bunny/hooks'
import { filesInQueueAtom, TFileInQueue } from '@/pages/admin/Courses'
import { useAtom } from 'jotai'

type FileType = Parameters<GetProp<UploadProps, 'beforeUpload'>>[0]

/**
 * accept: string
 * @example '.jpg,.png' , 'image/*', 'video/*', 'audio/*', 'image/png,image/jpeg', '.pdf, .docx, .doc, .xml'
 * @see accept https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#unique_file_type_specifiers
 */

type TUseUploadParams = {
  uploadProps?: UploadProps
  notificationApi: NotificationInstance
}

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

type TUploadVideoResponse = {
  success: boolean
  message: string
  statusCode: number
}

export const useUpload = (props: TUseUploadParams) => {
  const [filesInQueue, setFilesInQueue] = useAtom(filesInQueueAtom)
  const { libraryId } = useVideoLibrary()
  const [videoId, setVideoId] = useState<string>('')

  const uploadProps = props?.uploadProps
  const notificationApi = props.notificationApi

  const { data } = useGetVideo({
    libraryId,
    videoId,
    queryOptions: {
      enabled: !!videoId,
    },
  })

  console.log('⭐  data:', data)

  const [fileList, setFileList] = useState<UploadFile[]>([])

  const mergedUploadProps: UploadProps = {
    customRequest: async (options) => {
      const { file } = options
      const fileInQueueKey = nanoid()

      // 添加到佇列
      setFilesInQueue((prev) => [
        ...prev,
        {
          key: fileInQueueKey,
          file: file as RcFile,
          status: 'active' as
            | 'active'
            | 'normal'
            | 'exception'
            | 'success'
            | undefined,
        },
      ])

      try {
        // 創建影片 API
        const createVideoResult =
          await bunnyStreamAxios.post<TCreateVideoResponse>(
            `/${libraryId}/videos`,
            {
              title: (file as RcFile)?.name || 'unknown name',
            },
          )

        // 取得影片 ID
        const theVideoId = createVideoResult?.data?.guid || 'unknown id'
        setVideoId(theVideoId)

        // 上傳影片 API
        const uploadVideo = await bunnyStreamAxios.put<TUploadVideoResponse>(
          `/${libraryId}/videos/${theVideoId}?enabledResolutions=720p%2C1080p`,
          file,
          {
            headers: {
              'Content-Type': 'video/*',
            },
          },
        )

        if (uploadVideo?.data?.success) {
          // 設定為 100% 並顯示成功
          setFilesInQueue((prev) => {
            console.log('⭐ success prev:', prev)
            const newFilesInQueue = prev.map((fileInQueue) => {
              if (fileInQueue.key === fileInQueueKey) {
                return {
                  ...fileInQueue,
                  status: 'success' as
                    | 'active'
                    | 'normal'
                    | 'exception'
                    | 'success'
                    | undefined,
                }
              }

              return fileInQueue
            })
            console.log('⭐ success newFilesInQueue:', newFilesInQueue)

            return newFilesInQueue
          })
        } else {
          // 顯示失敗
          setFilesInQueue((prev) => {
            const newFilesInQueue = prev.map((fileInQueue) => {
              if (fileInQueue.key === fileInQueueKey) {
                return {
                  ...fileInQueue,
                  status: 'exception' as
                    | 'active'
                    | 'normal'
                    | 'exception'
                    | 'success'
                    | undefined,
                }
              }

              return fileInQueue
            })

            return newFilesInQueue
          })
        }
      } catch (error) {
        // 顯示失敗
        setFilesInQueue((prev) => {
          const newFilesInQueue = prev.map((fileInQueue) => {
            if (fileInQueue.key === fileInQueueKey) {
              return {
                ...fileInQueue,
                status: 'exception' as
                  | 'active'
                  | 'normal'
                  | 'exception'
                  | 'success'
                  | undefined,
              }
            }

            return fileInQueue
          })

          return newFilesInQueue
        })
      }
    },

    // previewFile(file) {
    //   console.log('Your upload file:', file)

    //   // Your process logic. Here we just mock to the same file

    //   return fetch('https://next.json-generator.com/api/json/get/4ytyBoLK8', {
    //     method: 'POST',
    //     body: file,
    //   })
    //     .then((res) => res.json())
    //     .then(({ thumbnail }) => thumbnail)
    // },

    accept: 'video/*',
    multiple: false, // 是否支持多選文件，ie10+ 支持。按住 ctrl 多選文件
    maxCount: 2, // 最大檔案數
    beforeUpload: (file, theFileList) => {
      setFileList([...fileList, ...theFileList])

      return true
    },

    onRemove: (file) => {
      const index = fileList.indexOf(file)
      const newFileList = fileList.slice()
      newFileList.splice(index, 1)
      setFileList(newFileList)
    },
    listType: 'picture',
    fileList,
    ...uploadProps,
  }

  return { uploadProps: mergedUploadProps, fileList, setFileList }
}
