import React from 'react'
import { Table, TableProps, Tag } from 'antd'
import {
  TChapterRecord,
  TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import {
  ProductName,
  ProductType,
  ProductPrice,
  ProductTotalSales,
  ProductCat,
  ProductStock,
  ProductAction,
} from '@/components/product'
import { getPostStatus } from '@/utils'

const useColumns = ({
  showCourseDrawer,
  showChapterDrawer,
}: {
  showCourseDrawer: (_record?: TCourseRecord | undefined) => () => void
  showChapterDrawer: (_record?: TChapterRecord | undefined) => () => void
}) => {
  const columns: TableProps<TCourseRecord>['columns'] = [
    Table.SELECTION_COLUMN,
    Table.EXPAND_COLUMN,
    {
      title: '商品名稱',
      dataIndex: 'name',
      width: 300,
      key: 'name',
      render: (_, record) => (
        <ProductName
          record={record}
          show={{ showCourseDrawer, showChapterDrawer }}
        />
      ),
    },
    {
      title: '狀態',
      dataIndex: 'status',
      width: 80,
      key: 'status',
      render: (_, record) => (
        <Tag color={getPostStatus(record?.status)?.color}>
          {getPostStatus(record?.status)?.label}
        </Tag>
      ),
    },
    {
      title: '時數',
      dataIndex: 'hours',
      width: 180,
      key: 'hours',
    },
    {
      title: '商品類型',
      dataIndex: 'type',
      width: 180,
      key: 'type',
      render: (_, record) => <ProductType record={record} />,
    },
    {
      title: '價格',
      dataIndex: 'price',
      width: 150,
      key: 'price',
      render: (_, record) => <ProductPrice record={record} />,
    },
    {
      title: '總銷量',
      dataIndex: 'total_sales',
      width: 150,
      key: 'total_sales',
      render: (_, record) => <ProductTotalSales record={record} />,
    },
    {
      title: '庫存',
      dataIndex: 'stock',
      width: 150,
      key: 'stock',
      render: (_, record) => <ProductStock record={record} />,
    },
    {
      title: '商品分類 / 商品標籤',
      dataIndex: 'category_ids',
      key: 'category_ids',
      render: (_, record) => <ProductCat record={record} />,
    },
    {
      title: '操作',
      dataIndex: '_actions',
      key: '_actions',
      render: (_, record) => <ProductAction record={record} />,
    },
  ]

  return columns
}

export default useColumns
