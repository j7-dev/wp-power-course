import React from 'react'
import { useList } from '@refinedev/core'
import { useSelect } from '@refinedev/antd'
import { Select, Space, Button } from 'antd'

type TUserRecord = any //TYPE
const index = () => {
  // const { data, loading } = useList<TUserRecord>({
  //   resource: 'users',
  //   filters: [
  //     {
  //       field: 's',
  //       operator: 'eq',
  //       value: '',
  //     },

  //     {
  //       field: 'status',
  //       operator: 'eq',
  //       value: 'any',
  //     },
  //     {
  //       field: 'posts_per_page',
  //       operator: 'eq',
  //       value: '100',
  //     },
  //     {
  //       field: 'type',
  //       operator: 'eq',
  //       value: 'power_bundle_product',
  //     },
  //   ],
  //   queryOptions: {
  //     enabled: true,
  //   },
  // })

  const { selectProps } = useSelect<TUserRecord>({
    resource: 'users',
    filters: [
      {
        field: 's',
        operator: 'eq',
        value: '',
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
      enabled: true,
    },
  })

  return (
    <>
      <Space.Compact>
        <Button type="primary">新增學員</Button>
        <Select {...selectProps} className="w-[25rem]" />
      </Space.Compact>
    </>
  )
}

export default index
