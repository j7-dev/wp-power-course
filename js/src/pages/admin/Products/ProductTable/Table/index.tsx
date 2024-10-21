import { useEffect, memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin, TableProps, Card, Form } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
	initialFilteredValues,
} from '@/components/product/ProductTable/Filter'
import { HttpError } from '@refinedev/core'
import {
	TFilterProps,
	TProductRecord,
	TBindCoursesData,
} from '@/components/product/ProductTable/types'
import {
	onSearch,
	keyLabelMapper,
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import useValueLabelMapper from '@/pages/admin/Products/ProductTable/hooks/useValueLabelMapper'
import {
	BindCourses,
	UpdateBoundCourses,
	UnbindCourses,
} from '@/components/product'
import { useSetAtom } from 'jotai'
import useColumns from '@/pages/admin/Products/ProductTable/hooks/useColumns'
import { productsAtom } from '@/pages/admin/Products/ProductTable'
import { useGCDItems } from '@/hooks'
import { WatchLimit } from '@/components/formItem'

const Main = () => {
	const { tableProps, searchFormProps } = useTable<
		TProductRecord,
		HttpError,
		TFilterProps
	>({
		resource: 'products',
		onSearch,
		filters: {
			initial: getInitialFilters({ ...initialFilteredValues, is_course: '0' }),
		},
	})

	const { valueLabelMapper } = useValueLabelMapper()

	const { rowSelection, selectedRowKeys } = useRowSelection<TProductRecord>({
		getCheckboxProps: (record) => {
			const isVariableProduct = record?.type?.startsWith('variable')
			return {
				disabled: !!isVariableProduct,
				className: isVariableProduct ? 'tw-hidden' : '',
			}
		},
	})

	/*
	 * 換頁時，將已加入的商品全局狀態同步到當前頁面的 selectedRowKeys 狀態
	 * 這樣在換頁時，已加入的商品就會被選中
	 */

	const setCourses = useSetAtom(productsAtom)

	useEffect(() => {
		setCourses([...(tableProps?.dataSource || [])])
	}, [tableProps?.dataSource])

	const columns = useColumns()

	const selectedAllBindCoursesData = selectedRowKeys
		.map((key) => {
			return tableProps?.dataSource?.find((product) => product.id === key)
				?.bind_courses_data
		})
		.filter((item) => item !== undefined)

	// 取得最大公約數的課程
	const { GcdItemsTags, selectedGCDs, setSelectedGCDs, gcdItems } =
		useGCDItems<TBindCoursesData>({
			allItems: selectedAllBindCoursesData,
		})

	return (
		<Spin spinning={tableProps?.loading as boolean}>
			<Card title="篩選" className="mb-4">
				<Filter searchFormProps={searchFormProps} />
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
							'is_course',
						]}
					/>
				</div>
			</Card>
			<Card>
				<Form layout="vertical">
					<div className="grid grid-cols-4 gap-x-6">
						<div>
							<WatchLimit />
						</div>
						<div className="col-span-3">
							<div className="mb-4">
								<BindCourses
									product_ids={selectedRowKeys as string[]}
									label="綁定其他課程"
								/>
							</div>
							<div className="mb-4 flex gap-x-6">
								<div>
									<label className="block mb-2">批量操作</label>
									<div className="flex gap-x-4">
										<UpdateBoundCourses
											product_ids={selectedRowKeys as string[]}
											course_ids={selectedGCDs}
											onSettled={() => {
												setSelectedGCDs([])
											}}
										/>
										<UnbindCourses
											product_ids={selectedRowKeys as string[]}
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
						</div>
					</div>
				</Form>
				<Table
					{...(defaultTableProps as unknown as TableProps<TProductRecord>)}
					{...tableProps}
					pagination={{
						...tableProps.pagination,
						...getDefaultPaginationProps({ label: '商品' }),
					}}
					rowSelection={rowSelection}
					columns={columns}
				/>
			</Card>
		</Spin>
	)
}

export default memo(Main)
