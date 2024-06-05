import { FC } from 'react'
import { Drawer, DrawerProps, Input, Form } from 'antd'

const { Item } = Form

export const ChapterDrawer: FC<DrawerProps> = (drawerProps) => {
  const form = Form.useFormInstance()

  return (
    <>
      <Drawer {...drawerProps}>
        {/* 這邊這個 form 只是為了調整 style */}
        <Form layout="vertical" form={form}>
          <Item name={['name']} label="課程名稱">
            <Input />
          </Item>
        </Form>
      </Drawer>
    </>
  )
}
