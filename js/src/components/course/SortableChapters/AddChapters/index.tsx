import React, { memo, useEffect } from 'react'
import { Space, Select, InputNumber, Button, Form } from 'antd'
import { useCreateMany, useParsed } from '@refinedev/core'
import { TChapterRecord } from '@/pages/admin/Courses/List/types'
import { defaultSelectProps } from '@/utils'

const { Item } = Form

const AddChapters = ({ records }: { records: TChapterRecord[] }) => {
	const { id } = useParsed()

	const [form] = Form.useForm()
	const watchDepth = Form.useWatch(['depth'], form) || 0
	const watchQty = Form.useWatch(['qty'], form) || 0
	const watchPostParents = Form.useWatch(['post_parents'], form) || []
	const canAdd =
		(watchDepth === 0 && watchQty > 0) ||
		(watchDepth > 0 && watchQty > 0 && watchPostParents?.length > 0)

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

	useEffect(() => {
		form.setFieldValue('post_parents', watchDepth === 0 ? [id] : [])
	}, [watchDepth])

	return (
		<Form form={form}>
			<div className="flex gap-x-4">
				<Button
					type="primary"
					loading={isLoading}
					onClick={handleCreateMany}
					disabled={!canAdd}
				>
					新增
				</Button>
				<Space.Compact>
					<Item name={['depth']} initialValue={0}>
						<Select
							className="w-24"
							options={[
								{
									value: 0,
									label: '章節',
								},
								{
									value: 1,
									label: '單元',
								},
							]}
						/>
					</Item>
					<Item name={['qty']}>
						<InputNumber className="w-40" addonAfter="個" />
					</Item>
				</Space.Compact>

				<div
					className={`ml-8 flex-1 flex items-center gap-x-4 ${watchDepth > 0 ? '' : 'tw-hidden'}`}
				>
					<label>在那些章節底下新增: </label>
					<Item name={['post_parents']} className="flex-1" initialValue={[id]}>
						<Select
							{...defaultSelectProps}
							defaultValue={0}
							options={records?.map((record) => ({
								value: record?.id,
								label: record?.name,
							}))}
						/>
					</Item>
				</div>
			</div>
		</Form>
	)
}

export default memo(AddChapters)
