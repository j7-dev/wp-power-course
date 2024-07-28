import { useEffect, useState } from 'react'
import { Form, Input, InputNumber, Radio, Select, Space } from 'antd'
import {
	keyLabelMapper,
	termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { siteUrl } from '@/utils'
import { Heading, ListSelect, useListSelect } from '@/components/general'
import { FiSwitch, VideoInput, DatePicker } from '@/components/formItem'
import { CopyText } from 'antd-toolkit'
import { useUpload } from '@/bunny'
import DescriptionDrawer from './DescriptionDrawer'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { FileUpload } from '@/components/post'

const { Item } = Form

export const CourseDescription = () => {
	const form = Form.useFormInstance()
	const { options, isLoading } = useOptions()
	const { product_cats = [], product_tags = [] } = options
	const productUrl = `${siteUrl}/courses/`
	const slug = Form.useWatch(['slug'], form)
	const bunnyUploadProps = useUpload()
	const { fileList } = bunnyUploadProps
	const watchLimitType: string = Form.useWatch(['limit_type'], form)
	const watchId = Form.useWatch(['id'], form)
	const isUpdate = !!watchId
	const [initTeacherIds, setInitTeacherIds] = useState<string[]>([])

	useEffect(() => {
		form.setFieldValue(['files'], fileList)
	}, [fileList])

	const handleReset = (value: string) => {
		if ('unlimited' === value) {
			form.setFieldsValue({ limit_value: '', limit_unit: '' })
		}
		if ('fixed' === value) {
			form.setFieldsValue({ limit_value: 1, limit_unit: 'day' })
		}
		if ('assigned' === value) {
			form.setFieldsValue({
				limit_value: undefined,
				limit_unit: 'timestamp',
			})
		}
	}

	const { listSelectProps } = useListSelect<TUserRecord>({
		resource: 'users',
		searchField: 'search',
		filters: [
			{
				field: 'meta_key',
				operator: 'eq',
				value: 'is_teacher',
			},
			{
				field: 'meta_value',
				operator: 'eq',
				value: 'yes',
			},
			{
				field: 'posts_per_page',
				operator: 'eq',
				value: 20,
			},
		],
		initKeys: initTeacherIds,
	})

	const { selectedItems: selectedTeachers } = listSelectProps

	useEffect(() => {
		form.setFieldValue(
			['teacher_ids'],
			selectedTeachers.map((item) => item.id),
		)
	}, [selectedTeachers.length])

	useEffect(() => {
		if (watchId) {
			const teacherIds = form.getFieldValue(['teacher_ids'])
			setInitTeacherIds(teacherIds || [])
		} else {
			setInitTeacherIds([])
		}
	}, [watchId])

	return (
		<>
			<div className="mb-12">
				<Heading>課程發佈</Heading>

				<Item name={['slug']} label="銷售網址">
					<Input
						addonBefore={productUrl}
						addonAfter={<CopyText text={`${productUrl}${slug}`} />}
					/>
				</Item>

				<FiSwitch
					formItemProps={{
						name: ['status'],
						label: '發佈',
						initialValue: 'publish',
						getValueProps: (value) => ({ value: value === 'publish' }),
						normalize: (value) => (value ? 'publish' : 'draft'),
						hidden: true,
					}}
					switchProps={{
						checkedChildren: '發佈',
						unCheckedChildren: '草稿',
					}}
				/>
			</div>
			<div className="mb-12">
				<Heading>課程描述</Heading>

				<Item name={['id']} hidden normalize={() => undefined}>
					<Input />
				</Item>
				<Item name={['name']} label="課程名稱">
					<Input allowClear />
				</Item>
				<Item
					name={['category_ids']}
					label={keyLabelMapper('product_category_id')}
				>
					<Select
						options={termFormatter(product_cats)}
						mode="multiple"
						placeholder="可多選"
						allowClear
					/>
				</Item>
				<Item name={['tag_ids']} label={keyLabelMapper('product_tag_id')}>
					<Select
						options={termFormatter(product_tags)}
						mode="multiple"
						placeholder="可多選"
						allowClear
					/>
				</Item>

				<Item name={['short_description']} label="課程簡介">
					<Input.TextArea rows={8} allowClear />
				</Item>
				<DescriptionDrawer />

				<div className="grid grid-cols-2 gap-6 mb-12 mt-12">
					<div className="mb-8">
						<p className="mb-3">課程封面圖</p>
						<FileUpload />
						<Item hidden name={['files']} label="課程封面圖">
							<Input />
						</Item>
						<Item hidden name={['images']} initialValue={[]}>
							<Input />
						</Item>
					</div>
				</div>

				<div className="grid grid-cols-2 gap-6">
					<div className="mb-8">
						<p className="mb-3">課程封面影片</p>
						<VideoInput name={['feature_video']} />
					</div>
					<div className="mb-8">
						<p className="mb-3">課程免費試看影片</p>
						<VideoInput name={['trial_video']} />
					</div>
				</div>
			</div>

			<div className="min-h-[12rem] mb-12">
				<Heading>課程資訊</Heading>

				<div className="grid 2xl:grid-cols-3 gap-6">
					<DatePicker
						formItemProps={{
							name: ['course_schedule'],
							label: '開課時間',
							className: 'mb-0',
						}}
					/>

					<div>
						<p className="mb-2">預計時長</p>
						<Space.Compact block>
							<Item name={['course_hour']} noStyle>
								<InputNumber className="w-1/2" min={0} addonAfter="時" />
							</Item>
							<Item name={['course_minute']} noStyle>
								<InputNumber className="w-1/2" min={0} addonAfter="分" />
							</Item>
						</Space.Compact>
					</div>

					<div>
						<Item
							label="觀看期限"
							name={['limit_type']}
							initialValue={'unlimited'}
						>
							<Radio.Group
								className="w-full w-avg"
								options={[
									{ label: '無期限', value: 'unlimited' },
									{ label: '固定天數', value: 'fixed' },
									{ label: '指定時間', value: 'assigned' },
								]}
								optionType="button"
								buttonStyle="solid"
								onChange={(e) => {
									const value = e?.target?.value || ''
									handleReset(value)
								}}
							/>
						</Item>
						{'unlimited' === watchLimitType && (
							<>
								<Item name={['limit_value']} initialValue="" hidden />
								<Item name={['limit_unit']} initialValue="" hidden />
							</>
						)}
						{'fixed' === watchLimitType && (
							<Space.Compact block>
								<Item
									name={['limit_value']}
									initialValue={1}
									className="w-full"
								>
									<InputNumber className="w-full" min={1} />
								</Item>
								<Item name={['limit_unit']} initialValue="day">
									<Select
										options={[
											{ label: '日', value: 'day' },
											{ label: '月', value: 'month' },
											{ label: '年', value: 'year' },
										]}
										className="w-16"
									/>
								</Item>
							</Space.Compact>
						)}
						{'assigned' === watchLimitType && (
							<>
								<DatePicker
									formItemProps={{
										name: ['limit_value'],
										className: 'mb-0',
										rules: [
											{
												required: true,
												message: '請填寫指定時間',
											},
										],
									}}
								/>
								<Item name={['limit_unit']} initialValue="timestamp" hidden>
									<Input />
								</Item>
							</>
						)}
					</div>
				</div>
			</div>

			<div className="mb-12">
				<Heading>講師資訊</Heading>
				<ListSelect<TUserRecord>
					listSelectProps={listSelectProps}
					rowName="display_name"
					rowUrl="user_avatar_url"
				/>
				<Item name={['teacher_ids']} hidden />
			</div>
		</>
	)
}
