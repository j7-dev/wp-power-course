import { PlusOutlined } from '@ant-design/icons'
import React from 'react'
import { Button, Form } from 'antd'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useCourseFormDrawer } from '@/hooks'

export const AddTeacherButton = () => {
  const [form] = Form.useForm()
  const { show: showDrawer, drawerProps } = useCourseFormDrawer({ form })
  return (
    <div>
      <Button
        type="primary"
        className="mb-4"
        icon={<PlusOutlined />}
        onClick={showDrawer()}
      >
        新增講師
      </Button>
      <Form layout="vertical" form={form}>
        <CourseDrawer {...drawerProps} />
      </Form>
    </div>
  )
}
