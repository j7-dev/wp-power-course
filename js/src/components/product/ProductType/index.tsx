import React, { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Tag, Tooltip } from 'antd'
import {
  StarFilled,
  StarOutlined,
  CloudOutlined,
  CloudFilled,
} from '@ant-design/icons'
import { IoMdDownload } from 'react-icons/io'
import { productTypes } from '@/utils'

export const ProductType: FC<{ record: TCourseRecord }> = ({ record }) => {
  const type = record?.type || ''
  if (!type || 'chapter' === type) return null
  const tag = productTypes.find((productType) => productType.value === type)
  return (
    <div className="flex items-center gap-2">
      <Tag bordered={false} color={tag?.color} className="m-0">
        {tag?.label}
      </Tag>

      <Tooltip
        zIndex={1000000 + 20}
        title={`${record?.featured ? '' : '非'}精選商品`}
      >
        {record?.featured ? (
          <StarFilled className="text-yellow-400" />
        ) : (
          <StarOutlined className="text-gray-400" />
        )}
      </Tooltip>

      <Tooltip
        zIndex={1000000 + 20}
        title={`${record?.virtual ? '' : '非'}虛擬商品`}
      >
        {record?.virtual ? (
          <CloudFilled className="text-primary" />
        ) : (
          <CloudOutlined className="text-gray-400" />
        )}
      </Tooltip>

      <Tooltip
        zIndex={1000000 + 20}
        title={`${record?.downloadable ? '' : '不'}可下載`}
      >
        {record?.downloadable ? (
          <IoMdDownload className="text-gray-900" />
        ) : (
          <IoMdDownload className="text-gray-400" />
        )}
      </Tooltip>
    </div>
  )
}
