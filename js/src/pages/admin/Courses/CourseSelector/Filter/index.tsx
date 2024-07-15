import React, { FC, useState, useEffect } from 'react'
import {
  FormProps,
  Form,
  Input,
  Select,
  DatePicker,
  Button,
  FormInstance,
  Divider,
  Spin,
} from 'antd'
import { BooleanSegmented } from 'antd-toolkit'
import { TFilterProps } from '@/pages/admin/Courses/CourseSelector/types'
import {
  keyLabelMapper,
  defaultBooleanRadioButtonProps,
  termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { backordersOptions, stockStatusOptions, statusOptions } from '@/utils'
import { SearchOutlined, UndoOutlined } from '@ant-design/icons'
import { BsChevronDoubleDown, BsChevronDoubleUp } from 'react-icons/bs'

const { Item } = Form
const { RangePicker } = DatePicker

export const initialFilteredValues = {
  status: [],
  featured: '',
  downloadable: '',
  virtual: '',
  sold_individually: '',
  is_course: '',
}

/**
 * Filter Component for WooCommerce Product Selector
 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
 */

const index: FC<{
  searchFormProps: FormProps
}> = ({ searchFormProps }) => {
  const [isExpand, setIsExpand] = useState(false)
  const form = searchFormProps.form as FormInstance<TFilterProps>
  const handleReset = () => {
    form.resetFields()
    form.submit()
  }

  const { options, isLoading } = useOptions()
  const { product_cats = [], product_tags = [], max_price, min_price } = options

  useEffect(() => {
    if (!isLoading) {
      form.setFieldValue(['price_range'], [min_price, max_price])
    }
  }, [isLoading])

  return (
    <Spin spinning={isLoading}>
      <Form<TFilterProps>
        {...searchFormProps}
        layout="vertical"
        initialValues={initialFilteredValues}
        className="antd-form-sm"
      >
        <div className="grid grid-cols-4 gap-x-4">
          <Item name={['s']} label={keyLabelMapper('s')}>
            <Input size="small" placeholder="模糊搜尋" allowClear />
          </Item>
          <Item name={['sku']} label={keyLabelMapper('sku')}>
            <Input size="small" placeholder="模糊搜尋" allowClear />
          </Item>

          <Item
            name={['product_category_id']}
            label={keyLabelMapper('product_category_id')}
          >
            <Select
              size="small"
              options={termFormatter(product_cats)}
              mode="multiple"
              placeholder="可多選"
              allowClear
            />
          </Item>

          <Item
            name={['product_tag_id']}
            label={keyLabelMapper('product_tag_id')}
          >
            <Select
              size="small"
              options={termFormatter(product_tags)}
              mode="multiple"
              placeholder="可多選"
              allowClear
            />
          </Item>
          {/* <Item
            name={['price_range']}
            label={keyLabelMapper('price_range')}
            initialValue={[min_price, max_price]}
          >
            <Slider range min={min_price} max={max_price} />
          </Item> */}
        </div>
        <Divider plain className="my-2">
          <Button
            type="link"
            onClick={() => {
              setIsExpand(!isExpand)
            }}
          >
            {isExpand ? (
              <>
                隱藏篩選條件 <BsChevronDoubleUp className="text-xs ml-2" />
              </>
            ) : (
              <>
                顯示更多篩選條件{' '}
                <BsChevronDoubleDown className="text-xs ml-2" />
              </>
            )}
          </Button>
        </Divider>
        <div
          className={`grid-cols-4 gap-x-4 ${isExpand ? 'grid' : 'tw-hidden'}`}
        >
          {(
            [
              'featured',
              'is_course',
              'downloadable',
              'virtual',
              'sold_individually',
            ] as (keyof TFilterProps)[]
          ).map((key) => (
            <BooleanSegmented
              {...defaultBooleanRadioButtonProps}
              key={key}
              formItemProps={{
                name: [key],
                label: keyLabelMapper(key),
              }}
            />
          ))}
          <Item name={['status']} label={keyLabelMapper('status')}>
            <Select
              size="small"
              options={statusOptions}
              mode="multiple"
              placeholder="可多選"
              allowClear
            />
          </Item>
          <Item name={['backorders']} label={keyLabelMapper('backorders')}>
            <Select
              size="small"
              options={backordersOptions}
              mode="multiple"
              placeholder="可多選"
              allowClear
            />
          </Item>
          <Item name={['stock_status']} label={keyLabelMapper('stock_status')}>
            <Select
              size="small"
              options={stockStatusOptions}
              mode="multiple"
              placeholder="可多選"
              allowClear
            />
          </Item>
          <Item name={['date_created']} label={keyLabelMapper('date_created')}>
            <RangePicker size="small" className="w-full" />
          </Item>
        </div>
        <div className="grid grid-cols-4 gap-x-4 mt-4">
          <Button
            type="default"
            size="small"
            className="w-full"
            onClick={handleReset}
            icon={<UndoOutlined />}
          >
            重置
          </Button>
          <Button
            htmlType="submit"
            type="primary"
            size="small"
            className="w-full"
            icon={<SearchOutlined />}
          >
            篩選
          </Button>
        </div>
      </Form>
    </Spin>
  )
}

export default index
