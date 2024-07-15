import React, { useEffect } from 'react'
import { Form, InputNumber, DatePicker } from 'antd'
import { FiSwitch } from '@/components/formItem'
import dayjs, { Dayjs } from 'dayjs'

const { Item } = Form

export const CoursePrice = () => {
  const form = Form.useFormInstance()
  const watchRegularPrice = Form.useWatch(['regular_price'], form)
  const watchIsFree = Form.useWatch(['is_free'], form) === 'yes'

  useEffect(() => {
    if (watchIsFree) {
      form.setFieldsValue({
        regular_price: 0,
        sale_price: 0,
        date_on_sale_from: undefined,
        date_on_sale_to: undefined,
      })
    }
  }, [watchIsFree])
  return (
    <div className="grid grid-cols-2 gap-4">
      <Item
        name={['regular_price']}
        label="原價"
        rules={[
          {
            required: true,
            message: '請輸入原價',
          },
        ]}
      >
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

      <Item
        name={['date_on_sale_from']}
        label="折扣價開始時間"
        normalize={(value) => value?.unix()}
        getValueProps={(value) => {
          return {
            value: value ? dayjs.unix(value) : undefined,
          }
        }}
      >
        <DatePicker
          className="w-full"
          showTime={{ defaultValue: dayjs() }}
          disabled={watchIsFree}
        />
      </Item>

      <Item
        name={['date_on_sale_to']}
        label="折扣價結束時間"
        normalize={(value) => value?.unix()}
        getValueProps={(value) => {
          return {
            value: value ? dayjs.unix(value) : undefined,
          }
        }}
      >
        <DatePicker
          className="w-full"
          showTime={{ defaultValue: dayjs() }}
          disabled={watchIsFree}
        />
      </Item>

      <FiSwitch
        formItemProps={{
          name: ['is_free'],
          label: '這是免費課程',
        }}
      />
    </div>
  )
}
