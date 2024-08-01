import React from 'react'
import { Form, Input, Button } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'
import { FiSwitch } from '@/components/formItem'

const { Item } = Form

const index = () => {
	const [form] = Form.useForm()
	const { handleSave, mutation } = useSave({ form })
	const { isLoading: isSaveLoading } = mutation
	const { isLoading: isGetLoading } = useOptions({ form })

	return (
		<Form layout="vertical" form={form} onFinish={handleSave}>
			<div className="flex flex-col md:flex-row gap-8">
				<div className="w-full max-w-[400px]">
					<Item name={['bunny_library_id']} label="Bunny Library ID">
						<Input disabled={isGetLoading || isSaveLoading} allowClear />
					</Item>
					<FiSwitch
						formItemProps={{
							name: ['override_course_product_permalink'],
							label: '改寫課程商品的永久連結',
							tooltip:
								'開啟後，課程商品的 product/{slug} 連結將會被覆寫為 courses/{slug}',
							initialValue: 'yes',
						}}
					/>

					<Button
						type="primary"
						htmlType="submit"
						loading={isSaveLoading}
						disabled={isGetLoading}
					>
						儲存
					</Button>
				</div>
				<div className="flex-1">說明</div>
			</div>
		</Form>
	)
}

export default index
