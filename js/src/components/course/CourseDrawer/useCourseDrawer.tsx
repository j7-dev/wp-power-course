import { useState } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate } from '@refinedev/core'

export const useCourseDrawer = (form: FormInstance) => {
  const [open, setOpen] = useState(false)

  const show = () => {
    setOpen(true)
  }

  const close = () => {
    setOpen(false)
  }

  const { mutate: createCourse } = useCreate()

  const handleSave = () => {
    form.validateFields().then(() => {
      const values = form.getFieldsValue()
      createCourse(
        {
          resource: 'courses',
          values,
        },
        {
          onSuccess: () => {
            close()
            form.resetFields()
          },
        },
      )
    })
  }

  const drawerProps: DrawerProps = {
    title: '新增課程',
    forceRender: true,
    onClose: close,
    open,
    width: '70%',
    extra: (
      <Button type="primary" onClick={handleSave}>
        儲存
      </Button>
    ),
  }

  return {
    open,
    setOpen,
    show,
    close,
    drawerProps,
  }
}
