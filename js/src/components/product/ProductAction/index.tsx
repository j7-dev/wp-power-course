import React, { FC } from 'react'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import AddChapter from '@/components/product/ProductAction/AddChapter'
import ToggleVisibility from './ToggleVisibility'
import { DeleteButton } from '@refinedev/antd'
import { useInvalidate } from '@refinedev/core'
import { ExportOutlined } from '@ant-design/icons'
import { Tooltip } from 'antd'
import { siteUrl } from '@/utils'

export const ProductAction: FC<{
  record: TCourseRecord | TChapterRecord
}> = ({ record }) => {
  const isChapter = record?.type === 'chapter'
  const resource = isChapter ? 'chapters' : 'courses'
  const invalidate = useInvalidate()

  return (
    <div className="flex gap-2">
      <Tooltip title="開啟課程網址">
        <a
          href={`${siteUrl}/courses/${record?.slug}`}
          className="text-gray-400"
          target="_blank"
          rel="noreferrer"
        >
          <ExportOutlined />
        </a>
      </Tooltip>
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
