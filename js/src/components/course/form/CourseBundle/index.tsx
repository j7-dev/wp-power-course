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
import { SaleRange } from '@/components/general'
import ProductCheckCard from './ProductCheckCard'

const { Item } = Form

export const CourseBundle = () => {
  const form = Form.useFormInstance()
  const watchBundleIds: string[] = Form.useWatch(['bundle_ids'], form) || []
  const [bundleProductForm] = Form.useForm()
  const { drawerProps, show } = useBundleFormDrawer({
    form: bundleProductForm,
    resource: 'bundle_products',
  })

  const { data, isFetching } = useList<TProductRecord>({
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

  return (
    <>
      <Button type="primary" icon={<PlusOutlined />} onClick={show()}>
        新增銷售方案
      </Button>
      {!isFetching && !!watchBundleIds.length && (
        <div className="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-x-4">
          {bundleProducts?.map((bundleProduct) => (
            <ProductCheckCard
              key={bundleProduct.id}
              product={bundleProduct}
              show={show}
            />
          ))}
          {isFetching &&
            watchBundleIds.map((id) => (
              <div
                key={id}
                className="p-4 border border-solid border-gray-200 rounded-md animate-pulse"
              >
                <div className="aspect-video w-full rounded bg-slate-300 mb-2" />
                <div className="mb-2 h-3 bg-slate-300 w-3/4" />
                <div className="mb-2 h-2 bg-slate-300 w-12" />
                <div className="mb-2 h-3 bg-slate-300 w-1/2" />
                <div className="mb-2 h-3 bg-slate-300 w-full" />
              </div>
            ))}
        </div>
      )}

      <Item name={['bundle_ids']} hidden>
        <Input />
      </Item>

      <Drawer {...drawerProps}>
        <BundleForm form={bundleProductForm} />
      </Drawer>
    </>
  )
}
