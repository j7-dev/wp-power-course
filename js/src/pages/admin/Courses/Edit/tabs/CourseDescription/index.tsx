import { memo, useEffect, useState } from 'react'
import { Form, Input, Select, FormProps, FormInstance } from 'antd'
import {
	keyLabelMapper,
	termToOptions,
} from '@/components/product/ProductTable/utils'
import useOptions from '@/components/product/ProductTable/hooks/useOptions'
import { Heading, ListSelect, useListSelect } from '@/components/general'
import { FiSwitch, VideoInput } from '@/components/formItem'
import { TUserRecord } from '@/pages/admin/Courses/List/types'
import { FileUpload } from '@/components/post'
import { useParseData, useRecord } from '@/pages/admin/Courses/Edit/hooks'
import { useEnv } from '@/hooks'
import {
	CopyText,
	DescriptionDrawer,
	defaultSelectProps,
	BlockNoteDrawer,
} from 'antd-toolkit'

const { Item } = Form

const CourseDescriptionComponent = ({
	formProps,
}: {
	formProps: FormProps
}) => {
	const form = formProps.form as FormInstance
	const { options } = useOptions({ endpoint: 'courses/options' })
	const { product_cats = [], product_tags = [] } = options
	const { SITE_URL, COURSE_PERMALINK_STRUCTURE } = useEnv()
	const productUrl = `${SITE_URL}/${COURSE_PERMALINK_STRUCTURE}/`
	const [initTeacherIds, setInitTeacherIds] = useState<string[]>([])
	const course = useRecord()
	const parseData = useParseData()

	const { listSelectProps } = useListSelect<TUserRecord>({
		resource: 'users',
		searchField: 'search',
		filters: [
			{
				field: 'is_teacher',
				operator: 'eq',
				value: 'yes',
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
		if (course?.id && course?.teacher_ids?.length) {
			setInitTeacherIds(course?.teacher_ids || [])
		} else {
			setInitTeacherIds([])
		}
	}, [course])

	return (
		<Form {...formProps}>
			<div className="mb-12">
				<Heading>課程發佈</Heading>

				<Item name={['slug']} label="銷售網址">
					<Input
						addonBefore={productUrl}
						addonAfter={<CopyText text={`${productUrl}${course?.slug}`} />}
					/>
				</Item>

				<FiSwitch
					formItemProps={{
						name: ['status'],
						label: '發佈',
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

				<Item name={['id']} hidden normalize={() => undefined} />

				<div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
					<Item name={['name']} label="課程名稱">
						<Input allowClear />
					</Item>
					<Item
						name={['category_ids']}
						label={keyLabelMapper('product_category_id')}
						initialValue={[]}
					>
						<Select
							{...defaultSelectProps}
							options={termToOptions(product_cats)}
							placeholder="可多選"
						/>
					</Item>
					<Item
						name={['tag_ids']}
						label={keyLabelMapper('product_tag_id')}
						initialValue={[]}
					>
						<Select
							{...defaultSelectProps}
							options={termToOptions(product_tags)}
							placeholder="可多選"
						/>
					</Item>
				</div>
				<div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
					<div>
						<label className="text-sm pb-2 inline-block">簡短說明</label>
						<div>
							<BlockNoteDrawer
								resource="courses"
								dataProviderName="power-course"
								parseData={parseData}
							/>
						</div>
					</div>
					<div className="col-span-2">
						<DescriptionDrawer
							resource="courses"
							dataProviderName="power-course"
							initialEditor={course?.editor as 'power-editor' | 'elementor'}
							parseData={parseData}
						/>
					</div>

					<div className="mb-8">
						<label className="mb-3 tw-block">課程封面圖</label>
						<FileUpload />
						<Item hidden name={['files']} label="課程封面圖">
							<Input />
						</Item>
						<Item hidden name={['images']} initialValue={[]}>
							<Input />
						</Item>
					</div>
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
			<div className="mb-12">
				<Heading>講師資訊</Heading>
				<ListSelect<TUserRecord>
					listSelectProps={listSelectProps}
					rowName="display_name"
					rowUrl="user_avatar_url"
				/>
				<Item name={['teacher_ids']} hidden initialValue={[]} />
			</div>
		</Form>
	)
}

export const CourseDescription = memo(CourseDescriptionComponent)
