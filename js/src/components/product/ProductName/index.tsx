import React, { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { Image, Form } from 'antd'
import { EyeOutlined } from '@ant-design/icons'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useFormDrawer } from '@/hooks'
import { ChapterDrawer } from '@/components/course/ChapterDrawer'

export const ProductName: FC<{
  record: TCourseRecord
}> = ({ record }) => {
  const { id, sku, name, images, type } = record
  const image_url = images?.[0]?.url || defaultImage
  const isChapter = type === 'chapter'

  const [courseForm] = Form.useForm()
  const { show: showCourseDrawer, drawerProps: courseDrawerProps } =
    useFormDrawer({ form: courseForm, record, resource: 'courses' })

  const [chapterForm] = Form.useForm()
  const { show: showChapterDrawer, drawerProps: chapterDrawerProps } =
    useFormDrawer({ form: chapterForm, record, resource: 'chapters' })

  const handleClick = () => {
    if (isChapter) {
      showChapterDrawer()
    } else {
      showCourseDrawer()
    }
  }

  return (
    <>
      <div className="flex">
        <div className="mr-4">
          <Image
            className="rounded-md object-cover"
            preview={{
              mask: <EyeOutlined />,
              maskClassName: 'rounded-md',
              forceRender: true,
            }}
            width={40}
            height={40}
            src={image_url || defaultImage}
            fallback={defaultImage}
          />
        </div>
        <div className="flex-1">
          <p
            className="mb-1 text-primary hover:text-primary/70 cursor-pointer"
            onClick={handleClick}
          >
            {renderHTML(name)}
          </p>
          <div className="flex text-[0.675rem] text-gray-500">
            <span className="pr-3">{`ID: ${id}`}</span>
            {sku && <span className="pr-3">{`SKU: ${sku}`}</span>}
          </div>
        </div>
      </div>
      <Form layout="vertical" form={courseForm}>
        <CourseDrawer {...courseDrawerProps} />
      </Form>

      <Form layout="vertical" form={chapterForm}>
        <ChapterDrawer {...chapterDrawerProps} />
      </Form>
    </>
  )
}
