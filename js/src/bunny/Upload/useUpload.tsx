import { useState, useEffect } from 'react'
import { GetProp, UploadFile, UploadProps, Progress } from 'antd'
import { useVideoLibrary } from './useVideoLibrary'
import { NotificationInstance } from 'antd/es/notification/interface'
import { bunnyStreamAxios } from '@/rest-data-provider/bunny-stream'
import { RcFile } from 'antd/lib/upload/interface'

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
  const { libraryId } = useVideoLibrary()

  // const key = 'test'

  const [progress, setProgress] = useState<{
    percent: number
    status: 'active' | 'exception' | 'success' | 'normal' | undefined
  }>({
    percent: 0,
    status: 'active',
  })

  const [fileInfo, setFileInfo] = useState<{
    key: string
    size: number
  }>({
    key: '',
    size: 0,
  })

  const uploadProps = props?.uploadProps
  const notificationApi = props.notificationApi

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
      key: fileInfo.key,
      message: `影片${label}`,
      description: <Progress percent={percent} status={status} />,
    })
  }

  notification(progress)

  useEffect(() => {
    // 用來模擬上傳進度

    if (progress?.percent >= 100 || !fileInfo?.key || !fileInfo?.size) {
      return
    }

    const estimatedTimeInSeconds = estimateUploadTimeInSeconds(fileInfo.size)
    const step = (100 / estimatedTimeInSeconds) * 3

    if (progress.percent + step >= 100) {
      return
    }

    const timer = setInterval(() => {
      setProgress((pre) => ({
        ...pre,
        percent: Number((pre.percent + step).toFixed(1)),
      }))
    }, 3000)

    return () => {
      clearInterval(timer)
    }
  }, [fileInfo, progress])

  const [fileList, setFileList] = useState<UploadFile[]>([])

  const mergedUploadProps: UploadProps = {
    customRequest: async (options) => {
      const { file } = options
      try {
        setFileInfo({
          key: (file as RcFile)?.name || 'unknown name',
          size: (file as RcFile)?.size,
        })

        const createVideoResult =
          await bunnyStreamAxios.post<TCreateVideoResponse>(
            `/${libraryId}/videos`,
            {
              title: (file as RcFile)?.name || 'unknown name',
            },
          )

        console.log('⭐  createVideoResult:', createVideoResult)

        const videoId = createVideoResult?.data?.guid || 'unknown id'
        const uploadVideo = await bunnyStreamAxios.put<TUploadVideoResponse>(
          `/${libraryId}/videos/${videoId}?enabledResolutions=720p%2C1080p`,
          file,
          {
            headers: {
              'Content-Type': 'video/*',
            },
          },
        )

        setProgress({
          percent: 100,
          status: 'success',
        })

        console.log('⭐  uploadVideo:', uploadVideo)
      } catch (error) {
        setProgress((prev) => ({
          ...prev,
          status: 'exception',
        }))
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
    maxCount: 1, // 最大檔案數
    beforeUpload: (file, theFileList) => {
      setFileList([...fileList, ...theFileList])

      return true
    },
    onChange: (info) => {
      const { file } = info
      setFileInfo({
        key: (file as RcFile)?.name || 'unknown name',
        size: (file as RcFile)?.size,
      })
      setProgress({
        percent: 0,
        status: 'active',
      })
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

function estimateUploadTimeInSeconds(fileSize: number) {
  // 將文件大小轉換為 bits（1 byte = 8 bits）

  const fileSizeInBits = fileSize * 8

  // 上傳速度（30 Mbps = 30,000,000 bits/second）
  const uploadSpeed = 30 * 1000 * 1000 // bits per second

  // 計算預期上傳時間（秒）

  const estimatedTimeInSeconds = fileSizeInBits / uploadSpeed

  // 返回秒數，保留兩位小數

  return Number(estimatedTimeInSeconds.toFixed(2))
}
