import { useTable, useModal } from '@refinedev/antd'
import { HttpError, useApiUrl } from '@refinedev/core'
import {
	Table,
	TableProps,
	FormInstance,
	Button,
	Modal,
	CardProps,
	message,
} from 'antd'
import { useRowSelection, Card } from 'antd-toolkit'
import { FilterTags } from 'antd-toolkit/refine'
import { useAtom } from 'jotai'
import React, { memo, useEffect, useState } from 'react'

import {
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import {
	GrantCourseAccess,
	RemoveCourseAccess,
	ModifyCourseExpireDate,
} from '@/components/user'
import { useGCDItems, useEnv } from '@/hooks'
import { TUserRecord, TAVLCourse } from '@/pages/admin/Courses/List/types'

import { selectedUserIdsAtom } from './atom'
import CsvUpload from './CsvUpload'
import Filter, { TFilterValues } from './Filter'
import HistoryDrawer from './HistoryDrawer'
import useColumns from './hooks/useColumns'
import SelectedUser from './SelectedUser'
import { keyLabelMapper } from './utils'

const UserTableComponent = ({
	canGrantCourseAccess = false,
	tableProps: overrideTableProps,
	cardProps,
}: {
	canGrantCourseAccess?: boolean
	tableProps?: TableProps<TUserRecord>
	cardProps?: CardProps & { showCard?: boolean }
}) => {
	const [selectedUserIds, setSelectedUserIds] = useAtom(selectedUserIdsAtom)

	const { searchFormProps, tableProps, filters } = useTable<
		TUserRecord,
		HttpError,
		TFilterValues
	>({
		resource: 'users',
		pagination: {
			pageSize: 20,
		},
		filters: {
			permanent: [
				{
					field: 'meta_keys',
					operator: 'eq',
					value: ['is_teacher', 'avl_courses'],
				},
			],
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

	const currentAllKeys =
		tableProps?.dataSource?.map((record) => record?.id.toString()) || []

	// 多選
	const { rowSelection, setSelectedRowKeys, selectedRowKeys } =
		useRowSelection<TUserRecord>({
			onChange: (currentSelectedRowKeys: React.Key[]) => {
				setSelectedRowKeys(currentSelectedRowKeys)

				/**
				 * 不在這頁的已選擇用戶
				 * @type string[]
				 */
				const setSelectedUserIdsNotInCurrentPage = selectedUserIds.filter(
					(selectedUserId) => !currentAllKeys.includes(selectedUserId),
				)

				/**
				 * 在這頁的已選擇用戶
				 * @type string[]
				 */
				const currentSelectedRowKeysStringify = currentSelectedRowKeys.map(
					(key) => key.toString(),
				)

				setSelectedUserIds(() => {
					// 把這頁的已選用戶加上 不在這頁的已選用戶
					const newKeys = new Set([
						...setSelectedUserIdsNotInCurrentPage,
						...currentSelectedRowKeysStringify,
					])
					return [...newKeys]
				})
			},
		})

	/*
	 * 換頁時，將已加入的商品全局狀態同步到當前頁面的 selectedRowKeys 狀態
	 */
	useEffect(() => {
		if (!tableProps?.loading) {
			const filteredKey =
				currentAllKeys?.filter((id) => selectedUserIds?.includes(id)) || []
			setSelectedRowKeys(filteredKey)
		}
	}, [
		JSON.stringify(filters),
		JSON.stringify(tableProps?.pagination),
		tableProps?.loading,
	])

	useEffect(() => {
		// 如果清空已選擇的用戶，連帶清空 selectedRowKeys (畫面上的打勾)
		if (selectedUserIds.length === 0) {
			setSelectedRowKeys([])
		}
	}, [selectedUserIds.length])

	useEffect(() => {
		// 剛載入組件時，清空已選擇的用戶
		setSelectedUserIds([])
	}, [])

	const columns = useColumns()

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

	// CSV 上傳 Modal
	const { show, modalProps } = useModal()

	// 學員匯出 CSV
	const apiUrl = useApiUrl('power-course')
	const { NONCE, AXIOS_INSTANCE } = useEnv()
	const [exportLoading, setExportLoading] = useState(false)

	/**
	 * 從篩選表單取得當前篩選值，建構 URL query string
	 */
	const getExportParams = () => {
		const form = searchFormProps?.form as FormInstance<TFilterValues>
		const values = form?.getFieldsValue()
		const params = new URLSearchParams()
		params.append('_wpnonce', NONCE)
		if (values?.search) {
			params.append('search', values.search)
		}
		if (values?.avl_course_ids?.length) {
			values.avl_course_ids.forEach((id) =>
				params.append('avl_course_ids[]', id),
			)
		}
		if (values?.include?.length) {
			values.include.forEach((id) => params.append('include[]', id))
		}
		return params.toString()
	}

	/**
	 * 點擊匯出按鈕：先取得預估筆數，再彈出確認 Modal
	 */
	const handleExportClick = async () => {
		setExportLoading(true)
		try {
			const exportParams = getExportParams()
			const { data } = await AXIOS_INSTANCE.get<{ count: number }>(
				`${apiUrl}/students/export-count?${exportParams}`,
			)
			const count = data?.count ?? 0
			Modal.confirm({
				title: '學員匯出 CSV',
				content:
					count > 0
						? `預估匯出 ${count} 筆資料，確認要匯出嗎？`
						: '目前篩選條件下無學員資料',
				okText: '確認匯出',
				cancelText: '取消',
				onOk:
					count > 0
						? () => {
								window.open(
									`${apiUrl}/students/export-all?${exportParams}`,
									'_blank',
								)
							}
						: undefined,
				okButtonProps: { disabled: count === 0 },
			})
		} catch {
			message.error('取得匯出筆數失敗')
		} finally {
			setExportLoading(false)
		}
	}

	return (
		<>
			<Card title="篩選" variant="borderless" className="mb-4" {...cardProps}>
				<Filter formProps={searchFormProps} />
				<FilterTags
					form={searchFormProps.form as FormInstance}
					keyLabelMapper={keyLabelMapper}
				/>
			</Card>
			<Card variant="borderless" {...cardProps}>
				{canGrantCourseAccess && (
					<>
						<div className="mt-4">
							<GrantCourseAccess
								user_ids={selectedRowKeys as string[]}
								label="新增其他課程"
							/>
						</div>

						<div className="mt-4 flex gap-x-6 justify-between">
							<div>
								{/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
								<label className="tw-block mb-2">批次操作</label>
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
									{/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
									<label className="tw-block mb-2">選擇課程</label>
									<GcdItemsTags />
								</div>
							)}
							<div className="flex gap-x-4 self-end">
								<Button
									onClick={handleExportClick}
									color="primary"
									variant="outlined"
									loading={exportLoading}
								>
									學員匯出 CSV
								</Button>
								<Button onClick={show} color="primary" variant="outlined">
									CSV 批次上傳學員權限
								</Button>
							</div>
						</div>
					</>
				)}

				<SelectedUser
					user_ids={selectedUserIds}
					onClear={() => {
						setSelectedUserIds([])
					}}
					onSelected={() => {
						const searchForm = searchFormProps?.form
						if (!searchForm) return
						searchForm.setFieldValue(['include'], selectedUserIds)
						searchForm.submit()
					}}
				/>

				<Table
					{...(defaultTableProps as unknown as TableProps<TUserRecord>)}
					{...tableProps}
					className="mt-4"
					columns={columns}
					rowSelection={rowSelection}
					pagination={{
						...tableProps.pagination,
						...getDefaultPaginationProps({ label: '用戶' }),
					}}
					{...overrideTableProps}
				/>
			</Card>
			{canGrantCourseAccess && (
				<Modal
					{...modalProps}
					centered
					title="CSV 批次上傳學員權限"
					footer={null}
					width={800}
				>
					<CsvUpload />
				</Modal>
			)}

			<HistoryDrawer />
		</>
	)
}

export const UserTable = memo(UserTableComponent)
export * from './atom'
export { default as SelectedUser } from './SelectedUser'
