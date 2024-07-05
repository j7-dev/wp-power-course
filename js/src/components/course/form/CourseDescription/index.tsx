import { useEffect } from 'react'
import {
	DatePicker,
	Form,
	Input,
	InputNumber,
	Radio,
	Select,
	Space,
} from 'antd'
import {
	keyLabelMapper,
	termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { siteUrl } from '@/utils'
import { Heading, Upload } from '@/components/general'
import { FiSwitch, VideoInput } from '@/components/formItem'
import { CopyText } from 'antd-toolkit'
import dayjs from 'dayjs'
import { useUpload } from '@/bunny'

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
	const isEdit = !!watchId

	useEffect(() => {
		form.setFieldValue(['files'], fileList)
	}, [fileList])

	const handleReset = (value: string) => {
		if ('unlimited' === value) {
			form.setFieldsValue({ limit_value: '', limit_unit: '' })
		}
		if ('fixed' === value) {
			form.setFieldsValue({ limit_value: '1', limit_unit: 'day' })
		}
		if ('assigned' === value) {
			form.setFieldsValue({
				limit_value: undefined,
				limit_unit: 'timestamp',
			})
		}
	}

	return (
		<>
			<div className="mb-12">
				<Heading>課程描述</Heading>

				<Item name={['id']} hidden normalize={() => undefined}>
					<Input />
				</Item>
				<Item name={['name']} label="課程名稱">
					<Input allowClear />
				</Item>
				<Item name={['sub_title']} label="課程副標題">
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
				<Item name={['description']} label="課程重點介紹">
					<Input.TextArea rows={8} disabled />
				</Item>

				<div className="grid grid-cols-2 gap-6 mb-12">
					<div className="mb-8">
						<p className="mb-3">課程封面圖</p>
						<Upload {...bunnyUploadProps} />
						<Item hidden name={['files']} label="課程封面圖">
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
					<Item
						name={['course_schedule']}
						label="開課時間"
						className="mb-0"
						getValueProps={(value) => ({
							value: value ? dayjs.unix(value) : null,
						})}
						normalize={(value) => value?.unix()}
					>
						<DatePicker
							className="w-full"
							format="YYYY-MM-DD HH:mm"
							showTime={{ defaultValue: dayjs() }}
						/>
					</Item>

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

						{'unlimited' === watchLimitType && (
							<>
								<Item name={['limit_value']} initialValue="" hidden>
									<Input />
								</Item>
								<Item name={['limit_unit']} initialValue="" hidden>
									<Input />
								</Item>
							</>
						)}

						{'assigned' === watchLimitType && (
							<>
								<Item
									name={['limit_value']}
									label="指定時間"
									noStyle
									className="mb-0"
									getValueProps={(value) => ({
										value: value ? dayjs.unix(value) : undefined,
									})}
									normalize={(value) => (value ? value?.unix() : '')}
								>
									<DatePicker
										className="w-full"
										format="YYYY-MM-DD HH:mm"
										showTime={{ defaultValue: dayjs() }}
									/>
								</Item>
								<Item name={['limit_unit']} initialValue="timestamp" hidden>
									<Input />
								</Item>
							</>
						)}
					</div>
				</div>
			</div>

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
						getValueProps: (value) => ({ value: value === 'publish' }),
						normalize: (value) => (value ? 'publish' : 'draft'),
					}}
					switchProps={{
						checkedChildren: '發佈',
						unCheckedChildren: '草稿',
					}}
				/>
			</div>
		</>
	)
}
