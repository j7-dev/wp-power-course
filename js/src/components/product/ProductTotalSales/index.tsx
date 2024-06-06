import React, { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Badge, Tooltip } from 'antd'
import useOptions from '@/pages/admin/Courses/ProductSelector/hooks/useOptions'

enum ColorGrade {
  'tier-5' = '#ffccc7',
  'tier-4' = '#ffa39e',
  'tier-3' = '#ff7875',
  'tier-2' = '#ff4d4f',
  'tier-1' = '#f5222d',
}

export const ProductTotalSales: FC<{ record: TCourseRecord }> = ({
  record,
}) => {
  const { total_sales } = record
  if (!total_sales) return null
  const { options } = useOptions()
  const { top_sales_products = [] } = options
  const max_sales = top_sales_products?.[0]?.total_sales || 0
  const { color, label } = get_tier(total_sales, max_sales)

  return (
    <Tooltip zIndex={1000000 + 20} title={label}>
      <Badge count={total_sales} color={color} showZero />
    </Tooltip>
  )
}

function get_tier(total_sales: number, max_sales: number) {
  if (total_sales > max_sales * 0.8 || max_sales === 0) {
    return {
      color: ColorGrade['tier-1'],
      tier: 'tier-1',
      label: '最暢銷產品 (前20%)',
    }
  } else if (total_sales > max_sales * 0.6) {
    return {
      color: ColorGrade['tier-2'],
      tier: 'tier-2',
      label: '還不錯的暢銷產品 (前40%)',
    }
  } else if (total_sales > max_sales * 0.4) {
    return {
      color: ColorGrade['tier-3'],
      tier: 'tier-3',
      label: '一般銷量產品 (前60%)',
    }
  } else if (total_sales > max_sales * 0.2) {
    return {
      color: ColorGrade['tier-4'],
      tier: 'tier-4',
      label: '有點不暢銷產品 (後40%)',
    }
  } else {
    return {
      color: ColorGrade['tier-5'],
      tier: 'tier-5',
      label: '最不暢銷產品 (後20%)',
    }
  }
}
