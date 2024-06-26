import React from 'react'
import { InboxOutlined, DeleteOutlined } from '@ant-design/icons'
import { Upload as AntdUpload, UploadProps, UploadFile } from 'antd'

const { Dragger } = AntdUpload

export const Upload: React.FC<{
  uploadProps: UploadProps
  fileList: UploadFile[]
  setFileList: React.Dispatch<React.SetStateAction<UploadFile<any>[]>>
}> = ({ uploadProps, fileList = [], setFileList }) => {
  const handleDelete = (item: UploadFile) => () => {
    setFileList((prev: UploadFile[]) =>
      prev.filter((file) => file?.uid !== item?.uid),
    )
  }

  return (
    <>
      <Dragger {...uploadProps}>
        <p className="ant-upload-drag-icon">
          <InboxOutlined />
        </p>
        <p className="ant-upload-text">點擊或拖曳文件到這裡上傳</p>
        <p className="ant-upload-hint">僅支持 video/mp4 類型 文件</p>
      </Dragger>
      {fileList.map((item) => (
        <div key={item?.uid} className="w-full relative mt-4">
          <video className="w-full h-full" controls>
            <source src={item?.preview} type="video/mp4" />
          </video>
          <div
            onClick={handleDelete(item)}
            className="group absolute top-4 right-4 rounded-md w-12 h-12 bg-white shadow-lg flex justify-center items-center transition durartion-300 hover:bg-red-500 cursor-pointer"
          >
            <DeleteOutlined className="text-red-500 group-hover:text-white" />
          </div>
        </div>
      ))}
    </>
  )
}

export * from './useUpload'
