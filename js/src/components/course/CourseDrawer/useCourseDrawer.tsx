import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate } from '@refinedev/core'
import { TProductRecord } from '@/pages/admin/Courses/CourseSelector/types'

type TUseCourseDrawerParams = {
  form: FormInstance
  record?: TProductRecord
}

export const useCourseDrawer = ({ form, record }: TUseCourseDrawerParams) => {
  const [open, setOpen] = useState(false)
  const isUpdate = !!record // 如果沒有傳入 record 就走新增課程，否則走更新課程

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

      if (isUpdate) {
        updateCourse({
          id: record?.id,
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
    title: `${isUpdate ? '編輯' : '新增'}課程`,
    forceRender: true,
    onClose: close,
    open,
    width: '50%',
    extra: (
      <Button type="primary" onClick={handleSave}>
        儲存
      </Button>
    ),
  }

  useEffect(() => {
    if (record?.id) {
      form.setFieldsValue(record)
    }
  }, [record?.id])

  return {
    open,
    setOpen,
    show,
    close,
    drawerProps,
  }
}
