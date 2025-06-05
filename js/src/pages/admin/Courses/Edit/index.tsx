import { memo, useMemo } from 'react'
import { Edit, useForm } from '@refinedev/antd'
import { Tabs, TabsProps, Form, Switch, Button, Tooltip } from 'antd'
import {
	CourseDescription,
	CourseQA,
	CourseAnnouncement,
	CoursePrice,
	CourseBundles,
	CourseOther,
	CourseStudents,
} from '@/components/course/form'
import { SortableChapters } from '@/components/course'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { CourseContext } from '@/pages/admin/Courses/Edit/hooks'
import { formatDateRangeData } from '@/utils'
import { toFormData } from 'antd-toolkit'
import { useEnv } from '@/hooks'

export const CoursesEdit = () => {
	const { SITE_URL, COURSE_PERMALINK_STRUCTURE } = useEnv()
	// 初始化資料
	const { formProps, form, saveButtonProps, query, mutation, onFinish } =
		useForm<TCourseRecord>({
			dataProviderName: 'power-course',
			redirect: false,
		})

	const record = useMemo(() => {
		return query?.data?.data
	}, [query])

	// TAB items
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
			forceRender: false,
			label: '銷售方案',
			children: <CourseBundles />,
		},
		{
			key: 'Chapters',
			forceRender: false,
			label: '章節管理',
			children: <SortableChapters />,
		},
		{
			key: 'CourseQA',
			forceRender: true,
			label: 'QA設定',
			children: <CourseQA />,
		},
		// {
		// 	key: 'CourseAnnouncement',
		// 	forceRender: false,
		// 	label: '課程公告',
		// 	children: <CourseAnnouncement />,
		// },
		{
			key: 'CourseOther',
			forceRender: true,
			label: '其他設定',
			children: <CourseOther />,
		},
		{
			key: 'CourseStudents',
			forceRender: false,
			label: '學員管理',
			children: <CourseStudents />,
		},
	]

	// 顯示
	const watchName = Form.useWatch(['name'], form)
	const watchId = Form.useWatch(['id'], form)
	const watchStatus = Form.useWatch(['status'], form)
	const watchSlug = Form.useWatch(['slug'], form)

	// 將 [] 轉為 '[]'，例如，清除原本分類時，如果空的，前端會是 undefined，轉成 formData 時會遺失
	const handleOnFinish = (values: Partial<TCourseRecord>) => {
		const formattedValues = formatDateRangeData(values, 'sale_date_range', [
			'date_on_sale_from',
			'date_on_sale_to',
		])
		onFinish(toFormData(formattedValues))
	}

	return (
		<div className="sticky-card-actions sticky-tabs-nav">
			<CourseContext.Provider value={record}>
				<Edit
					resource="courses"
					dataProviderName="power-course"
					title={
						<>
							{watchName}{' '}
							<span className="text-gray-400 text-xs">#{watchId}</span>
						</>
					}
					headerButtons={() => null}
					saveButtonProps={{
						...saveButtonProps,
						children: '儲存',
						icon: null,
						loading: mutation?.isLoading,
					}}
					footerButtons={({ defaultButtons }) => (
						<>
							<Switch
								className="mr-4"
								checkedChildren="發佈"
								unCheckedChildren="草稿"
								value={watchStatus === 'publish'}
								onChange={(checked) => {
									form.setFieldValue(['status'], checked ? 'publish' : 'draft')
								}}
							/>
							{defaultButtons}
						</>
					)}
					isLoading={query?.isLoading}
				>
					<Form {...formProps} onFinish={handleOnFinish} layout="vertical">
						<Tabs
							items={items}
							tabBarExtraContent={
								<>
									<Tooltip
										title={
											record?.classroom_link
												? undefined
												: '此課程還沒有章節，無法前往教室'
										}
									>
										<Button
											href={record?.classroom_link}
											target="_blank"
											rel="noreferrer"
											className="ml-4"
											type="default"
											disabled={!record?.classroom_link}
										>
											前往教室
										</Button>
									</Tooltip>

									<Button
										href={`${SITE_URL}/${COURSE_PERMALINK_STRUCTURE}/${watchSlug}`}
										target="_blank"
										rel="noreferrer"
										className="ml-4"
										type="default"
									>
										前往銷售頁
									</Button>
								</>
							}
						/>
					</Form>
				</Edit>
			</CourseContext.Provider>
		</div>
	)
}

export default memo(CoursesEdit)
