import { useUpload } from '@/bunny'
import { Form, FormItemProps, Input } from 'antd'
import { FC, useEffect } from 'react'
import { Upload } from '@/components/general'
import { bunny_library_id } from '@/utils'
import { DeleteOutlined } from '@ant-design/icons'

const { Item } = Form
export const VideoInput: FC<FormItemProps> = (formItemProps) => {
  const form = Form.useFormInstance()
  const bunnyUploadProps = useUpload()
  const { fileList } = bunnyUploadProps
  const videoId = fileList?.[0]?.videoId // 上傳影片到 bunny 後取得的 videoId
  const name = formItemProps?.name

  useEffect(() => {
    if (videoId) {
      form.setFieldValue(name, videoId)
    }
  }, [videoId])

  if (!name) {
    throw new Error('name is required')
  }

  const watchChapterId = Form.useWatch(['id'], form)

  // 取得後端傳來的 saved video id
  const savedVideoId: string | undefined = Form.useWatch(name, form)

  const isEmpty = savedVideoId === ''

  const videoUrl = `https://iframe.mediadelivery.net/embed/${bunny_library_id}/${savedVideoId}`

  const handleDelete = () => {
    form.setFieldValue(name, '')
  }

  return (
    <>
      <Upload {...bunnyUploadProps} />
      <Item hidden {...formItemProps}>
        <Input />
      </Item>
      {/* 如果章節已經有存影片，則顯示影片 */}
      {watchChapterId && (
        <div className={'mt-8'}>
          <div
            className={!isEmpty ? 'block' : 'hidden'}
            style={{
              position: 'relative',
              paddingTop: '56.25%',
            }}
          >
            <iframe
              className="border-0 absolute top-0 left-0 w-full h-full rounded-xl"
              src={videoUrl}
              loading="lazy"
              allow="encrypted-media;picture-in-picture;"
              allowFullScreen={true}
            ></iframe>

            <div
              onClick={handleDelete}
              className="group absolute top-4 right-4 rounded-md w-12 h-12 bg-white shadow-lg flex justify-center items-center transition durartion-300 hover:bg-red-500 cursor-pointer"
            >
              <DeleteOutlined className="text-red-500 group-hover:text-white" />
            </div>
          </div>
        </div>
      )}
    </>
  )
}
