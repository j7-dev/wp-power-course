/* eslint-disable lines-around-comment */
import React, { useRef, useEffect, memo } from 'react'
import { Input, Button, Collapse, CollapseProps, Form, Empty } from 'antd'
import { SortableList, SortableListRef } from '@ant-design/pro-editor'
import { HolderOutlined, DeleteOutlined } from '@ant-design/icons'
import { nanoid } from 'nanoid'

type TCollapseItem = NonNullable<CollapseProps['items']>[number]
type TListItem = {
	key: string
	question: string
	answer: string
}

const { Item } = Form

const CourseQAComponent = () => {
	const ref = useRef<SortableListRef>(null)
	const form = Form.useFormInstance()

	const watchQAList = (Form.useWatch(['qa_list'], form) || []) as TListItem[]

	useEffect(() => {
		if (!watchQAList.length) {
			form.setFieldValue(['qa_list'], [])
		}
	}, [watchQAList?.length])

	return (
		<>
			<div className="gap-6 p-6">
				<Button
					onClick={() => {
						const newList = [
							...watchQAList,
							{
								key: nanoid(),
								question: '',
								answer: '',
							},
						]
						form.setFieldValue(['qa_list'], newList)
					}}
				>
					新增
				</Button>

				<SortableList<TListItem>
					value={watchQAList.filter(
						(item) =>
							!(item.question === undefined && item.answer === undefined),
					)}
					ref={ref}
					onChange={(newList) => {
						form.setFieldValue(['qa_list'], newList)
					}}
					getItemStyles={() => ({ padding: '16px' })}
					renderEmpty={() => <Empty description="目前沒有 QA" />}
					renderItem={(item: TListItem, { index, listeners }) => {
						const collapseItem: TCollapseItem = {
							key: item.key,
							label: (
								<Item
									name={['qa_list', index, 'question'] as string[]}
									noStyle
									initialValue={item.question}
								>
									<Input placeholder="請輸入問題" />
								</Item>
							),
							children: (
								<Item
									name={['qa_list', index, 'answer'] as string[]}
									noStyle
									initialValue={item.answer}
								>
									<Input.TextArea placeholder="請輸入答案" rows={5} />
								</Item>
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
										onClick={() => {
											const list = form.getFieldValue(['qa_list'])
											form.setFieldValue(
												['qa_list'],
												list.filter((_: TListItem, i: number) => i !== index),
											)
										}}
									/>
								</div>
							</div>
						)
					}}
				/>
			</div>
			<Item hidden name={['qa_list']} initialValue={[]}>
				<Input />
			</Item>
		</>
	)
}

export const CourseQA = memo(CourseQAComponent)
