/* eslint-disable lines-around-comment */
import { useRef, useState } from 'react'
import { Input, Button, Collapse, CollapseProps } from 'antd'
import { SortableList, SortableListRef } from '@ant-design/pro-editor'
import { HolderOutlined, DeleteOutlined } from '@ant-design/icons'
import { nanoid } from 'nanoid'

type TCollapseItem = NonNullable<CollapseProps['items']>[number]
type TListItem = {
  key: string
  question: string
  answer: string
}

export const CourseQA = () => {
  const ref = useRef<SortableListRef>(null)

  const [list, setList] = useState<TListItem[]>([
    {
      key: 'defaultQA',
      question: '',
      answer: '',
    },
  ])

  return (
    <div className="gap-6 p-6">
      <Button
        onClick={() => {
          ref.current!.addItem(
            {
              key: nanoid(),
              question: '',
              answer: '',
            },
            0,
          )
        }}
      >
        新增
      </Button>

      <SortableList<TListItem>
        value={list}
        ref={ref}
        onChange={setList}
        getItemStyles={() => ({ padding: '16px' })}
        renderItem={(item: TListItem, { index, listeners }) => {
          const collapseItem: TCollapseItem = {
            key: item.key,
            label: (
              <Input placeholder="請輸入問題" defaultValue={item.question} />
            ),
            children: (
              <Input.TextArea
                placeholder="請輸入答案"
                defaultValue={item.answer}
                rows={5}
              />
            ),
            showArrow: false,
          }

          return (
            <div className="w-full flex justify-between gap-3">
              <div className="flex gap-2 items-start w-full">
                <HolderOutlined
                  className="cursor-grab hover:bg-gray-200 rounded-lg mt-4"
                  {...listeners}
                />
                <Collapse ghost className="w-full" items={[collapseItem]} />

                <DeleteOutlined
                  // 由于拖拽事件是通过监听 onMouseDown 来识别用户动作
                  // 因此针对相关用户操作，需要终止 onMouseDown 的冒泡行为

                  onMouseDown={(e) => {
                    e.stopPropagation()
                  }}
                  className="text-red-500 cursor-pointer mt-4"
                  onClick={() => ref.current!.removeItem(index as number)}
                />
              </div>
              <div></div>
            </div>
          )
        }}
      />
    </div>
  )
}
