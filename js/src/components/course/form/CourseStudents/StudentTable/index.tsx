import React, { useState } from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Table, Form, message, DatePicker, Space, Button } from 'antd'
import useColumns from './hooks/useColumns'
import { useRowSelection } from 'antd-toolkit'
import { PopconfirmDelete } from '@/components/general'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import { Dayjs } from 'dayjs'
import {
  defaultPaginationProps,
  defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'

const index = () => {
  const apiUrl = useApiUrl()
  const invalidate = useInvalidate()
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)
  const columns = useColumns()
  const { tableProps } = useTable<TUserRecord>({
    resource: 'users/students',
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
  const { mutate, isLoading } = useCustomMutation()

  const handleRemove = () => {
    mutate(
      {
        url: `${apiUrl}/courses/${watchId}/remove-students`,
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
            resource: 'users/students',
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

  // update student mutation
  const [time, setTime] = useState<Dayjs | undefined>(undefined)
  const handleUpdate = (timestamp?: number) => () => {
    mutate(
      {
        url: `${apiUrl}/courses/${watchId}/update-students`,
        method: 'post',
        values: {
          user_ids: selectedRowKeys,
          timestamp: timestamp ?? time?.unix(),
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
            content: '批量修改觀看期限成功！',
            key: 'update-students',
          })
          invalidate({
            resource: 'users/students',
            invalidates: ['list'],
          })
          setSelectedRowKeys([])
          setTime(undefined)
        },
        onError: () => {
          message.error({
            content: '批量修改觀看期限失敗！',
            key: 'update-students',
          })
        },
      },
    )
  }

  return (
    <>
      <div className="mb-4 flex justify-between gap-4">
        <div className="flex gap-4">
          <Button
            type="primary"
            disabled={!selectedRowKeys.length}
            onClick={handleUpdate(0)}
          >
            修改為無期限
          </Button>

          <Space.Compact>
            <DatePicker
              value={time}
              showTime
              format="YYYY-MM-DD HH:mm"
              onChange={(value: Dayjs) => {
                setTime(value)
              }}
            />
            <Button
              type="primary"
              disabled={!selectedRowKeys.length}
              onClick={handleUpdate()}
              ghost
            >
              修改觀看期限
            </Button>
          </Space.Compact>
        </div>

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
        {...defaultTableProps}
        {...tableProps}
        columns={columns}
        rowSelection={rowSelection}
        pagination={{
          ...tableProps.pagination,
          ...defaultPaginationProps,
        }}
      />
    </>
  )
}

export default index
