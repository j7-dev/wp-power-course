import { FC, useEffect } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import {
  ProductPrice,
  ProductStock,
  ProductVariationsSelector,
  useProductVariationsSelector,
} from '@/components/product'
import {
  Gallery,
  ToggleContent,
  useToggleContent,
  QuantityInput,
  useQuantityInput,
} from '@/components/general'
import { AddToCartButton } from '@/components/woocommerce'

import { Modal, ModalProps, Row, Col } from 'antd'
import { ShrinkOutlined, ArrowsAltOutlined } from '@ant-design/icons'

export const ProductModal: FC<{
  record: TCourseRecord
  modal: {
    show: () => void
    close: () => void
    modalProps: ModalProps
  }
}> = ({ record, modal }) => {
  const { close: closeModal, modalProps } = modal
  const { toggleContentProps } = useToggleContent()
  const { isExpand, showReadMore, setIsExpand } = toggleContentProps
  const { quantityInputProps, setQuantityInputProps } = useQuantityInput({
    max: record.stock_quantity || Infinity,
  })
  const { productVariationsSelectorProps } =
    useProductVariationsSelector(record)
  const { selectedVariation, setSelectedAttributes } =
    productVariationsSelectorProps
  const { id, name, images, description, short_description } = record

  const image_urls = images.map((image) => image?.url || defaultImage)

  const handleExpand = () => {
    setIsExpand(!isExpand)
  }

  useEffect(() => {
    if (modalProps?.open) {
      setSelectedAttributes([])
      setQuantityInputProps((prev) => ({ ...prev, value: 1 }))
    }
  }, [modalProps?.open])

  return (
    <>
      <Modal
        zIndex={999999}
        className="lg:w-1/2 lg:max-w-[960px]"
        footer={null}
        {...modalProps}
      >
        <Row
          gutter={24}
          className="max-h-[75vh] overflow-y-auto overflow-x-hidden"
        >
          <Col span={24} lg={{ span: 10 }} className="mb-4 relative">
            <div className="sticky top-0">
              <Gallery images={image_urls} />
            </div>
          </Col>
          <Col span={24} lg={{ span: 14 }}>
            <div className="flex flex-col">
              <div>
                <div className="text-xl mb-4">{renderHTML(name)}</div>
              </div>
              <div className="my-4">
                <ToggleContent
                  content={`${description}${short_description}`}
                  {...toggleContentProps}
                />
              </div>

              <ProductVariationsSelector
                record={record}
                {...productVariationsSelectorProps}
              />
              <ProductStock record={record} />

              {/* <div>
                {product?.type === 'variable' && !!product && (
                  <ProductVariationsSelect product={product} />
                )}
                <StockInfo
                  product={product}
                  selectedVariationId={selectedVariationId}
                />
                <BuyerCount
                  product={product}
                  selectedVariationId={selectedVariationId}
                />
              </div> */}

              <div className="mt-4">
                <ProductPrice
                  record={selectedVariation ? selectedVariation : record}
                />
              </div>

              <div>
                <p className="mb-0 mt-4">數量</p>
                <QuantityInput {...quantityInputProps} />
                <AddToCartButton
                  product_id={id}
                  quantity={quantityInputProps?.value || 1}
                  variation_id={selectedVariation?.id || '0'}
                  className="w-full mt-4"
                  disabled={
                    selectedVariation
                      ? selectedVariation?.stock_status === 'outofstock'
                      : record?.stock_status === 'outofstock'
                  }
                  useAddToCartParams={{
                    onMutate: () => {
                      closeModal()
                    },
                  }}
                />
              </div>
            </div>
          </Col>
        </Row>
        {showReadMore && (
          <div
            className="absolute bottom-24 right-0 md:-right-8 bg-white w-8 flex items-center py-3 cursor-pointer shadow md:shadow-none opacity-50 md:opacity-100 rounded-l-lg md:rounded-l-none md:rounded-r-lg"
            style={{
              writingMode: 'vertical-rl',
            }}
            onClick={handleExpand}
          >
            {isExpand ? (
              <>
                <ShrinkOutlined className="mb-2" />
                收合全部內容
              </>
            ) : (
              <>
                <ArrowsAltOutlined className="mb-2" />
                展開全部內容
              </>
            )}
          </div>
        )}
      </Modal>
    </>
  )
}
