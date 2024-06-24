import React from 'react'
import CourseSelector from './CourseSelector'
import { Upload, useUpload } from '@/bunny/Upload'
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

  return (
    <>
      {contextHolder}
      <Upload uploadProps={uploadProps} />
      <CourseSelector />
    </>
  )
}

export default index
