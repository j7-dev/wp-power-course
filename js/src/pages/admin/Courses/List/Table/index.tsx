import { PlusOutlined, DownOutlined } from '@ant-design/icons'
import { useTable } from '@refinedev/antd'
import { HttpError, useCreate } from '@refinedev/core'
import {
	Table,
	FormInstance,
	Spin,
	Button,
	TableProps,
	Card,
	Dropdown,
	MenuProps,
} from 'antd'
import { useRowSelection } from 'antd-toolkit'
import { FilterTags } from 'antd-toolkit/refine'
import { memo } from 'react'

import Filter, {
	initialFilteredValues,
} from '@/components/product/ProductTable/Filter'
import { TFilterProps } from '@/components/product/ProductTable/types'
import {
	onSearch,
	keyLabelMapper,
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import useColumns from '@/pages/admin/Courses/List/hooks/useColumns'
import useValueLabelMapper from '@/pages/admin/Courses/List/hooks/useValueLabelMapper'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { getInitialFilters, getIsVariation } from '@/utils'

import DeleteButton from './DeleteButton'

const Main = () => {
	const { tableProps, searchFormProps } = useTable<
		TCourseBaseRecord,
		HttpError,
		TFilterProps
	>({
		resource: 'courses',
		dataProviderName: 'power-course',
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
		dataProviderName: 'power-course',
		invalidates: ['list'],
		meta: {
			headers: { 'Content-Type': 'multipart/form-data;' },
		},
	})

	const createCourse = (productType: 'simple' | 'external' = 'simple') => {
		const values: Record<string, string> =
			productType === 'external'
				? { name: '新課程', product_type: 'external', external_url: '' }
				: { name: '新課程' }

		create({ values })
	}

	const createMenuItems: MenuProps['items'] = [
		{
			key: 'simple',
			label: '站內課程',
			onClick: () => createCourse('simple'),
		},
		{
			key: 'external',
			label: '外部課程',
			onClick: () => createCourse('external'),
		},
	]

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
					<Dropdown menu={{ items: createMenuItems }} trigger={['click']}>
						<Button
							loading={isCreating}
							type="primary"
							icon={<PlusOutlined />}
						>
							新增課程 <DownOutlined />
						</Button>
					</Dropdown>
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
