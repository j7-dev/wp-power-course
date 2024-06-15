import React, { useEffect, useState, FC } from 'react'
import {
  Form,
  InputNumber,
  DatePicker,
  Select,
  Input,
  SelectProps,
  FormInstance,
  Switch,
} from 'antd'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import dayjs from 'dayjs'
import { useSelect } from '@refinedev/antd'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { useList } from '@refinedev/core'
import { Upload, useUpload, PopconfirmDelete } from '@/components/general'

// TODO 搜尋應該要排除 bundle 商品
// TODO 如何結合可變商品?

dayjs.extend(customParseFormat)

const { RangePicker } = DatePicker

const { Item } = Form

const OPTIONS = [
  { label: '合購優惠', value: 'bundle' },
  { label: '團購優惠', value: 'groupbuy', disabled: true },
]

const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids

const CourseBundle: FC<{
  courseId: string
  form: FormInstance
}> = ({ courseId, form: bundleProductForm }) => {
  const watchRegularPrice = Form.useWatch(['regular_price'], bundleProductForm)
  const [enabled, setEnabled] = useState(false)
  const { uploadProps, fileList } = useUpload()

  const { selectProps, queryResult } = useSelect<TProductRecord>({
    resource: 'products',
    debounce: 1200,
    onSearch: (value) => {
      setEnabled(value?.length > 1)

      return [
        {
          field: 's',
          operator: 'eq',
          value,
        },
        {
          field: 'exclude',
          operator: 'eq',
          value: [courseId],
        },
        {
          field: 'posts_per_page',
          operator: 'eq',
          value: 20,
        },
      ]
    },
    queryOptions: {
      enabled,
    },
  })

  const watchIncludedProductIds: string[] = Form.useWatch(
    [INCLUDED_PRODUCT_IDS_FIELD_NAME],
    bundleProductForm,
  )

  // 從選擇的商品ID中取得商品資訊

  const includedProductsResult = useList<TProductRecord>({
    resource: 'products',
    filters: [
      {
        field: 'include',
        operator: 'eq',
        value: watchIncludedProductIds,
      },
    ],
    queryOptions: {
      enabled: !!watchIncludedProductIds?.length,
    },
  })

  const includedProducts = includedProductsResult?.data?.data || []

  const queryResultProducts = queryResult?.data?.data || []
  const formattedOptions: SelectProps['options'] = queryResultProducts.map(
    (product) => ({
      label: product.name,
      value: product.id,
      regular_price: product.regular_price,
      sale_price: product.sale_price,
      image: product.images?.[0]?.url,
      price_html: product.price_html,
    }),
  )

  useEffect(() => {
    if (includedProductsResult.isFetching) {
      return
    }

    if (watchIncludedProductIds?.length) {
      bundleProductForm.setFieldValue(
        ['regular_price'],
        includedProducts?.reduce(
          (acc, product) => acc + Number(product.regular_price),
          0,
        ),
      )
    }

    if (!watchIncludedProductIds?.length) {
      bundleProductForm.setFieldValue(['regular_price'], 0)
    }
  }, [
    includedProductsResult.isFetching,
    includedProducts?.length,
    watchIncludedProductIds?.length,
  ])

  useEffect(() => {
    bundleProductForm.setFieldValue(['files'], fileList)
  }, [fileList])

  return (
    <Form form={bundleProductForm} layout="vertical">
      <Item
        name={['bundle_type']}
        label="銷售方案種類"
        initialValue={OPTIONS[0].value}
      >
        <Select options={OPTIONS} />
      </Item>
      <Item name={['name']} label="銷售方案名稱">
        <Input />
      </Item>
      <Item name={['description']} label="銷售方案說明">
        <Input.TextArea rows={8} />
      </Item>

      <Item
        name={[INCLUDED_PRODUCT_IDS_FIELD_NAME]}
        label="請選擇要加入的商品"
        tooltip="請輸入 2 個字以上才會搜尋，每次最多返回 20 筆資料"
      >
        <Select
          {...selectProps}
          mode="multiple"
          optionRender={(option) => (
            <div className="flex items-center gap-4">
              <img
                src={option?.data?.image || defaultImage}
                className="h-9 w-16 rounded object-cover"
              />
              <div>
                {option?.data?.label} #{option?.data?.value}{' '}
                {renderHTML(option?.data?.price_html)}
              </div>
            </div>
          )}
          options={formattedOptions}
          loading={queryResult.isFetching}
        />
      </Item>

      {includedProducts?.map(({ id, images, name, price_html }) => (
        <div
          key={id}
          className="flex items-center justify-between gap-4 border border-dash border-gray-200 p-2 rounded-md mb-2"
        >
          <img
            src={images?.[0]?.url || defaultImage}
            className="h-9 w-16 rounded object-cover"
          />
          <div className="w-full">
            {name} #{id} {renderHTML(price_html)}
          </div>
          <div className="w-8 text-right">
            <PopconfirmDelete
              popconfirmProps={{
                onConfirm: () => {
                  bundleProductForm.setFieldValue(
                    [INCLUDED_PRODUCT_IDS_FIELD_NAME],
                    watchIncludedProductIds?.filter(
                      (productId) => productId !== id,
                    ),
                  )
                },
              }}
            />
          </div>
        </div>
      ))}

      <Item name={['regular_price']} label="此銷售組合原價" hidden>
        <InputNumber
          addonBefore="NT$"
          className="w-full [&_input]:text-right [&_.ant-input-number]:bg-white [&_.ant-input-number-group-addon]:bg-[#fafafa]  [&_.ant-input-number-group-addon]:text-[#1f1f1f]"
          min={0}
          disabled
        />
      </Item>
      <Item
        name={['sale_price']}
        label="方案折扣價"
        tooltip="折扣價不能超過原價"
        rules={[
          {
            type: 'number',
            max: watchRegularPrice,
            message: '折扣價不能超過原價',
          },
        ]}
        help={
          <>
            <div className="grid grid-cols-2 gap-x-4 mb-4">
              <div>此銷售組合原訂原價</div>
              <div className="text-right pr-0">
                {getPrice({
                  type: 'regular_price',
                  isFetching: includedProductsResult.isFetching,
                  products: includedProducts,
                  watchIncludedProductLength: !!watchIncludedProductIds?.length,
                })}
              </div>
              <div>此銷售組合原訂折扣價</div>
              <div className="text-right pr-0">
                {getPrice({
                  type: 'sale_price',
                  isFetching: includedProductsResult.isFetching,
                  products: includedProducts,
                  watchIncludedProductLength: !!watchIncludedProductIds?.length,
                })}
              </div>
            </div>
          </>
        }
      >
        <InputNumber
          addonBefore="NT$"
          className="w-full [&_input]:text-right"
          min={0}
          controls={false}
        />
      </Item>

      <Item name={['sale_date_range']} label="銷售期間">
        <RangePicker
          className="w-full"
          showTime={{
            defaultValue: [
              dayjs('00:00', 'HH:mm'),
              dayjs('11:59', 'HH:mm'),
            ],
          }}
          allowEmpty={[true, true]}
          format="YYYY-MM-DD HH:mm"
        />
      </Item>

      <p className="mb-3">課程封面圖</p>
      <div className="mb-8">
        <Upload uploadProps={uploadProps} />
        <Item hidden name={['files']} label="課程封面圖">
          <Input />
        </Item>
      </div>
      <Item name={['status']} label="發佈" valuePropName="checked">
        <Switch checkedChildren="發佈" unCheckedChildren="草稿" />
      </Item>
    </Form>
  )
}

function getPrice({
  type,
  isFetching,
  products,
  watchIncludedProductLength,
}: {
  type: 'regular_price' | 'sale_price'
  isFetching: boolean
  products: TProductRecord[] | undefined
  watchIncludedProductLength: boolean
}) {
  if (isFetching) {
    return <span className="px-4 animate-pulse bg-gray-200">loading...</span>
  }

  if (watchIncludedProductLength) {
    const total = products?.reduce(
      (acc, product) => acc + Number(product?.[type] || product.regular_price),
      0,
    )
    return `NT$ ${total?.toLocaleString()}`
  }

  if (!watchIncludedProductLength) {
    return 0
  }
}

export default CourseBundle
