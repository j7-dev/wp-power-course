import React, { FC } from 'react'
import { TProductRecord } from '@/pages/admin/Courses/CourseSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { Image, Form } from 'antd'
import { EyeOutlined } from '@ant-design/icons'
import { useCourseDrawer, CourseDrawer } from '@/components/course/CourseDrawer'

export const ProductName: FC<{
  record: TProductRecord
}> = ({ record }) => {
  const { id, sku, name, image_url } = record

  const [form] = Form.useForm()
  const { show: showDrawer, drawerProps } = useCourseDrawer({ form, record })

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
            onClick={showDrawer}
          >
            {renderHTML(name)}
          </p>
          <div className="flex text-[0.675rem] text-gray-500">
            <span className="pr-3">{`ID: ${id}`}</span>
            {sku && <span className="pr-3">{`SKU: ${sku}`}</span>}
          </div>
        </div>
      </div>
      <Form layout="vertical" form={form}>
        <CourseDrawer {...drawerProps} />
      </Form>
    </>
  )
}
