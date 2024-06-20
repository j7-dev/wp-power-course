import React from 'react'
import { Form, Switch, Slider, InputNumber, Rate, Tooltip, Select } from 'antd'
import { Heading } from '@/components/general'
import { FiSwitch } from '@/components/formItem'

const { Item } = Form

export const CourseOther = () => {
  const form = Form.useFormInstance()

  const watchIsPopular: boolean = Form.useWatch(['is_popular'], form) === 'yes'
  const watchAvgRating: number = Form.useWatch(['average_rating'], form)
  const watchReviewCount: number = Form.useWatch(['review_count'], form)

  return (
    <>
      <FiSwitch
        formItemProps={{
          name: ['is_popular'],
          label: '這是熱門課程',
        }}
      />

      <Heading>課程評價</Heading>

      <div className="grid 2xl:grid-cols-3 gap-6">
        <Item label="自訂課程評價" name={['average_rating']} initialValue={2.5}>
          <Slider step={0.1} min={0} max={5} />
        </Item>
        <Item label="自訂評價數量" name={['review_count']} initialValue={20}>
          <InputNumber className="w-full" min={0} />
        </Item>
        <div>
          <p className="mb-2">預覽</p>
          <Tooltip title="預覽" className="w-fit mb-12">
            <div className="flex items-center text-gray-800">
              <span className="mr-2 text-2xl font-semibold">
                {Number(watchAvgRating).toFixed(1)}
              </span>
              <Rate disabled value={watchAvgRating} allowHalf />
              <span className="ml-2">({watchReviewCount || 0})</span>
            </div>
          </Tooltip>
        </div>

        <Item
          label="灌水學員人數"
          name={['extra_student_count']}
          tooltip="前台顯示學員人數 = 實際學員人數 + 灌水學員人數"
          initialValue={0}
        >
          <InputNumber
            addonBefore="實際學員人數 + "
            addonAfter="人"
            className="w-full"
            min={0}
          />
        </Item>

        <Item label="開放已購買用戶評價課程" name={['average_rating']}>
          <Switch />
        </Item>
      </div>
    </>
  )
}
