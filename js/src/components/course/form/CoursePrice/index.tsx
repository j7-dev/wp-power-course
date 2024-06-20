import React, { useEffect } from 'react'
import { Form, InputNumber } from 'antd'
import { FiSwitch } from '@/components/formItem'

const { Item } = Form

export const CoursePrice = () => {
  const form = Form.useFormInstance()
  const watchRegularPrice = Form.useWatch(['regular_price'], form)
  const watchIsFree = Form.useWatch(['is_free'], form) === 'yes'

  useEffect(() => {
    if (watchIsFree) {
      form.setFieldsValue({ regular_price: 0, sale_price: 0 })
    }
  }, [watchIsFree])
  return (
    <>
      <Item name={['regular_price']} label="原價">
        <InputNumber className="w-full" min={0} disabled={watchIsFree} />
      </Item>
      <Item
        name={['sale_price']}
        label="折扣價"
        rules={[
          {
            type: 'number',
            max: watchRegularPrice,
            message: '折扣價不能超過原價',
          },
        ]}
      >
        <InputNumber className="w-full" min={0} disabled={watchIsFree} />
      </Item>

      <FiSwitch
        formItemProps={{
          name: ['is_free'],
          label: '這是免費課程',
        }}
      />
    </>
  )
}
