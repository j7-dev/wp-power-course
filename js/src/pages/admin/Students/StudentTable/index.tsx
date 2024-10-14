import React, { memo } from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import {
	Table,
	message,
	Button,
	Form,
	TableProps,
	Card,
	Space,
	DatePicker,
} from 'antd'
import useColumns from './hooks/useColumns'
import useGCDCourses from './hooks/useGCDCourses'
import { useRowSelection } from 'antd-toolkit'
import { useApiUrl, useInvalidate } from '@refinedev/core'
import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'
import { PopconfirmDelete } from '@/components/general'
import { useUserFormDrawer } from '@/hooks'
import {
	UserDrawer,
	GrantCourseAccess,
	RemoveCourseAccess,
	ModifyCourseExpireDate,
} from '@/components/user'

const StudentTable = () => {
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const { tableProps } = useTable<TUserRecord>({
		resource: 'users',
		pagination: {
			pageSize: 20,
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
	const { GcdCoursesTags, selectedGCDs, setSelectedGCDs } = useGCDCourses({
		allUsersAVLCourses: selectedAllAVLCourses,
	})

	return (
		<>
			<Card title="Filter" bordered={false} className="mb-4">
				<div className="flex gap-4 mb-4"></div>
			</Card>
			<Card bordered={false}>
				<div className="mb-4">
					<GrantCourseAccess user_ids={selectedRowKeys as string[]} />
				</div>
				<div className="mb-4 flex justify-between">
					<div>
						<GcdCoursesTags />
					</div>
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
