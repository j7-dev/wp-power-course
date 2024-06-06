import React, { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Tag } from 'antd'
import useOptions from '@/pages/admin/Courses/ProductSelector/hooks/useOptions'

export const ProductCat: FC<{ record: TCourseRecord }> = ({ record }) => {
  const { category_ids, tag_ids } = record
  const { options } = useOptions()
  const { product_cats = [], product_tags = [] } = options
  return (
    <>
      <div>
        {category_ids?.map((cat_id) => {
          return (
            <Tag key={cat_id} color="blue" bordered={false}>
              {product_cats.find((cat) => cat.id === cat_id)?.name}
            </Tag>
          )
        })}
      </div>
      <div>
        {tag_ids?.map((tag_id) => {
          return (
            <span key={tag_id} className="text-gray-400 text-xs mr-2">
              #{product_tags.find((tag) => tag.id === tag_id)?.name}
            </span>
          )
        })}
      </div>
    </>
  )
}
