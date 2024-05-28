import React from 'react'
import { Form, Input, Select } from 'antd'
import {
  keyLabelMapper,
  termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { siteUrl } from '@/utils'
import { Uploader } from '@/components/general'

const { Item } = Form

export const CourseDescription = () => {
  const { options, isLoading } = useOptions()
  const { product_cats = [], product_tags = [] } = options

  return (
    <>
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
        <Input addonBefore={`${siteUrl}/product/`} />
      </Item>
      <Item name={['short_description']} label="課程簡介">
        <Input.TextArea rows={8} />
      </Item>
      <Item name={['description']} label="課程重點介紹">
        <Input.TextArea rows={8} />
      </Item>

      <p>課程封面圖</p>
      <Uploader />
      <Item hidden name={['image_id']} label="課程封面圖">
        <Input />
      </Item>
    </>
  )
}
