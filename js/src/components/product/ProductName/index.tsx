import React, { FC } from 'react'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { siteUrl } from '@/utils'
import { Image } from 'antd'
import { EyeOutlined } from '@ant-design/icons'

export const ProductName: FC<{
  record: TProductRecord
}> = ({ record }) => {
  const { id, sku, name, image_url } = record
  const editable_post_id = record?.parent_id || id
  const editUrl = `${siteUrl}/wp-admin/post.php?post=${editable_post_id}&action=edit`
  return (
    <div className="flex">
      <div className="mr-4">
        <Image
          className="rounded-md object-cover"
          preview={{
            mask: <EyeOutlined />,
            maskClassName: 'rounded-md',
            forceRender: true,
          }}
          width={40}
          height={40}
          src={image_url || defaultImage}
          fallback={defaultImage}
        />
      </div>
      <div className="flex-1">
        <a href={editUrl} target="_blank" rel="noreferrer">
          <p className="mb-1">{renderHTML(name)}</p>
        </a>
        <div className="flex text-[0.675rem] text-gray-500">
          <span className="pr-3">{`ID: ${id}`}</span>
          {sku && <span className="pr-3">{`SKU: ${sku}`}</span>}
        </div>
      </div>
    </div>
  )
}
