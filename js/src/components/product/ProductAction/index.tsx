import React, { FC } from 'react'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import AddChapter from '@/components/product/ProductAction/AddChapter'
import DeleteProduct from '@/components/product/ProductAction/DeleteProduct'

export const ProductAction: FC<{
  record: TProductRecord
}> = ({ record }) => {
  return (
    <div className="flex gap-2">
      <AddChapter />
      <DeleteProduct />
    </div>
  )
}
