import React from 'react'
import { TableProps, Tooltip } from 'antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { UserName, UserWatchLimit, UserWatchStatus } from '@/components/user'
import Remove from '../Remove'

const useColumns = () => {
  const columns: TableProps<TUserRecord>['columns'] = [
    {
      title: '學員',
      dataIndex: 'id',
      width: 180,
      render: (_, record) => <UserName record={record} />,
    },
    {
      title: '狀態',
      dataIndex: 'avl_courses',
      key: 'avl_courses_status',
      width: 100,
      render: (_, record) => <UserWatchStatus record={record} />,
    },
    {
      title: (
        <Tooltip title="學員可以看的期限，如果空白代表無期限">觀看權限</Tooltip>
      ),
      dataIndex: 'avl_courses',
      key: 'avl_courses',
      width: 180,
      render: (_, record) => <UserWatchLimit record={record} />,
    },
  ]

  return columns
}

export default useColumns
