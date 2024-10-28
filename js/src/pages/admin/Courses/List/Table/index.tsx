import { useEffect, memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin, Button, TableProps, Card } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
	initialFilteredValues,
} from '@/components/product/ProductTable/Filter'
import { HttpError, useCreate } from '@refinedev/core'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { TFilterProps } from '@/components/product/ProductTable/types'
import {
	onSearch,
	keyLabelMapper,
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import useValueLabelMapper from '@/pages/admin/Courses/List/hooks/useValueLabelMapper'
import useColumns from '@/pages/admin/Courses/List/hooks/useColumns'
import { PlusOutlined } from '@ant-design/icons'
import DeleteButton from './DeleteButton'

const Main = () => {
	const { tableProps, searchFormProps } = useTable<
		TCourseBaseRecord,
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
		useRowSelection<TCourseBaseRecord>({
			getCheckboxProps: (record) => {
				const isVariation = getIsVariation(record?.type)
				return {
					disabled: isVariation,
					className: isVariation ? 'tw-hidden' : '',
				}
			},
		})

	const columns = useColumns()

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
					{...(defaultTableProps as unknown as TableProps<TCourseBaseRecord>)}
					{...tableProps}
					pagination={{
						...tableProps.pagination,
						...getDefaultPaginationProps({ label: '課程' }),
					}}
					rowSelection={rowSelection}
					columns={columns}
					rowKey={(record) => record.id.toString()}
				/>
			</Card>
		</Spin>
	)
}

export default memo(Main)
