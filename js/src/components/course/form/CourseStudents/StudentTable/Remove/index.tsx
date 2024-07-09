import { FC } from 'react'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { DeleteOutlined } from '@ant-design/icons'
import { Tooltip } from 'antd'
import { PopconfirmDelete } from '@/components/general'

const index: FC<{
  record: TUserRecord
}> = () => {
  return (
    <>
      <Tooltip title="移除學員">
        <PopconfirmDelete
          popconfirmProps={{
            className: 'text-red-500',
            title: '確認移除學員嗎?',
          }}
        />
      </Tooltip>
    </>
  )
}

export default index
