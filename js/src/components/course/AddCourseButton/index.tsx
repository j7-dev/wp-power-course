import { PlusOutlined } from '@ant-design/icons'
import { Button } from 'antd'
import { useCourseDrawer, CourseDrawer } from '@/components/course/CourseDrawer'

export const AddCourseButton = () => {
  const { show: showDrawer, drawerProps } = useCourseDrawer()
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
      <CourseDrawer {...drawerProps} />
    </>
  )
}
