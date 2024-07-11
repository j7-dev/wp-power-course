import { FC, useEffect, useState } from 'react'
import { Drawer, DrawerProps, Form, Input } from 'antd'
import { UserAvatarUpload } from '@/components/user'

const { Item } = Form

export const UserDrawer: FC<DrawerProps> = (drawerProps) => {
  const form = Form.useFormInstance()

  return (
    <>
      <Drawer {...drawerProps}>
        {/* 這邊這個 form 只是為了調整 style */}

        <Form layout="vertical" form={form}>
          <Item name={['id']} hidden>
            <Input />
          </Item>
          <UserAvatarUpload />

          <Item name={['user_login']} label="帳號名稱(username)">
            <Input />
          </Item>
          <Item name={['user_pass']} label="密碼">
            <Input.Password />
          </Item>
          <Item name={['user_email']} label="Email">
            <Input />
          </Item>
          <Item name={['display_name']} label="顯示名稱">
            <Input />
          </Item>
          <Item name={['description']} label="講師介紹">
            <Input.TextArea rows={8} allowClear />
          </Item>
        </Form>
      </Drawer>
    </>
  )
}
