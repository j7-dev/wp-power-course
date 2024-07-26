import { useEffect } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin, Form, Button } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
	initialFilteredValues,
} from '@/pages/admin/Courses/CourseSelector/Filter'
import MobileFilter from '@/pages/admin/Courses/CourseSelector/Filter/MobileFilter'
import { HttpError } from '@refinedev/core'
import {
	TFilterProps,
	TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import {
	keyLabelMapper,
	onSearch,
	defaultPaginationProps,
	defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import useValueLabelMapper from '@/pages/admin/Courses/CourseSelector/hooks/useValueLabelMapper'
import { useWindowSize } from '@uidotdev/usehooks'
import { useAtom, useSetAtom } from 'jotai'
import { addedProductIdsAtom } from '@/pages/admin/Courses/atom'
import { SortableChapter } from '@/components/course'
import { CourseDrawer } from '@/components/course/CourseDrawer'
import { useCourseFormDrawer } from '@/hooks'
import { ChapterDrawer } from '@/components/course/ChapterDrawer'
import useColumns from '@/pages/admin/Courses/CourseSelector/hooks/useColumns'
import { PlusOutlined } from '@ant-design/icons'
import { coursesAtom } from '@/pages/admin/Courses/CourseSelector'

const index = () => {
	const { width = 1920 } = useWindowSize()
	const isMobile = width ? width < 810 : false
	const { tableProps, searchFormProps, filters } = useTable<
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

	const currentAllKeys =
		tableProps?.dataSource?.map((record) => record?.id.toString()) || []
	const [addedProductIds, setAddedProductIds] = useAtom(addedProductIdsAtom)

	const { valueLabelMapper } = useValueLabelMapper()

	const { rowSelection, setSelectedRowKeys } = useRowSelection<TCourseRecord>({
		getCheckboxProps: (record) => {
			const isVariation = getIsVariation(record?.type)
			return {
				disabled: isVariation,
				className: isVariation ? 'tw-hidden' : '',
			}
		},
		onChange: (currentSelectedRowKeys: React.Key[]) => {
			setSelectedRowKeys(currentSelectedRowKeys)
			const addedProductIdsNotInCurrentPage = addedProductIds.filter(
				(addedProductId) => !currentAllKeys.includes(addedProductId),
			)

			const currentSelectedRowKeysStringify = currentSelectedRowKeys.map(
				(key) => key.toString(),
			)

			setAddedProductIds(() => {
				const newKeys = new Set([
					...addedProductIdsNotInCurrentPage,
					...currentSelectedRowKeysStringify,
				])
				return [...newKeys]
			})
		},
	})

	/*
	 * 換頁時，將已加入的商品全局狀態同步到當前頁面的 selectedRowKeys 狀態
	 * 這樣在換頁時，已加入的商品就會被選中
	 */

	const setCourses = useSetAtom(coursesAtom)

	useEffect(() => {
		if (!tableProps?.loading) {
			const filteredKey =
				currentAllKeys?.filter((id) => addedProductIds?.includes(id)) || []
			setSelectedRowKeys(filteredKey)
		}
	}, [
		JSON.stringify(filters),
		JSON.stringify(tableProps?.pagination),
		tableProps?.loading,
	])

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

	return (
		<div className="flex gap-8 flex-col">
			{!isMobile && (
				<div className="w-full">
					<Filter searchFormProps={searchFormProps} />
				</div>
			)}
			<div className="w-full">
				<Spin spinning={tableProps?.loading as boolean}>
					<div className="mb-4">
						{isMobile && <MobileFilter searchFormProps={searchFormProps} />}
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
					<Button
						type="primary"
						className="mb-4"
						icon={<PlusOutlined />}
						onClick={showCourseDrawer()}
					>
						新增課程
					</Button>

					<Table
						{...defaultTableProps}
						{...tableProps}
						pagination={{
							...tableProps.pagination,
							...defaultPaginationProps,
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

					<Form layout="vertical" form={courseForm}>
						<CourseDrawer {...courseDrawerProps} />
					</Form>

					<Form layout="vertical" form={chapterForm}>
						<ChapterDrawer {...chapterDrawerProps} />
					</Form>
				</Spin>
			</div>
		</div>
	)
}

export default index
