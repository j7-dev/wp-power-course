import React, { FC } from 'react'
import {
  EyeOutlined,
  EyeInvisibleOutlined,
  LoadingOutlined,
} from '@ant-design/icons'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { Tooltip } from 'antd'
import { useUpdate } from '@refinedev/core'
import { toFormData } from '@/utils'

const ToggleVisibility: FC<{
  record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
  const { catalog_visibility = 'visible', id } = record
  const isVisible = catalog_visibility !== 'hidden'

  const { mutate: update, isLoading } = useUpdate()

  const handleToggle = () => {
    const formData = toFormData({
      catalog_visibility: isVisible ? 'hidden' : 'visible',
    })
    update({
      resource: 'courses',
      values: formData,
      id,
      meta: {
        headers: { 'Content-Type': 'multipart/form-data;' },
      },
    })
  }

  if (isLoading) {
    return <LoadingOutlined className="text-gray-400 cursor-pointer" />
  }

  return (
    <Tooltip
      title={`調整商品型錄可見度隱藏，目前為${isVisible ? '可見' : '隱藏'}`}
    >
      {isVisible ? (
        <EyeOutlined
          className="text-gray-400 cursor-pointer"
          onClick={handleToggle}
        />
      ) : (
        <EyeInvisibleOutlined
          className="text-yellow-700 cursor-pointer"
          onClick={handleToggle}
        />
      )}
    </Tooltip>
  )
}

export default ToggleVisibility
