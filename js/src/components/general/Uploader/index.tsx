import { FC } from 'react'
import { InboxOutlined } from '@ant-design/icons'
import { Upload, UploadProps } from 'antd'

const { Dragger } = Upload

export * from './useUploader'

export const Uploader: FC<UploadProps> = (uploadProps) => {
  return (
    <Dragger {...uploadProps}>
      <p className="ant-upload-drag-icon">
        <InboxOutlined />
      </p>
      <p className="ant-upload-text">
        Click or drag file to this area to upload
      </p>
      <p className="ant-upload-hint">
        Support for a single or bulk upload. Strictly prohibited from uploading
        company data or other banned files.
      </p>
    </Dragger>
  )
}
