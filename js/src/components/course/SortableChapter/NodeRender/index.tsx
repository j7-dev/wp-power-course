import React, { FC } from 'react'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { ProductName } from '@/components/product'
import { getPostStatus } from '@/utils'
import { Tag } from 'antd'

const NodeRender: FC<{
  record: TCourseRecord | TChapterRecord
  show: {
    showCourseDrawer: (_record: TCourseRecord | undefined) => () => void
    showChapterDrawer: (_record: TChapterRecord | undefined) => () => void
  }
}> = ({ record, show }) => {
  return (
    <div className="flex gap-4 justify-start items-center">
      <div>
        <ProductName record={record} show={show} />
      </div>
      <div>
        <Tag color={getPostStatus(record?.status)?.color}>
          {getPostStatus(record?.status)?.label}
        </Tag>
      </div>
      <div>{record?.hours}</div>
      {/* <ProductType record={record} /> */}
      {/* <ProductPrice record={record} />
      <ProductTotalSales record={record} />
      <ProductCat record={record} />
      <ProductStock record={record} /> */}
      {/* <ProductAction record={record} /> */}
    </div>
  )
}

export default NodeRender
