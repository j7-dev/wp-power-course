import React, { FC } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import AddChapter from '@/components/product/ProductAction/AddChapter'
import ToggleVisibility from './ToggleVisibility'
import { DeleteButton } from '@refinedev/antd'
import { useInvalidate } from '@refinedev/core'

export const ProductAction: FC<{
  record: TCourseRecord
}> = ({ record }) => {
  const isChapter = record?.type === 'chapter'
  const resource = isChapter ? 'chapters' : 'courses'
  const invalidate = useInvalidate()

  return (
    <div className="flex gap-2">
      <AddChapter record={record} />
      <ToggleVisibility record={record} />
      <DeleteButton
        resource={resource}
        recordItemId={record.id}
        onSuccess={() => {
          invalidate({
            resource: 'courses',
            invalidates: ['list'],
          })
        }}
        mutationMode="undoable"
        hideText
        confirmTitle="確定要刪除嗎？"
        confirmOkText="確定"
        confirmCancelText="取消"
        type="link"
        size="small"
        className="mx-0"
      />
    </div>
  )
}
