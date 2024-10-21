import React, { memo } from 'react'
import { useTable } from '@refinedev/antd'
import {
	TUserRecord,
	TAVLCourse,
} from '@/pages/admin/Courses/CourseTable/types'
import { Table, Form, TableProps, Card, FormInstance } from 'antd'
import useColumns from './hooks/useColumns'
import { useRowSelection, FilterTags } from 'antd-toolkit'
import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import { useUserFormDrawer, useGCDItems } from '@/hooks'
import {
	UserDrawer,
	GrantCourseAccess,
	RemoveCourseAccess,
	ModifyCourseExpireDate,
} from '@/components/user'
import Filter, { TFilterValues } from './Filter'
import { HttpError } from '@refinedev/core'
import { keyLabelMapper } from './utils'

const StudentTable = () => {
	const { searchFormProps, tableProps } = useTable<
		TUserRecord,
		HttpError,
		TFilterValues
	>({
		resource: 'users',
		pagination: {
			pageSize: 20,
		},
		onSearch: (values) => {
			return Object.keys(values).map((key) => {
				return {
					field: key,
					operator: 'contains',
					value: values[key as keyof TFilterValues],
				}
			})
		},
	})

	// 多選
	const { rowSelection, setSelectedRowKeys, selectedRowKeys } =
		useRowSelection<TUserRecord>({
			onChange: (currentSelectedRowKeys: React.Key[]) => {
				setSelectedRowKeys(currentSelectedRowKeys)
			},
		})

	const [form] = Form.useForm()
	const { show, drawerProps } = useUserFormDrawer({ form, resource: 'users' })
	const columns = useColumns({
		onClick: show,
	})

	const selectedAllAVLCourses = selectedRowKeys
		.map((key) => {
			return tableProps?.dataSource?.find((user) => user.id === key)
				?.avl_courses
		})
		.filter((courses) => courses !== undefined)

	// 取得最大公約數的課程
	const { GcdItemsTags, selectedGCDs, setSelectedGCDs, gcdItems } =
		useGCDItems<TAVLCourse>({
			allItems: selectedAllAVLCourses,
		})

	return (
		<>
			<Card title="篩選" bordered={false} className="mb-4">
				<Filter formProps={searchFormProps} />
				<FilterTags
					form={searchFormProps.form as FormInstance}
					keyLabelMapper={keyLabelMapper}
				/>
			</Card>
			<Card bordered={false}>
				<div className="mb-4">
					<GrantCourseAccess
						user_ids={selectedRowKeys as string[]}
						label="添加其他課程"
					/>
				</div>

				<div className="mb-4 flex gap-x-6">
					<div>
						<label className="block mb-2">批量操作</label>
						<div className="flex gap-x-4">
							<ModifyCourseExpireDate
								user_ids={selectedRowKeys as string[]}
								course_ids={selectedGCDs}
								onSettled={() => {
									setSelectedGCDs([])
								}}
							/>
							<RemoveCourseAccess
								user_ids={selectedRowKeys}
								course_ids={selectedGCDs}
								onSettled={() => {
									setSelectedGCDs([])
								}}
							/>
						</div>
					</div>
					{!!gcdItems.length && (
						<div className="flex-1">
							<label className="block mb-2">選擇課程</label>
							<GcdItemsTags />
						</div>
					)}
				</div>
				<Table
					{...(defaultTableProps as unknown as TableProps<TUserRecord>)}
					{...tableProps}
					columns={columns}
					rowSelection={rowSelection}
					pagination={{
						...tableProps.pagination,
						...getDefaultPaginationProps({ label: '學員' }),
					}}
				/>
			</Card>
			<Form layout="vertical" form={form}>
				<UserDrawer {...drawerProps} />
			</Form>
		</>
	)
}

export default memo(StudentTable)
