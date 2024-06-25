import React, { useEffect } from 'react'
import CourseSelector from './CourseSelector'
import { Upload, useUpload } from '@/bunny/Upload'
import { useGetVideo } from '@/bunny/hooks'
import { notification } from 'antd'
import { FileUploadProgress } from '@/components/general'
import { atom, useAtomValue } from 'jotai'
import { RcFile } from 'antd/lib/upload/interface'

export type TFileInQueue = {
  key: string
  file: RcFile
  status?: 'active' | 'normal' | 'exception' | 'success' | undefined
}

export const filesInQueueAtom = atom<TFileInQueue[]>([])
export const NOTIFICATION_API_KEY = 'upload-queue'

const index = () => {
  const filesInQueue = useAtomValue(filesInQueueAtom)
  console.log('⭐  filesInQueue:', filesInQueue)
  const [notificationApi, contextHolder] = notification.useNotification({
    duration: 0,
    placement: 'bottomLeft',
    stack: { threshold: 1 },
  })
  const { uploadProps } = useUpload({
    notificationApi,
  })

  // TEST
  const { data } = useGetVideo({
    libraryId: 244459,
    videoId: '70ade997-40f3-43cb-9685-25742cdb2683',
  })

  useEffect(() => {
    if (!filesInQueue.length) {
      // notificationApi.destroy(NOTIFICATION_API_KEY)
      return
    }
    notificationApi.info({
      key: NOTIFICATION_API_KEY,
      message: '影片上傳佇列',
      description: (
        <>
          {filesInQueue.map(({ key, status, file }) => (
            <FileUploadProgress key={key} status={status} file={file} />
          ))}
        </>
      ),
    })
  }, [filesInQueue])

  return (
    <>
      {contextHolder}
      <Upload uploadProps={uploadProps} />
      <CourseSelector />
    </>
  )
}

export default index
