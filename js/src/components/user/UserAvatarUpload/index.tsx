import React, { useState } from 'react'
import ImgCrop from 'antd-img-crop'
import { Upload, UploadProps, Form, Input, UploadFile } from 'antd'
import { useApiUrl } from '@refinedev/core'

const { Item } = Form

export const UserAvatarUpload = () => {
  const [fileList, setFileList] = useState<UploadFile[]>([])
  const form = Form.useFormInstance()

  const apiUrl = useApiUrl()

  const onChange: UploadProps['onChange'] = ({
    file,
    fileList: newFileList,
  }) => {
    setFileList(newFileList)
    const { status } = file
    if ('done' !== status) return

    const attachmentId = file?.response?.data?.id

    form.setFieldValue('pc_user_avatar', attachmentId)
  }

  return (
    <div className="flex justify-center w-full mb-4">
      <ImgCrop
        quality={1}
        rotationSlider
        showReset
        resetText="重置"
        cropShape="round"
        showGrid
      >
        <Upload
          name="files"
          listType="picture-circle"
          accept="image/*"
          action={`${apiUrl}/upload`}
          headers={{
            'X-WP-Nonce': window?.wpApiSettings?.nonce || '',
          }}
          maxCount={1}
          withCredentials
          fileList={fileList}
          onChange={onChange}
          onPreview={undefined}
        >
          {fileList.length < 1 && '上傳'}
        </Upload>
      </ImgCrop>
      <Item name={['pc_user_avatar']} hidden>
        <Input />
      </Item>
    </div>
  )
}
