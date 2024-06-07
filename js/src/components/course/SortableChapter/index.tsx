import React, { FC, useState, useEffect } from 'react'
import {
  SortableTree,
  TreeData,
  TreeNode,
  useSortableTree,
} from '@ant-design/pro-editor'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { message, Button } from 'antd'
import NodeRender from './NodeRender'
import { nanoid } from 'nanoid'

export const SortableChapter: FC<{
  record: TCourseRecord | TChapterRecord
  show: {
    showCourseDrawer: (_record: TCourseRecord | undefined) => () => void
    showChapterDrawer: (_record: TChapterRecord | undefined) => () => void
  }
}> = ({ record, show }) => {
  const [treeData, setTreeData] = useState<TreeData<TChapterRecord>>([])

  useEffect(() => {
    if (!!record?.children) {
      const chapterTree = record?.children?.map(chapterToTreeNode)
      setTreeData(chapterTree)
    }
  }, [record?.id])

  const handleAdd = () => {
    const newChapter: TChapterRecord = {
      id: `new-${nanoid(5)}`, // 不會存入DB，真正的ID是後端產生的
      name: '新章節',
      status: 'draft',
      type: 'chapter',
      depth: 0,
    }
    setTreeData((prev) => [...prev, chapterToTreeNode(newChapter)])
  }

  const handleSave = () => {
    // 這個儲存只存新增，不存章節的細部資料

    const params = treeToParams(treeData)
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
        <Button block onClick={handleAdd}>
          新增
        </Button>
        <Button type="primary" block onClick={handleSave}>
          儲存排序
        </Button>
      </div>
    </>
  )
}

/**
 * 將章節 TChapterRecord 傳換成 TreeNode<TChapterRecord>
 *
 * @param {TChapterRecord} chapter
 * @return {TreeNode<TChapterRecord>}
 */

function chapterToTreeNode(chapter: TChapterRecord): TreeNode<TChapterRecord> {
  const { id, children, ...rest } = chapter
  return {
    id,
    content: {
      id,
      ...rest,
    },
    children: children?.map(chapterToTreeNode) || [],
    showExtra: false,
    collapsed: false,
  }
}

/**
 * 將 TreeData<TChapterRecord> 轉換成 Create API 傳送的參數
 * 只抓出順序、parent_id、id
 *
 * @param {TreeData<TChapterRecord>} treeData
 * @return {*}
 */

function treeToParams(treeData: TreeData<TChapterRecord>): any {
  console.log('treeData', treeData)
  const depth0 = treeData.map((node, index) => {
    return {
      id: node.id,
      depth: 0,
      menu_order: index,
    }
  })
  const depth1 = treeData
    .map((parentNode) => {
      const nodes = parentNode.children.map((node, index) => {
        return {
          id: node.id,
          depth: 1,
          menu_order: index,
          parent_id: parentNode.id,
        }
      })
      return nodes
    })
    .flat()
  console.log('depth1', depth1)

  return [...depth0, ...depth1]
}
