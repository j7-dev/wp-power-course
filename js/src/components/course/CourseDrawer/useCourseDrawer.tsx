import { useState } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate } from '@refinedev/core'

type TUseCourseDrawerParams = {
  form: FormInstance
  id?: string
}

export const useCourseDrawer = ({ form, id }: TUseCourseDrawerParams) => {
  const [open, setOpen] = useState(false)

  const show = () => {
    setOpen(true)
  }

  const close = () => {
    setOpen(false)
  }

  const { mutate: createCourse } = useCreate()
  const { mutate: updateCourse } = useUpdate()

  const handleSave = () => {
    form.validateFields().then(() => {
      const values = form.getFieldsValue()
      if (!!id) {
        updateCourse({
          id,
          resource: 'courses',
          values,
        })
      } else {
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
      }
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
