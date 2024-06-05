import { useEffect } from 'react'
import { useTable } from '@refinedev/antd'
import { Table, FormInstance, Spin } from 'antd'
import { FilterTags, useRowSelection } from 'antd-toolkit'
import Filter, {
  initialFilteredValues,
} from '@/pages/admin/Courses/CourseSelector/Filter'
import MobileFilter from '@/pages/admin/Courses/CourseSelector/Filter/MobileFilter'
import { HttpError } from '@refinedev/core'
import {
  TFilterProps,
  TProductRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import {
  keyLabelMapper,
  onSearch,
  defaultPaginationProps,
  defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'
import { getInitialFilters, getIsVariation } from '@/utils'
import {
  ProductName,
  ProductType,
  ProductPrice,
  ProductTotalSales,
  ProductCat,
  ProductStock,
  ProductAction,
} from '@/components/product'
import useValueLabelMapper from '@/pages/admin/Courses/CourseSelector/hooks/useValueLabelMapper'
import { useWindowSize } from '@uidotdev/usehooks'
import { useAtom } from 'jotai'
import { addedProductIdsAtom } from '@/pages/admin/Courses/atom'
import { AddCourseButton } from '@/components/course'

const index = () => {
  const { width = 1920 } = useWindowSize()
  const isMobile = width ? width < 810 : false

  const { tableProps, searchFormProps, filters } = useTable<
    TProductRecord,
    HttpError,
    TFilterProps
  >({
    resource: 'courses',
    onSearch,
    filters: {
      permanent: [
        {
          field: 'status',
          operator: 'eq',
          value: ['publish'],
        },
      ],
      initial: getInitialFilters(initialFilteredValues),
    },
    queryOptions: {
      staleTime: 1000 * 60 * 60 * 24,
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
        className: isVariation ? 'hidden' : '',
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
   */

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
                'is_second_hand',
                'downloadable',
                'virtual',
                'sold_individually',
              ]}
            />
          </div>
          <AddCourseButton />

          <Table
            {...defaultTableProps}
            {...tableProps}
            pagination={{
              ...tableProps.pagination,
              ...defaultPaginationProps,
            }}
            rowSelection={rowSelection}
          >
            <Table.Column<TProductRecord>
              title="商品名稱"
              dataIndex="name"
              width={300}
              render={(_, record) => <ProductName record={record} />}
            />
            <Table.Column<TProductRecord>
              title="商品類型"
              dataIndex="type"
              width={180}
              render={(_, record) => <ProductType record={record} />}
            />
            <Table.Column<TProductRecord>
              title="價格"
              dataIndex="price"
              width={150}
              render={(_, record) => <ProductPrice record={record} />}
            />
            <Table.Column<TProductRecord>
              title="總銷量"
              dataIndex="total_sales"
              width={150}
              render={(_, record) => <ProductTotalSales record={record} />}
            />
            <Table.Column<TProductRecord>
              title="庫存"
              dataIndex="stock"
              width={150}
              render={(_, record) => <ProductStock record={record} />}
            />
            <Table.Column<TProductRecord>
              title="商品分類 / 商品標籤"
              dataIndex="category_ids"
              render={(_, record) => <ProductCat record={record} />}
            />
            <Table.Column<TProductRecord>
              title="操作"
              dataIndex="_actions"
              render={(_, record) => <ProductAction record={record} />}
            />
          </Table>
        </Spin>
      </div>
    </div>
  )
}

export default index
