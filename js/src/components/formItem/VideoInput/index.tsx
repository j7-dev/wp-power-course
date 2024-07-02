import { Upload, useUpload } from '@/bunny'
import { Form, FormItemProps, Input } from 'antd'
import { FC, useEffect } from 'react'

const { Item } = Form
export const VideoInput: FC<FormItemProps> = (formItemProps) => {
  const form = Form.useFormInstance()
  const bunnyUploadProps = useUpload()
  const { fileList } = bunnyUploadProps
  const videoId = fileList?.[0]?.videoId
  const name = formItemProps?.name

  useEffect(() => {
    if (videoId) {
      form.setFieldValue(name, videoId)
    }
  }, [videoId])

  if (!name) {
    throw new Error('name is required')
  }

  return (
    <>
      <Upload {...bunnyUploadProps} />
      <Item hidden {...formItemProps}>
        <Input />
      </Item>
    </>
  )
}
