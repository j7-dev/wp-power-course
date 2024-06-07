import React, { FC, useState, useEffect } from 'react'
import { SortableTree, TreeData } from '@ant-design/pro-editor'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { message, Button } from 'antd'
import NodeRender from './NodeRender'
import { nanoid } from 'nanoid'
import { chapterToTreeNode, treeToParams } from './utils'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

export const SortableChapter: FC<{
  record: TCourseRecord | TChapterRecord
  show: {
    showCourseDrawer: (_record: TCourseRecord | undefined) => () => void
    showChapterDrawer: (_record: TChapterRecord | undefined) => () => void
  }
}> = ({ record, show }) => {
  const [treeData, setTreeData] = useState<TreeData<TChapterRecord>>([])
  const [originTree, setOriginTree] = useState<TreeData<TChapterRecord>>([])
  const invalidate = useInvalidate()

  const apiUrl = useApiUrl()
  const { mutate, isLoading } = useCustomMutation()

  useEffect(() => {
    if (!!record?.children) {
      const chapterTree = record?.children?.map(chapterToTreeNode)
      setTreeData(chapterTree)
      setOriginTree(chapterTree)
    }
  }, [record])

  const handleAdd = () => {
    const newChapter: TChapterRecord = {
      id: `new-${nanoid(5)}`, // 不會存入DB，真正的ID是後端產生的
      name: '新章節',
      status: 'draft',
      type: 'chapter',
      depth: 0,
      parent_id: record.id,
    }
    setTreeData((prev) => [...prev, chapterToTreeNode(newChapter)])
  }

  const handleSave = () => {
    // 這個儲存只存新增，不存章節的細部資料

    const from_tree = treeToParams(originTree)
    const to_tree = treeToParams(treeData)

    mutate(
      {
        url: `${apiUrl}/chapters/sort`,
        method: 'post',
        values: {
          from_tree,
          to_tree,
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

  return (
    <>
      <div className="pl-[4.5rem]">
        <SortableTree
          hideAdd
          treeData={treeData}
          onTreeDataChange={(data: TreeData<TChapterRecord>) => {
            setTreeData(data)
          }}
          renderContent={(node) => {
            const theRecord = node.content
            return theRecord && <NodeRender record={theRecord} show={show} />
          }}
          indentationWidth={48}
          sortableRule={({ activeNode, projected }) => {
            const activeNodeHasChild = !!activeNode.children.length
            const sortable = projected?.depth <= (activeNodeHasChild ? 0 : 1)
            if (!sortable) message.error('超過最大深度，無法執行')
            return sortable
          }}
        />
      </div>
      <div className="flex gap-1">
        <Button block onClick={handleAdd} disabled={isLoading}>
          新增
        </Button>
        <Button type="primary" block onClick={handleSave} loading={isLoading}>
          儲存
        </Button>
      </div>
    </>
  )
}
