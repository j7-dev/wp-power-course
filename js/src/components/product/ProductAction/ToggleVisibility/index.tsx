import React, { FC, useState } from 'react'
import { EyeOutlined, EyeInvisibleOutlined } from '@ant-design/icons'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { Tooltip } from 'antd'

// TODO 還沒接API

const ToggleVisibility: FC<{
  record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
  const [isVisible, setIsVisible] = useState(true)

  const handleToggle = () => {
    setIsVisible(!isVisible)
  }

  return (
    <Tooltip title="調整可見度">
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
