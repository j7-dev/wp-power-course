import React from 'react'
import { InboxOutlined } from '@ant-design/icons'
import { Upload as AntdUpload, UploadProps } from 'antd'
import ImgCrop, { ImgCropProps } from 'antd-img-crop'

const { Dragger } = AntdUpload

export const Upload: React.FC<{
  uploadProps: UploadProps
  imgCropProps?: ImgCropProps
}> = ({ uploadProps, imgCropProps }) => {
  return (
    // <ImgCrop rotationSlider quality={1} showGrid aspect={1} {...imgCropProps}>

    <Dragger {...uploadProps}>
      <p className="ant-upload-drag-icon">
        <InboxOutlined />
      </p>
      <p className="ant-upload-text">點擊或拖曳文件到這裡上傳</p>
      <p className="ant-upload-hint">
        支持單個或批量上傳。僅支持 image 類型 文件
      </p>
    </Dragger>

    // </ImgCrop>
  )
}

export * from './useUpload'
