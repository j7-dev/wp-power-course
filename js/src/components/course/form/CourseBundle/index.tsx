import React from 'react'
import { Button, Form, Drawer, Input } from 'antd'
import { PlusOutlined, EditOutlined } from '@ant-design/icons'
import { useBundleFormDrawer } from '@/hooks'
import { CheckCard } from '@ant-design/pro-components'
import BundleForm from './BundleForm'
import { useList } from '@refinedev/core'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'

const { Item } = Form

export const CourseBundle = () => {
  const form = Form.useFormInstance()
  const watchBundleIds = Form.useWatch(['bundle_ids'], form) || []
  const courseId: string = Form.useWatch(['id'], form) || ''
  const [bundleProductForm] = Form.useForm()
  const { drawerProps, show } = useBundleFormDrawer({
    form: bundleProductForm,
    resource: 'bundle_products',
  })

  const { data, isLoading } = useList<TProductRecord>({
    resource: 'products',
    filters: [
      {
        field: 'include',
        operator: 'eq',
        value: watchBundleIds,
      },

      {
        field: 'status',
        operator: 'eq',
        value: 'any',
      },
      {
        field: 'posts_per_page',
        operator: 'eq',
        value: '100',
      },
      {
        field: 'type',
        operator: 'eq',
        value: 'power_bundle_product',
      },
    ],
    queryOptions: {
      enabled: !!watchBundleIds.length,
    },
  })

  const bundleProducts = data?.data || []
  console.log('⭐  bundleProducts:', bundleProducts)

  return (
    <>
      <Button type="primary" icon={<PlusOutlined />} onClick={show()}>
        新增銷售方案
      </Button>
      {!!watchBundleIds.length && (
        <div className="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-x-4">
          {bundleProducts?.map((bundleProduct) => {
            const {
              id,
              name,
              regular_price,
              sale_price,
              sale_date_range,
              images,
              description,
              status,
              price_html,
            } = bundleProduct
            const imgUrl = images?.[0]?.url || defaultImage
            return (
              <div key={id}>
                <CheckCard
                  className="w-full"
                  avatar={
                    <div className="group aspect-video w-20 rounded overflow-hidden">
                      <img
                        className="group-hover:scale-125 transition duration-300 ease-in-out object-cover w-full h-full"
                        src={imgUrl}
                      />
                    </div>
                  }
                  title={
                    <div>
                      {renderHTML(name)}
                      <p className="m-0 text-[0.675rem] text-gray-500">
                        ID: {id}
                      </p>
                    </div>
                  }
                  description={
                    <div className="flex justify-between">
                      <div className="whitespace-nowrap">
                        {renderHTML(price_html)}
                      </div>
                      <div>
                        <Button
                          type="link"
                          className="m-0 p-0"
                          onClick={(e) => {
                            e.stopPropagation()
                            return show(bundleProduct)()
                          }}
                        >
                          編輯 <EditOutlined />
                        </Button>
                      </div>
                    </div>
                  }
                  onChange={(checked) => {
                    console.log('CARD checked', checked)
                  }}

                  // checked={status === 'publish'}
                />
              </div>
            )
          })}
        </div>
      )}

      <Item name={['bundle_ids']} hidden>
        <Input />
      </Item>

      <Drawer {...drawerProps}>
        <BundleForm courseId={courseId} form={bundleProductForm} />
      </Drawer>
    </>
  )
}
