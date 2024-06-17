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
  const watchBundleIds = Form.useWatch(['bundle_ids'], form) || []
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
        <div className="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-x-4">
          {bundleProducts?.map((bundleProduct) => (
            <ProductCheckCard
              key={bundleProduct.id}
              product={bundleProduct}
              show={show}
            />
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
