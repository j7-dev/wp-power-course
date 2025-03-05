import React, { memo } from 'react'
import { Space, InputNumber, Button, Form } from 'antd'
import { useCreateMany, useParsed } from '@refinedev/core'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'

const { Item } = Form

const AddChapters = ({ records }: { records: TChapterRecord[] }) => {
	const { id } = useParsed()
	const [form] = Form.useForm()

	const { mutate, isLoading } = useCreateMany({
		resource: 'chapters',
	})

	const handleCreateMany = () => {
		const values = form.getFieldsValue()
		mutate({
			values,
			invalidates: ['list'],
		})
	}

	return (
		<Form form={form} className="w-full">
			<Space.Compact>
				<Button type="primary" loading={isLoading} onClick={handleCreateMany}>
					新增
				</Button>
				<Item name={['qty']}>
					<InputNumber className="w-40" addonAfter="個" />
				</Item>
			</Space.Compact>
			<Item name={['parent_course_id']} initialValue={id} hidden />
			<Item name={['chapter_video', 'type']} initialValue="none" hidden />
		</Form>
	)
}

export default memo(AddChapters)
