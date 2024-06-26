import { useState } from "react"
import { UploadFile, UploadProps } from "antd"
import { bunnyStreamAxios } from "@/rest-data-provider/bunny-stream"
import { RcFile } from "antd/lib/upload/interface"
import { useVideoLibrary } from "@/bunny/hooks"
import { filesInQueueAtom } from "@/pages/admin/Courses"
import { useSetAtom } from "jotai"
import { getVideoUrl } from "@/utils"
import { TCreateVideoResponse, TUploadVideoResponse, TUseUploadParams } from "@/bunny/Upload/types"

export const useUpload = (props?: TUseUploadParams) => {
  const setFilesInQueue = useSetAtom(filesInQueueAtom)
  const { libraryId } = useVideoLibrary()

  const uploadProps = props?.uploadProps

  const [fileList, setFileList] = useState<UploadFile[]>([])

  const mergedUploadProps: UploadProps = {
    customRequest: async (options) => {
      const file = options?.file as RcFile

      // 添加到佇列
      setFilesInQueue((prev) => [
        ...prev,
        {
          key: file?.uid,
          file: file as RcFile,
          status: 'active' as
            | 'active'
            | 'normal'
            | 'exception'
            | 'success'
            | undefined,
          videoId: '',
          isEncoding: false,
          encodeProgress: 0,
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

        // 取得 bunny 影片 ID
        const vId = createVideoResult?.data?.guid || 'unknown id'

        // 把 vid 更新到狀態上
        setFileList((prev) => {
          return prev.map((fileInList) => {
            if (fileInList.uid === file?.uid) {
              return {
                ...fileInList,
                videoId: vId,
              }
            }

            return fileInList
          })
        })

        // 把 vid, preview URL 更新到全局佇列狀態
        setFilesInQueue((prev) => {
          return prev.map((fileInQueue) => {
            const findFileInList = fileList.find(
              (fileInList) => fileInList.uid === fileInQueue.key,
            )
            if (findFileInList) {
              return {
                ...fileInQueue,
                videoId: vId,
                preview: findFileInList?.preview,
              }
            }

            return fileInQueue
          })
        })

        // 更新到狀態
        setFilesInQueue((prev) => {
          return prev.map((fileInQueue) => {
            if (fileInQueue.key === file?.uid) {
              return {
                ...fileInQueue,
                videoId: vId,
              }
            }

            return fileInQueue
          })
        })

        // 上傳影片 API
        const uploadVideo = await bunnyStreamAxios.put<TUploadVideoResponse>(
          `/${libraryId}/videos/${vId}?enabledResolutions=720p%2C1080p`,
          file,
          {
            headers: {
              'Content-Type': 'video/*',
            },
          },
        )

        if (uploadVideo?.data?.success) {
          // 設定為 100% 並顯示成功，狀態不用改，馬上就要 encode
          setFilesInQueue((prev) => {
            return prev.map((fileInQueue) => {
              if (fileInQueue.key === file?.uid) {
                return {
                  ...fileInQueue,
                  isEncoding: true,
                }
              }

              return fileInQueue
            })
          })
        } else {
          // 顯示失敗
          setFilesInQueue((prev) => {
            return prev.map((fileInQueue) => {
              if (fileInQueue.key === file?.uid) {
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
          })
        }
      } catch (error) {
        // 顯示失敗
        setFilesInQueue((prev) => {
          return prev.map((fileInQueue) => {
            if (fileInQueue.key === file?.uid) {
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
        })
      }
    },
    beforeUpload: (file, theFileList) => {
      // 生成預覽 URL 並更新到狀態上
      const newFileList = theFileList.map((theFile) => {
        const preview = getVideoUrl(theFile) // 用瀏覽器轉換為預覽的 URL
        return {
          ...theFile,
          preview,
        }
      })
      setFileList([...fileList, ...newFileList])

      return true
    },
    onRemove: (file) => {
      const index = fileList.indexOf(file)
      const newFileList = fileList.slice()
      newFileList.splice(index, 1)
      setFileList(newFileList)
    },
    listType: 'text',
    itemRender: () => <></>,
    fileList,
    accept: 'video/*',
    multiple: false, // 是否支持多選文件，ie10+ 支持。按住 ctrl 多選文件
    maxCount: 1, // 最大檔案數
    ...uploadProps,
  }

  return { uploadProps: mergedUploadProps, fileList, setFileList }
}
