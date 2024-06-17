import React, { useEffect } from 'react'
import { Form, Input, Select, Switch } from 'antd'
import {
  keyLabelMapper,
  termFormatter,
} from '@/pages/admin/Courses/CourseSelector/utils'
import useOptions from '@/pages/admin/Courses/CourseSelector/hooks/useOptions'
import { siteUrl } from '@/utils'
import { Upload, useUpload } from '@/components/general'
import { CopyText } from 'antd-toolkit'

const { Item } = Form

export const CourseDescription = () => {
  const form = Form.useFormInstance()
  const { options, isLoading } = useOptions()
  const { product_cats = [], product_tags = [] } = options
  const productUrl = `${siteUrl}/courses/`
  const slug = Form.useWatch(['slug'], form)
  const { uploadProps, fileList } = useUpload()

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
      <Item
        name={['status']}
        label="發佈"
        valuePropName="checked"
        getValueProps={(value) => ({ value: value === 'publish' })}
        normalize={(value) => (value ? 'publish' : 'draft')}
      >
        <Switch checkedChildren="發佈" unCheckedChildren="草稿" />
      </Item>
    </>
  )
}
