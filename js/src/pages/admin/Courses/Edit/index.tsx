import React, { memo } from 'react'
import { Edit, useForm } from '@refinedev/antd'
import { Tabs, TabsProps, Form, Switch, Modal, Button } from 'antd'
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
import { mediaLibraryAtom } from '@/pages/admin/Courses/atom'
import { useAtom } from 'jotai'
import { MediaLibrary } from '@/bunny'
import { TBunnyVideo } from '@/bunny/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { siteUrl, course_permalink_structure, toFormData } from '@/utils'

export const CoursesEdit = () => {
	// 初始化資料
	const { formProps, form, saveButtonProps, query, mutation, onFinish } =
		useForm({
			redirect: false,
		})

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
		{
			key: 'CourseAnnouncement',
			forceRender: false,
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

	// 處理 media library
	const [mediaLibrary, setMediaLibrary] = useAtom(mediaLibraryAtom)
	const {
		modalProps,
		mediaLibraryProps,
		name,
		form: mediaLibraryForm, // TODO 其實不需要這個 form 了
	} = mediaLibrary
	const { limit, selectedVideos } = mediaLibraryProps

	const selectedVideosSetter = (
		videosOrFunction:
			| TBunnyVideo[]
			| ((_videos: TBunnyVideo[]) => TBunnyVideo[]),
	) => {
		if (typeof videosOrFunction === 'function') {
			const newVideos = videosOrFunction(selectedVideos)
			setMediaLibrary((prev) => ({
				...prev,
				mediaLibraryProps: {
					...prev.mediaLibraryProps,
					selectedVideos: newVideos,
				},
			}))
		} else {
			setMediaLibrary((prev) => ({
				...prev,
				mediaLibraryProps: {
					...prev.mediaLibraryProps,
					selectedVideos: videosOrFunction,
				},
			}))
		}
	}

	// 顯示
	const watchName = Form.useWatch(['name'], form)
	const watchId = Form.useWatch(['id'], form)
	const watchStatus = Form.useWatch(['status'], form)
	const watchSlug = Form.useWatch(['slug'], form)

	// 將 [] 轉為 '[]'，例如，清除原本分類時，如果空的，前端會是 undefined，轉成 formData 時會遺失
	const handleOnFinish = (values: Partial<TCourseRecord>) => {
		onFinish(toFormData(values))
	}

	return (
		<div className="sticky-card-actions sticky-tabs-nav">
			<Edit
				title={
					<>
						{watchName} <sub className="text-gray-500">#{watchId}</sub>
					</>
				}
				resource="courses"
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
						<a
							href={`${siteUrl}/${course_permalink_structure}/${watchSlug}`}
							target="_blank"
							rel="noreferrer"
						>
							<Button className="ml-4" color="primary" variant="filled">
								前往銷售頁
							</Button>
						</a>
					</>
				)}
				isLoading={query?.isLoading}
			>
				{/* 這邊這個 form 只是為了調整 style */}
				<Form {...formProps} onFinish={handleOnFinish} layout="vertical">
					<Tabs items={items} />
				</Form>
			</Edit>

			<Modal
				{...modalProps}
				onCancel={() => {
					setMediaLibrary((prev) => ({
						...prev,
						modalProps: {
							...prev.modalProps,
							open: false,
						},
					}))
				}}
			>
				<div className="max-h-[75vh] overflow-x-hidden overflow-y-auto pr-4">
					<MediaLibrary
						limit={limit}
						selectedVideos={selectedVideos}
						setSelectedVideos={selectedVideosSetter}
						selectButtonProps={{
							onClick: () => {
								setMediaLibrary((prev) => ({
									...prev,
									modalProps: {
										...prev.modalProps,
										open: false,
									},
								}))
								setMediaLibrary((prev) => ({
									...prev,
									confirmedSelectedVideos: selectedVideos,
								}))
								if (mediaLibraryForm && name) {
									mediaLibraryForm.setFieldValue(name, {
										type: 'bunny-stream-api',
										id: selectedVideos?.[0]?.guid || '',
										meta: {},
									})
								}
							},
						}}
					/>
				</div>
			</Modal>
		</div>
	)
}

export default memo(CoursesEdit)
