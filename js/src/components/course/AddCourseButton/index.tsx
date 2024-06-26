import { PlusOutlined } from '@ant-design/icons'
import React from 'react'
import { Button, Form } from 'antd'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useCourseFormDrawer } from '@/hooks'

export const AddCourseButton = () => {
  const [form] = Form.useForm()
  const { show: showDrawer, drawerProps } = useCourseFormDrawer({ form })
  return (
    <>
      <Button
        type="primary"
        className="mb-4"
        icon={<PlusOutlined />}
        onClick={showDrawer()}
      >
        新增課程
      </Button>
      <Form layout="vertical" form={form}>
        <CourseDrawer {...drawerProps} />
      </Form>
    </>
  )
}
