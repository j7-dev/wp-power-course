import { TFilterProps } from '@/pages/admin/Courses/ProductSelector/types'
import { PaginationProps, TableProps, RadioGroupProps } from 'antd'

export * from './onSearch'

export const filterLabels: {
  [key in keyof TFilterProps]: string
} = {
  s: '關鍵字',
  sku: '貨號(sku)',
  product_category_id: '商品分類',
  product_tag_id: '商品標籤',
  product_brand_id: '品牌',
  status: '商品狀態',
  featured: '精選商品',
  downloadable: '可下載',
  virtual: '虛擬商品',
  sold_individually: '單獨販售',
  backorders: '允許延期交貨',
  stock_status: '庫存狀態',
  date_created: '商品發佈日期',
  is_course: '商品狀態',
  price_range: '價格範圍',
}

export const keyLabelMapper = (key: keyof TFilterProps) => {
  return filterLabels?.[key] || key
}

export const defaultBooleanRadioButtonProps: {
  radioGroupProps: RadioGroupProps
} = {
  radioGroupProps: {
    size: 'small',
  },
}

export const defaultTableProps: TableProps = {
  size: 'small',
  rowKey: 'id',
  bordered: true,
  sticky: true,
}

export const defaultPaginationProps: PaginationProps & {
  position: [
    | 'topLeft'
    | 'topCenter'
    | 'topRight'
    | 'bottomLeft'
    | 'bottomCenter'
    | 'bottomRight',
  ]
} = {
  position: ['bottomCenter'],
  size: 'default',
  showSizeChanger: true,
  showQuickJumper: true,
  showTitle: true,
  showTotal: (total: number, range: [number, number]) =>
    `目前顯示第 ${range[0]} ~ ${range[1]} 個商品，總共有 ${total} 個商品`,
}
