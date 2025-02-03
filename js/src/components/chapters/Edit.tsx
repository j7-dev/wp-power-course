import React, { memo, useEffect } from 'react'
import { Form, Input, Switch } from 'antd'
import {
	VideoInput,
	VideoLength,
	DescriptionDrawer,
} from '@/components/formItem'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { Edit, useForm } from '@refinedev/antd'
import { toFormData } from 'antd-toolkit'
import { ExclamationCircleFilled } from '@ant-design/icons'

const { Item } = Form

const ChapterEditComponent = ({ record }: { record: TChapterRecord }) => {
	const { id, name } = record

	// 初始化資料
	const { formProps, form, saveButtonProps, mutation, onFinish } = useForm({
		action: 'edit',
		resource: 'chapters',
		id,
		redirect: false,
		queryOptions: {
			enabled: false,
		},

		invalidates: ['list', 'detail'],
		warnWhenUnsavedChanges: true,
	})

	// 取得課程深度，用來判斷是否為子章節
	const watchDepth = Form.useWatch(['depth'], form)
	const label = watchDepth === 0 ? '章節' : '單元'
	const watchStatus = Form.useWatch(['status'], form)

	useEffect(() => {
		form.setFieldsValue(record)
	}, [record])

	// 將 [] 轉為 '[]'，例如，清除原本分類時，如果空的，前端會是 undefined，轉成 formData 時會遺失
	const handleOnFinish = (values: Partial<TChapterRecord>) => {
		onFinish(toFormData(values))
	}

	return (
		<Edit
			resource="chapters"
			recordItemId={id}
			breadcrumb={null}
			goBack={null}
			headerButtons={() => null}
			title={
				<div className="pl-4">
					《編輯》 {name} <span className="text-gray-400 text-xs">#{id}</span>
				</div>
			}
			saveButtonProps={{
				...saveButtonProps,
				children: `儲存${label}`,
				icon: null,
				loading: mutation?.isLoading,
			}}
			footerButtons={({ defaultButtons }) => (
				<>
					<div className="text-red-500 font-bold mr-8">
						<ExclamationCircleFilled />{' '}
						章節/單元和課程是分開儲存的，編輯完成請記得儲存
					</div>

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
			wrapperProps={{
				style: {
					boxShadow: '0px 0px 16px 0px #ddd',
					paddingTop: '1rem',
					borderRadius: '0.5rem',
				},
			}}
		>
			<Form {...formProps} onFinish={handleOnFinish} layout="vertical">
				<Item name={['name']} label={`${label}名稱`}>
					<Input />
				</Item>
				{/* 如果深度為 0 清除 chapter_video*/}
				{watchDepth === 0 && (
					<Item name={['chapter_video']} hidden>
						<Input allowClear />
					</Item>
				)}
				{/*如果深度為 1 顯示上傳課程內容*/}
				{watchDepth === 1 && (
					<>
						<div className="mb-8">
							<DescriptionDrawer itemLabel="單元" />
						</div>
						<div className="mb-6 max-w-[20rem]">
							<p className="mb-3">上傳課程內容</p>
							<VideoInput name={['chapter_video']} />
						</div>
						<div className="mb-6 max-w-[20rem]">
							<p className="mb-3">課程時長</p>
							<VideoLength name={['chapter_length']} />
						</div>
					</>
				)}
				<Item name={['status']} hidden />
				<Item name={['depth']} hidden />
				<Item name={['id']} hidden />
			</Form>
		</Edit>
	)
}

export const ChapterEdit = memo(ChapterEditComponent)
