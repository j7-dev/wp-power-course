import React, { useState } from 'react'
import { useTable } from '@refinedev/antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Table, message } from 'antd'
import useColumns from './hooks/useColumns'
import { useRowSelection } from 'antd-toolkit'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'
import { Dayjs } from 'dayjs'
import {
  defaultPaginationProps,
  defaultTableProps,
} from '@/pages/admin/Courses/CourseSelector/utils'
import UserSelector from '../UserSelector'
import { AddTeacherButton } from '@/components/teacher'
import { PopconfirmDelete } from '@/components/general'

const index = () => {
  const apiUrl = useApiUrl()
  const invalidate = useInvalidate()
  const columns = useColumns()
  const { tableProps } = useTable<TUserRecord>({
    resource: 'users',
    filters: {
      permanent: [
        // {
        //   field: 'meta_key',
        //   operator: 'eq',
        //   value: 'is_teacher',
        // },
        // {
        //   field: 'meta_value',
        //   operator: 'eq',
        //   value: 'yes',
        // },
      ],
    },
    pagination: {
      pageSize: 20,
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
        url: `${apiUrl}/courses/remove-students`,
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
        url: `${apiUrl}/courses/update-students/`,
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
      <div className="flex gap-4">
        <AddTeacherButton />
        <div className="flex-1">
          <UserSelector />
        </div>
        <PopconfirmDelete
          type="button"
          popconfirmProps={{
            title: '確認移除這些用戶的講師身分嗎?',
            onConfirm: handleRemove,
          }}
          buttonProps={{
            children: '移除講師身分',
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
