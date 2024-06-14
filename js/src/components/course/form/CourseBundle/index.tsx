import React from 'react'
import { Button, Form, Drawer } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
import { useBundleFormDrawer } from '@/hooks'
import BundleForm from './BundleForm'

export const CourseBundle = () => {
  const form = Form.useFormInstance()
  const courseId: string = Form.useWatch(['id'], form) || ''
  const [bundleProductForm] = Form.useForm()
  const { drawerProps, show } = useBundleFormDrawer({
    form: bundleProductForm,
  })

  return (
    <>
      <Button type="primary" icon={<PlusOutlined />} onClick={show()}>
        新增銷售方案
      </Button>

      <Drawer {...drawerProps}>
        <BundleForm courseId={courseId} form={bundleProductForm} />
      </Drawer>
    </>
  )
}
