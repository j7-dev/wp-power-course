import { memo, useMemo, useState } from 'react'
import { Edit, useForm } from '@refinedev/antd'
import { Tabs, TabsProps, Form, Switch, Button, Tooltip, FormProps } from 'antd'
import { SortableChapters } from '@/components/course'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { RecordContext } from '@/pages/admin/Courses/Edit/hooks'
import {
	CourseDescription,
	CourseQA,
	CourseAnnouncement,
	CoursePrice,
	CourseBundles,
	CourseOther,
	CourseStudents,
	CourseAnalysis,
} from '@/pages/admin/Courses/Edit/tabs'
import { useEnv } from '@/hooks'
import { toFormData, formatDateRangeData } from 'antd-toolkit'

const { Item } = Form

export const CoursesEdit = () => {
	const { SITE_URL, COURSE_PERMALINK_STRUCTURE } = useEnv()
	const [activeKey, setActiveKey] = useState('CourseDescription')
	// 初始化資料
	const {
		formProps: _formProps,
		saveButtonProps,
		query,
		mutation,
		onFinish,
	} = useForm<TCourseRecord>({
		dataProviderName: 'power-course',
		redirect: false,
	})

	const record = useMemo(() => {
		return query?.data?.data
	}, [query])

	// 將 [] 轉為 '[]'，例如，清除原本分類時，如果空的，前端會是 undefined，轉成 formData 時會遺失
	const handleOnFinish = (values: Partial<TCourseRecord>) => {
		const formattedValues = formatDateRangeData(values, 'sale_date_range', [
			'date_on_sale_from',
			'date_on_sale_to',
		])
		const { description, short_description, ...rest } = formattedValues
		onFinish(toFormData(rest))
	}

	// 重組 formProps
	const formProps: FormProps = {
		..._formProps,
		layout: 'vertical',
		onFinish: handleOnFinish,
	}

	// TAB items
	const items: TabsProps['items'] = [
		{
			key: 'CourseDescription',
			forceRender: true,
			label: '課程描述',
			children: <CourseDescription formProps={formProps} />,
		},
		{
			key: 'CoursePrice',
			forceRender: true,
			label: '課程訂價',
			children: <CoursePrice formProps={formProps} />,
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
			children: <CourseQA formProps={formProps} />,
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
			children: <CourseOther formProps={formProps} />,
		},
		{
			key: 'CourseStudents',
			forceRender: false,
			label: '學員管理',
			children: <CourseStudents />,
		},
		{
			key: 'CourseAnalysis',
			forceRender: false,
			label: '分析',
			children: <CourseAnalysis />,
		},
	]

	const disableSaveButton = [
		'CourseBundle',
		'Chapters',
		'CourseStudents',
		'CourseAnalysis',
	].includes(activeKey)

	return (
		<div className="sticky-card-actions sticky-tabs-nav">
			<RecordContext.Provider value={record}>
				<Edit
					resource="courses"
					dataProviderName="power-course"
					title={
						<>
							{record?.name}{' '}
							<span className="text-gray-400 text-xs">#{record?.id}</span>
						</>
					}
					headerButtons={() => null}
					saveButtonProps={{
						...saveButtonProps,
						children: '儲存',
						icon: null,
						loading: mutation?.isLoading,
					}}
					footerButtons={({ defaultButtons }) =>
						disableSaveButton ? null : (
							<>
								<Form {...formProps}>
									<Item
										noStyle
										name={['status']}
										getValueProps={(value) => {
											return {
												value: value === 'publish',
											}
										}}
										normalize={(value) => {
											return value ? 'publish' : 'draft'
										}}
									>
										<Switch
											className="mr-4"
											checkedChildren="發佈"
											unCheckedChildren="草稿"
											disabled={disableSaveButton}
										/>
									</Item>
								</Form>
								{defaultButtons}
							</>
						)
					}
					isLoading={query?.isLoading}
				>
					<Tabs
						activeKey={activeKey}
						onChange={(key) => setActiveKey(key)}
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
									href={`${SITE_URL}/${COURSE_PERMALINK_STRUCTURE}/${record?.slug}`}
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
				</Edit>
			</RecordContext.Provider>
		</div>
	)
}

export default memo(CoursesEdit)
