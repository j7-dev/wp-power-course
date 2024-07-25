import React from 'react'
import { Form, Input, Button } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'

const { Item } = Form

const index = () => {
	const [form] = Form.useForm()
	const { handleSave, mutation } = useSave({ form })
	const { isLoading: isSaveLoading } = mutation
	const { isLoading: isGetLoading } = useOptions({ form })

	return (
		<Form layout="vertical" form={form} onFinish={handleSave}>
			<div className="grid grid-cols-4">
				<Item name={['bunny_library_id']} label="Bunny Library ID">
					<Input disabled={isGetLoading || isSaveLoading} allowClear />
				</Item>
			</div>
			<div className="grid grid-cols-4">
				<Button
					type="primary"
					htmlType="submit"
					loading={isSaveLoading}
					disabled={isGetLoading}
				>
					儲存
				</Button>
			</div>
		</Form>
	)
}

export default index
