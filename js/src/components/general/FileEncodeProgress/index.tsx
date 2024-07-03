import { FC, useEffect, useState } from 'react'
import { Typography, Progress } from 'antd'
import { filesInQueueAtom, TFileInQueue } from '@/pages/admin/Courses'
import { useGetVideo } from '@/bunny'
import { CodeOutlined } from '@ant-design/icons'
import { useSetAtom } from 'jotai'

const { Paragraph } = Typography
const REFETCH_INTERVAL = 10000

export const FileEncodeProgress: FC<{
  fileInQueue: TFileInQueue
}> = ({ fileInQueue }) => {
  const { file, status = 'active', videoId = '' } = fileInQueue
  const [enabled, setEnabled] = useState(true)
  const { data } = useGetVideo({
    videoId,
    queryOptions: {
      enabled: !!fileInQueue?.videoId && enabled,
      refetchInterval: REFETCH_INTERVAL,
    },
  })

  const encodeProgress = data?.data?.encodeProgress || 0

  const setFilesInQueue = useSetAtom(filesInQueueAtom)

  useEffect(() => {
    if (encodeProgress >= 100) {
      setEnabled(false)

      setFilesInQueue((prev) => {
        return prev.map((theFileInQueue) => {
          if (videoId === theFileInQueue.videoId) {
            return {
              ...fileInQueue,
              status: 'success',
              isEncoding: false,
              encodeProgress: 100,
            }
          }

          return fileInQueue
        })
      })
    }
  }, [encodeProgress])

  return (
    <>
      <Paragraph className="mb-0 text-xs" ellipsis>
        <CodeOutlined className="mr-1" />
        影片處理中 {file.name}
      </Paragraph>
      <p className="text-xs my-0 ml-4">
        您可以先儲存此課程/章節/段落，去編輯其他部分了
      </p>
      <Progress percent={encodeProgress} status={status} />
    </>
  )
}
