import { useEffect, memo } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin, TableProps, Card } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
	initialFilteredValues,
} from '@/components/product/ProductTable/Filter'
import MobileFilter from '@/components/product/ProductTable/Filter/MobileFilter'
import { HttpError } from '@refinedev/core'
import {
	TFilterProps,
	TProductRecord,
} from '@/components/product/ProductTable/types'
import {
	onSearch,
	keyLabelMapper,
	getDefaultPaginationProps,
	defaultTableProps,
} from '@/components/product/ProductTable/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import useValueLabelMapper from '@/pages/admin/Products/ProductTable/hooks/useValueLabelMapper'

import { useAtom, useSetAtom } from 'jotai'
import { addedProductIdsAtom } from '@/pages/admin/Products/atom'
import useColumns from '@/pages/admin/Products/ProductTable/hooks/useColumns'
import { productsAtom } from '@/pages/admin/Products/ProductTable'

const Main = () => {
	const { tableProps, searchFormProps, filters } = useTable<
		TProductRecord,
		HttpError,
		TFilterProps
	>({
		resource: 'products',
		onSearch,
		filters: {
			initial: getInitialFilters({
				...initialFilteredValues,
				meta_key: '_is_course',
				meta_value: 'no',
			}),
		},
	})

	const currentAllKeys =
		tableProps?.dataSource?.map((record) => record?.id.toString()) || []
	const [addedProductIds, setAddedProductIds] = useAtom(addedProductIdsAtom)

	const { valueLabelMapper } = useValueLabelMapper()

	const { rowSelection, setSelectedRowKeys } = useRowSelection<TProductRecord>({
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

	const setCourses = useSetAtom(productsAtom)

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

	const columns = useColumns()

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
						]}
					/>
				</div>
			</Card>
			<Card>
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
