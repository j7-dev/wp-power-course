import { FC } from 'react'
import { Drawer, DrawerProps, Input, Form, Switch } from 'antd'

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
          <Item
            name={['status']}
            label="發佈"
            getValueProps={(value) => ({ value: value === 'publish' })}
            normalize={(value) => (value ? 'publish' : 'draft')}
          >
            <Switch checkedChildren="發佈" unCheckedChildren="草稿" />
          </Item>
        </Form>
      </Drawer>
    </>
  )
}
