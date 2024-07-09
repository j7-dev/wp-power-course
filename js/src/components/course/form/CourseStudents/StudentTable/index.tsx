import React from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Table, Form } from 'antd'
import useColumns from './hooks/useColumns'

const index = () => {
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)
  const columns = useColumns()
  const { tableProps } = useTable<TUserRecord>({
    resource: 'users',
    filters: {
      permanent: [
        {
          field: 'meta_key',
          operator: 'eq',
          value: 'avl_course_ids',
        },
        {
          field: 'meta_value',
          operator: 'eq',
          value: watchId,
        },
      ],
    },
    pagination: {
      pageSize: 20,
    },
  })

  return <Table {...tableProps} rowKey="id" columns={columns} />
}

export default index
