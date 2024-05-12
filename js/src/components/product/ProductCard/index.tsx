import { FC } from 'react'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { ProductPrice } from '@/components/product'
import { AddToCartButton } from '@/components/woocommerce'

import { Button, ButtonProps } from 'antd'

export const ProductCard: FC<{
  record: TProductRecord
  cardBodyProps?: React.HTMLAttributes<HTMLDivElement>
  cardButtonProps?: {
    more?: ButtonProps
    addToCart?: ButtonProps
  }
}> = ({ record, cardBodyProps, cardButtonProps }) => {
  const { id, name, image_url = defaultImage, type } = record
  const moreButtonProps = cardButtonProps?.more || {}
  const addToCartButtonProps = cardButtonProps?.addToCart || {}
  return (
    <>
      <div data-product-id={id} className="group relative pb-12">
        <div {...cardBodyProps}>
          <div className="w-full aspect-square overflow-hidden">
            <img
              src={image_url}
              className="group-hover:scale-125 transition duration-300 w-full aspect-square object-cover"
              alt={name}
            />
          </div>
          <div className="mt-2">{renderHTML(name)}</div>
          <div>
            <ProductPrice record={record} />
          </div>
        </div>
        {!['variable', 'variable-subscription', 'external'].includes(type) ? (
          <AddToCartButton
            product_id={id}
            quantity={1}
            variation_id="0"
            className="w-full absolute bottom-0"
            disabled={record?.stock_status === 'outofstock'}
            {...addToCartButtonProps}
          />
        ) : (
          <Button
            type="primary"
            className="w-full absolute bottom-0"
            htmlType="button"
            {...moreButtonProps}
          >
            了解詳情
          </Button>
        )}
      </div>
    </>
  )
}
