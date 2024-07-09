import React from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Table, Form, message } from 'antd'
import useColumns from './hooks/useColumns'
import { useRowSelection } from 'antd-toolkit'
import { PopconfirmDelete } from '@/components/general'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

const index = () => {
  const apiUrl = useApiUrl()
  const invalidate = useInvalidate()
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)
  const columns = useColumns()
  const { tableProps } = useTable<TUserRecord>({
    resource: 'students',
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
    queryOptions: {
      enabled: !!watchId,
    },
  })

  // 多選
  const { rowSelection, setSelectedRowKeys, selectedRowKeys } =
    useRowSelection<TUserRecord>({
      onChange: (currentSelectedRowKeys: React.Key[]) => {
        setSelectedRowKeys(currentSelectedRowKeys)
      },
    })

  // remove student mutation
  const { mutate: removeStudent, isLoading } = useCustomMutation()

  const handleRemove = () => {
    removeStudent(
      {
        url: `${apiUrl}/remove-students/${watchId}`,
        method: 'post',
        values: {
          user_ids: selectedRowKeys,
        },
        config: {
          headers: {
            'Content-Type': 'multipart/form-data;',
          },
        },
      },
      {
        onSuccess: () => {
          message.success({
            content: '移除學員成功！',
            key: 'remove-students',
          })
          invalidate({
            resource: 'students',
            invalidates: ['list'],
          })
          setSelectedRowKeys([])
        },
        onError: () => {
          message.error({
            content: '移除學員失敗！',
            key: 'remove-students',
          })
        },
      },
    )
  }

  return (
    <>
      <div className="mb-4">
        <PopconfirmDelete
          type="button"
          popconfirmProps={{
            title: '確認移除這些學員嗎?',
            onConfirm: handleRemove,
          }}
          buttonProps={{
            children: '移除學員',
            disabled: !selectedRowKeys.length,
            loading: isLoading,
          }}
        />
      </div>
      <Table
        {...tableProps}
        rowKey="id"
        columns={columns}
        rowSelection={rowSelection}
        size="small"
      />
    </>
  )
}

export default index
