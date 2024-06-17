import React, { FC } from 'react'
import { CheckCard } from '@ant-design/pro-components'
import { SaleRange } from '@/components/general'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'

const ProductCheckCard: FC<{
  product: TProductRecord
  show: (_product: TProductRecord) => () => void
}> = ({ product, show }) => {
  const { id, name, sale_date_range, images, status, price_html } = product
  const imgUrl = images?.[0]?.url || defaultImage

  return (
    <CheckCard
      className="w-full"
      title={
        <div className="w-full flex flex-col">
          <div className="group aspect-video w-full rounded overflow-hidden mb-2">
            <img
              className="group-hover:scale-125 transition duration-300 ease-in-out object-cover w-full h-full"
              src={imgUrl}
            />
          </div>
          <div>
            {renderHTML(name)}
            <p className="m-0 text-[0.675rem] text-gray-500">ID: {id}</p>
          </div>
        </div>
      }
      description={
        <>
          <div className="whitespace-nowrap">{renderHTML(price_html)}</div>
          <div className="whitespace-nowrap">
            <SaleRange saleDateRange={sale_date_range} />
          </div>
        </>
      }
      checked={status === 'publish'}
      onClick={show(product)}
    />
  )
}

export default ProductCheckCard
