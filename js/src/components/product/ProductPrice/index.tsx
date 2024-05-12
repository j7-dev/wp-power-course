import { FC } from 'react'
import {
  TProductRecord,
  TProductVariation,
} from '@/pages/admin/Courses/ProductSelector/types'
import { renderHTML } from 'antd-toolkit'
import './style.scss'

export const ProductPrice: FC<{
  record: TProductRecord | TProductVariation
}> = ({ record }) => {
  const { price_html } = record
  return <div className="at-product-price">{renderHTML(price_html)}</div>
}
