import React, { useEffect } from 'react'
import {
  DatePicker,
  Form,
  Input,
  InputNumber,
  Select,
  Switch,
  Space,
  Radio,
} from 'antd'
import {
  keyLabelMapper,
  termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { siteUrl } from '@/utils'
import { Upload, useUpload, Heading } from '@/components/general'
import { FiSwitch } from '@/components/formItem'
import { CopyText } from 'antd-toolkit'
import dayjs from 'dayjs'

const { Item } = Form

const selectAfter = (
  <Select
    options={[
      { label: '日', value: 'day' },
      { label: '月', value: 'month' },
      { label: '年', value: 'year' },
    ]}
    defaultValue="day"
    style={{ width: 60 }}
  />
)

export const CourseDescription = () => {
  const form = Form.useFormInstance()
  const { options, isLoading } = useOptions()
  const { product_cats = [], product_tags = [] } = options
  const productUrl = `${siteUrl}/courses/`
  const slug = Form.useWatch(['slug'], form)
  const { uploadProps, fileList } = useUpload()
  const watchLimitType: string = Form.useWatch(['limit_type'], form)

  useEffect(() => {
    form.setFieldValue(['files'], fileList)
  }, [fileList])

  return (
    <>
      <Item name={['id']} hidden normalize={() => undefined}>
        <Input />
      </Item>
      <Item name={['name']} label="課程名稱">
        <Input />
      </Item>
      <Item name={['sub_title']} label="課程副標題">
        <Input disabled />
      </Item>
      <Item
        name={['category_ids']}
        label={keyLabelMapper('product_category_id')}
      >
        <Select
          options={termFormatter(product_cats)}
          mode="multiple"
          placeholder="可多選"
          allowClear
        />
      </Item>
      <Item name={['product_tag_id']} label={keyLabelMapper('product_tag_id')}>
        <Select
          options={termFormatter(product_tags)}
          mode="multiple"
          placeholder="可多選"
          allowClear
          disabled
        />
      </Item>
      <Item name={['slug']} label="銷售網址">
        <Input
          addonBefore={productUrl}
          addonAfter={<CopyText text={`${productUrl}${slug}`} />}
        />
      </Item>
      <Item name={['short_description']} label="課程簡介">
        <Input.TextArea rows={8} />
      </Item>
      <Item name={['description']} label="課程重點介紹">
        <Input.TextArea rows={8} />
      </Item>

      <p className="mb-3">課程封面圖</p>
      <div className="mb-8">
        <Upload uploadProps={uploadProps} />
        <Item hidden name={['files']} label="課程封面圖">
          <Input />
        </Item>
      </div>

      <FiSwitch
        formItemProps={{
          name: ['status'],
          label: '發佈',
          getValueProps: (value) => ({ value: value === 'publish' }),
          normalize: (value) => (value ? 'publish' : 'draft'),
        }}
        switchProps={{
          checkedChildren: '發佈',
          unCheckedChildren: '草稿',
        }}
      />

      <div className="min-h-[12rem]">
        <Heading>課程資訊</Heading>

        <div className="grid 2xl:grid-cols-3 gap-6">
          <Item name={['course_schedule']} label="開課時間" className="mb-0">
            <DatePicker
              className="w-full"
              format="YYYY-MM-DD HH:mm"
              showTime={{ defaultValue: dayjs() }}
            />
          </Item>

          <div>
            <p className="mb-2">預計時長</p>
            <Space.Compact>
              <Item name={['course_hour']} noStyle>
                <InputNumber className="w-1/2" min={0} addonAfter="時" />
              </Item>
              <Item name={['course_minute']} noStyle>
                <InputNumber className="w-1/2" min={0} addonAfter="分" />
              </Item>
            </Space.Compact>
          </div>

          <div>
            <Item
              label="觀看期限"
              name={['limit_type']}
              initialValue={'unlimited'}
            >
              <Radio.Group
                className="w-full w-avg"
                options={[
                  { label: '無期限', value: 'unlimited' },
                  { label: '固定天數', value: 'fixed' },
                  { label: '指定到期日', value: 'assigned' },
                ]}
                optionType="button"
                buttonStyle="solid"
              />
            </Item>
            {'fixed' === watchLimitType && (
              <div>
                <InputNumber
                  className="w-full"
                  min={1}
                  addonAfter={selectAfter}
                />
              </div>
            )}

            {'assigned' === watchLimitType && (
              <div>
                <DatePicker className="w-full" />
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  )
}
