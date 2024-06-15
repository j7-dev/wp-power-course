import { FC, useEffect, useState } from 'react'
import { Drawer, DrawerProps, Tabs, TabsProps, Form } from 'antd'
import {
  CourseDescription,
  CourseQA,
  CourseAnnouncement,
  CoursePrice,
  CourseBundle,
} from '@/components/course/form'

export const CourseDrawer: FC<DrawerProps> = (drawerProps) => {
  const form = Form.useFormInstance()

  const items: TabsProps['items'] = [
    {
      key: '1',
      forceRender: true,
      label: '課程描述',
      children: <CourseDescription />,
    },
    {
      key: '2',
      forceRender: true,
      label: 'QA設定',
      children: <CourseQA />,
    },
    {
      key: '3',
      forceRender: true,
      label: '課程公告',
      children: <CourseAnnouncement />,
    },
    {
      key: '4',
      forceRender: true,
      label: '其他設定',
      children: 'Content of Tab Pane 3',
    },
    {
      key: '5',
      forceRender: true,
      label: '課程訂價',
      children: <CoursePrice />,
    },
    {
      key: '6',
      forceRender: true,
      label: '銷售方案',
      children: <CourseBundle />,
    },
  ]

  const [activeKey, setActiveKey] = useState('1')

  useEffect(() => {
    if (drawerProps.open) {
      setActiveKey('1')
    }
  }, [drawerProps.open])

  return (
    <>
      <Drawer {...drawerProps}>
        {/* 這邊這個 form 只是為了調整 style */}
        <Form layout="vertical" form={form}>
          <Tabs
            className="pc-course-drawer-tabs"
            items={items}
            centered
            activeKey={activeKey}
            onChange={(key: string) => {
              setActiveKey(key)
            }}
          />
        </Form>
      </Drawer>
    </>
  )
}
