import { PlusOutlined } from '@ant-design/icons'
import { Button, Form } from 'antd'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useFormDrawer } from '@/hooks'

export const AddCourseButton = () => {
  const [form] = Form.useForm()
  const { show: showDrawer, drawerProps } = useFormDrawer({ form })
  return (
    <>
      <Button
        type="primary"
        className="mb-4"
        icon={<PlusOutlined />}
        onClick={showDrawer}
      >
        新增課程
      </Button>
      <Form layout="vertical" form={form}>
        <CourseDrawer {...drawerProps} />
      </Form>
    </>
  )
}
