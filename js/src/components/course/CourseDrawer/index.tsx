import { FC, useEffect, useState } from 'react'
import { Drawer, DrawerProps, Tabs, TabsProps, Form } from 'antd'
import {
	CourseDescription,
	CourseQA,
	CourseAnnouncement,
	CoursePrice,
	CourseBundle,
	CourseOther,
	CourseStudents,
} from '@/components/course/form'

export const CourseDrawer: FC<DrawerProps> = (drawerProps) => {
	const form = Form.useFormInstance()

	const items: TabsProps['items'] = [
		{
			key: 'CourseDescription',
			forceRender: true,
			label: '課程描述',
			children: <CourseDescription />,
		},
		{
			key: 'CoursePrice',
			forceRender: true,
			label: '課程訂價',
			children: <CoursePrice />,
		},
		{
			key: 'CourseBundle',
			forceRender: true,
			label: '銷售方案',
			children: <CourseBundle />,
		},
		{
			key: 'CourseQA',
			forceRender: true,
			label: 'QA設定',
			children: <CourseQA />,
		},
		{
			key: 'CourseAnnouncement',
			forceRender: true,
			label: '課程公告',
			children: <CourseAnnouncement />,
		},
		{
			key: 'CourseOther',
			forceRender: true,
			label: '其他設定',
			children: <CourseOther />,
		},
		{
			key: 'CourseStudents',
			forceRender: true,
			label: '學員管理',
			children: <CourseStudents />,
		},
	]

	const [activeKey, setActiveKey] = useState('CourseDescription')

	useEffect(() => {
		if (drawerProps.open) {
			setActiveKey('CourseDescription')
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
