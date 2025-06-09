import { memo, useState } from 'react'
import { Typography, Form, Select, Checkbox, Spin, Alert } from 'antd'
import { Heading } from '@/components/general'
import { useProductSelect } from '@/hooks'

import { useCustom, useApiUrl } from '@refinedev/core'
import { renderHTML } from 'antd-toolkit'

const { Item } = Form
const { Text } = Typography

const Bundle = () => {
	const [shortcode, setShortcode] = useState('[pc_bundle_card]')
	const { selectProps } = useProductSelect({
		selectProps: {
			mode: undefined,
		},
		useSelectProps: {
			filters: [
				{
					field: 'type',
					operator: 'in',
					value: ['simple', 'power_bundle_product', 'subscription'],
				},
				{
					field: 'meta_key',
					operator: 'eq',
					value: 'link_course_ids',
				},
				{
					field: 'meta_compare',
					operator: 'eq',
					value: 'EXISTS',
				},
			],
		},
	})

	const [form] = Form.useForm()

	const handleValuesChange = () => {
		const { product_id } = form.getFieldsValue()
		setShortcode(
			`[pc_bundle_card${product_id ? ` product_id="${product_id}"` : ''}]`,
		)
	}

	const watchPreview = Form.useWatch(['preview'], form) || false
	const apiUrl = useApiUrl('power-course')
	const { data, isFetching } = useCustom({
		url: `${apiUrl}/shortcode`,
		method: 'get',
		config: {
			query: {
				shortcode,
			},
		},
		queryOptions: {
			enabled: watchPreview,
		},
	})

	const html = data?.data?.data || ''

	return (
		<>
			<Heading className="mt-8">銷售方案</Heading>
			<div className="grid grid-cols-1 md:grid-cols-[25rem_1fr] gap-8">
				<div>
					<Form
						form={form}
						labelCol={{ span: 8 }}
						wrapperCol={{ span: 14 }}
						layout="horizontal"
						onValuesChange={handleValuesChange}
					>
						<Item
							name={['preview']}
							label="即時預覽"
							initialValue={false}
							valuePropName="checked"
						>
							<Checkbox />
						</Item>

						<Item
							name={['product_id']}
							label="選擇銷售方案"
							initialValue={false}
						>
							<Select {...selectProps} />
						</Item>
					</Form>
					<Text key="copy" code className="m-0 text-base tw-block" copyable>
						{shortcode}
					</Text>
				</div>
				<Spin spinning={isFetching}>
					<Alert
						message="卡片本身寬度適應外容器，需要自己設定外容器寬度，建議大約 300~400px"
						type="info"
						className="mb-4"
						showIcon
					/>
					{/* 預覽 */}
					<div className="w-[20rem]">{renderHTML(html)}</div>
				</Spin>
			</div>
		</>
	)
}

export default memo(Bundle)
