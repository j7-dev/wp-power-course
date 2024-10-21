import { useEffect, memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin, Form, Button, TableProps, Card } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
	initialFilteredValues,
} from '@/components/product/ProductTable/Filter'
import { HttpError, useCreate } from '@refinedev/core'
import { TCourseRecord } from '@/pages/admin/Courses/CourseTable/types'
import { TFilterProps } from '@/components/product/ProductTable/types'
import {
	onSearch,
	keyLabelMapper,
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import useValueLabelMapper from '@/pages/admin/Courses/CourseTable/hooks/useValueLabelMapper'
import { useSetAtom } from 'jotai'
import { SortableChapter } from '@/components/course'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useCourseFormDrawer } from '@/hooks'
import { ChapterDrawer } from '@/components/course/ChapterDrawer'
import useColumns from '@/pages/admin/Courses/CourseTable/hooks/useColumns'
import { PlusOutlined } from '@ant-design/icons'
import { coursesAtom } from '@/pages/admin/Courses/CourseTable'
import DeleteButton from './DeleteButton'

const Main = () => {
	const { tableProps, searchFormProps } = useTable<
		TCourseRecord,
		HttpError,
		TFilterProps
	>({
		resource: 'courses',
		onSearch,
		filters: {
			initial: getInitialFilters(initialFilteredValues),
		},
	})

	const { valueLabelMapper } = useValueLabelMapper()

	const { rowSelection, selectedRowKeys, setSelectedRowKeys } =
		useRowSelection<TCourseRecord>({
			getCheckboxProps: (record) => {
				const isVariation = getIsVariation(record?.type)
				return {
					disabled: isVariation,
					className: isVariation ? 'tw-hidden' : '',
				}
			},
		})

	const setCourses = useSetAtom(coursesAtom)

	useEffect(() => {
		setCourses([...(tableProps?.dataSource || [])])
	}, [tableProps?.dataSource])

	// Drawer

	const [courseForm] = Form.useForm()
	const { show: showCourseDrawer, drawerProps: courseDrawerProps } =
		useCourseFormDrawer({
			form: courseForm,
			resource: 'courses',
		})

	const [chapterForm] = Form.useForm()
	const { show: showChapterDrawer, drawerProps: chapterDrawerProps } =
		useCourseFormDrawer({ form: chapterForm, resource: 'chapters' })

	const columns = useColumns({
		showCourseDrawer,
		showChapterDrawer,
	})

	const { mutate: create, isLoading: isCreating } = useCreate({
		resource: 'courses',
		invalidates: ['list'],
		meta: {
			headers: { 'Content-Type': 'multipart/form-data;' },
		},
	})

	const createCourse = () => {
		create({
			values: {
				name: '新課程',
			},
		})
	}

	return (
		<Spin spinning={tableProps?.loading as boolean}>
			<Card title="篩選" className="mb-4">
				<Filter
					searchFormProps={searchFormProps}
					optionParams={{
						endpoint: 'courses/options',
					}}
					isCourse={true}
				/>
				<div className="mt-2">
					<FilterTags
						form={searchFormProps?.form as FormInstance<TFilterProps>}
						keyLabelMapper={keyLabelMapper}
						valueLabelMapper={valueLabelMapper}
						booleanKeys={[
							'featured',
							'downloadable',
							'virtual',
							'sold_individually',
						]}
					/>
				</div>
			</Card>
			<Card>
				<div className="mb-4 flex justify-between">
					<Button
						loading={isCreating}
						type="primary"
						icon={<PlusOutlined />}
						onClick={createCourse}
					>
						新增課程
					</Button>
					<DeleteButton
						selectedRowKeys={selectedRowKeys}
						setSelectedRowKeys={setSelectedRowKeys}
					/>
				</div>
				<Table
					{...(defaultTableProps as unknown as TableProps<TCourseRecord>)}
					{...tableProps}
					pagination={{
						...tableProps.pagination,
						...getDefaultPaginationProps({ label: '課程' }),
					}}
					rowSelection={rowSelection}
					columns={columns}
					expandable={{
						expandedRowRender: (record) => (
							<SortableChapter record={record} show={showChapterDrawer} />
						),
						rowExpandable: (record: TCourseRecord) =>
							!!record?.chapters?.length,
					}}
					rowKey={(record) => record.id.toString()}
				/>
			</Card>

			<Form layout="vertical" form={courseForm}>
				<CourseDrawer {...courseDrawerProps} />
			</Form>

			<Form layout="vertical" form={chapterForm}>
				<ChapterDrawer {...chapterDrawerProps} />
			</Form>
		</Spin>
	)
}

export default memo(Main)
