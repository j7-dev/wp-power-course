import { memo } from 'react'
import { Button, Empty, Form, Select, Space } from 'antd'
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons'
import { useCourseSelect } from '@/hooks'
import { WatchLimit } from '@/components/formItem'

type TAutoGrantCourseRowProps = {
	field: {
		name: number
	}
	onRemove: (index: number) => void
}

const AutoGrantCourseRow = ({ field, onRemove }: TAutoGrantCourseRowProps) => {
	const { selectProps } = useCourseSelect()
	const courseSelectProps = { ...selectProps }
	delete courseSelectProps.value
	delete courseSelectProps.onChange

	return (
		<div className="rounded border border-gray-200 p-4">
			<div className="flex items-start gap-3">
				<Form.Item
					className="mb-0 flex-1"
					name={[field.name, 'course_id']}
					label="課程"
					rules={[{ required: true, message: '請選擇課程' }]}
				>
					<Select
						{...courseSelectProps}
						placeholder="搜尋課程關鍵字"
					/>
				</Form.Item>
				<Button
					danger
					icon={<DeleteOutlined />}
					onClick={() => onRemove(field.name)}
					className="mt-[30px]"
				/>
			</div>
			<WatchLimit namePrefix={[field.name]} showFollowSubscription={false} />
		</div>
	)
}

const AutoGrant = () => {
	const form = Form.useFormInstance()
	const autoGrantCourses = Form.useWatch(['auto_grant_courses'], form) ?? []

	return (
		<Form.List name="auto_grant_courses">
			{(fields, { add, remove }) => (
				<div className="flex flex-col gap-4">
					{!autoGrantCourses.length && (
						<Empty description="尚未設定自動開通課程，點擊下方按鈕新增" />
					)}
					<Space direction="vertical" size={16} className="w-full">
						{fields.map((field) => (
							<AutoGrantCourseRow
								key={field.key}
								field={{ name: field.name }}
								onRemove={remove}
							/>
						))}
					</Space>
					<Button
						icon={<PlusOutlined />}
						onClick={() =>
							add({
								course_id: undefined,
								limit_type: 'unlimited',
								limit_value: null,
								limit_unit: null,
							})
						}
					>
						新增課程
					</Button>
				</div>
			)}
		</Form.List>
	)
}

export default memo(AutoGrant)
