import React from 'react'
import CourseSelector from './CourseSelector'
import { Upload, useUpload } from '@/bunny/Upload'
import { useGetVideo } from '@/bunny/hooks'
import { notification } from 'antd'

const index = () => {
  const [api, contextHolder] = notification.useNotification({
    duration: 0,
    placement: 'bottomLeft',
    stack: { threshold: 1 },
  })
  const { uploadProps } = useUpload({
    notificationApi: api,
  })

  const { data } = useGetVideo({
    libraryId: 244459,
    videoId: '70ade997-40f3-43cb-9685-25742cdb2683',
  })

  console.log('⭐  data:', data)

  return (
    <>
      {contextHolder}
      <Upload uploadProps={uploadProps} />
      <CourseSelector />
    </>
  )
}

export default index
