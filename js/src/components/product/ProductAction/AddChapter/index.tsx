import { FC } from 'react'
import { PlusOutlined } from '@ant-design/icons'
import { Tooltip, Button } from 'antd'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { useCreate, useInvalidate } from '@refinedev/core'

const AddChapter: FC<{
  record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
  const { mutate, isLoading } = useCreate()
  const invalidate = useInvalidate()
  const { type, depth } = record
  const isChapter = type === 'chapter'
  const itemLabel = isChapter ? '段落' : '章節'

  const handleCreate = () => {
    mutate(
      {
        resource: 'chapters',
        values: {
          post_parent: record.id,
          post_title: `新${itemLabel}`,
        },
      },
      {
        onSuccess: () => {
          invalidate({
            resource: 'courses',
            invalidates: ['list'],
          })
        },
      },
    )
  }

  if (depth >= 1) return null

  return (
    <>
      <Tooltip title={`新增${itemLabel}`}>
        <Button
          loading={isLoading}
          type="link"
          size="small"
          className="mx-0"
          icon={<PlusOutlined className="text-gray-400 cursor-pointer" />}
          onClick={handleCreate}
        />
      </Tooltip>
    </>
  )
}

export default AddChapter
