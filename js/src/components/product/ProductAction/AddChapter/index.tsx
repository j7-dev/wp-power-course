import React from 'react'
import { PlusOutlined } from '@ant-design/icons'
import { Tooltip } from 'antd'

const AddChapter = () => {
  return (
    <>
      <Tooltip title="新增章節">
        <PlusOutlined className="text-gray-400 cursor-pointer" />
      </Tooltip>
    </>
  )
}

export default AddChapter
