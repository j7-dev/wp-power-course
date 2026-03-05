import { memo } from 'react'
import { useSelect } from '@refinedev/antd'
import { Button, Divider, Empty, Form, InputNumber, Select, Space } from 'antd'
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons'
import { defaultSelectProps } from 'antd-toolkit'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

const { Item, List } = Form

const limitTypeOptions = [
	{
		label: '無期限',
		value: 'unlimited',
	},
	{
		label: '固定期限',
		value: 'fixed',
	},
]

const limitUnitOptions = [
	{
		label: '天',
		value: 'day',
	},
	{
		label: '月',
		value: 'month',
	},
	{
		label: '年',
		value: 'year',
	},
]

const index = () => {
	const form = Form.useFormInstance()

	const { selectProps: courseSelectProps } = useSelect<TCourseRecord>({
		resource: 'courses',
		dataProviderName: 'power-course',
		optionLabel: 'name',
		optionValue: 'id',
		debounce: 500,
		pagination: {
			pageSize: 20,
			mode: 'server',
		},
		onSearch: (value) => [
			{
				field: 's',
				operator: 'contains',
				value,
			},
		],
	})

	return (
		<div className="w-full max-w-[600px]">
			<List name={['auto_grant_courses']}>
				{(fields, { add, remove }) => (
					<>
						{fields.length === 0 && (
							<Empty
								className="my-8"
								description="尚未設定任何註冊自動開通課程"
							/>
						)}

						{fields.map((field, index) => (
							<div key={field.key}>
								<Space className="w-full" align="start">
									<div className="flex-1">
										<Item
											{...field}
											label="課程搜尋"
											name={[field.name, 'course_id']}
											rules={[
												{
													required: true,
													message: '請選擇課程',
												},
											]}
										>
											<Select
												{...defaultSelectProps}
												{...courseSelectProps}
												placeholder="搜尋課程關鍵字"
											/>
										</Item>

										<Space.Compact block>
											<Item
												{...field}
												label="觀看期限"
												name={[field.name, 'limit_type']}
												initialValue="unlimited"
												className="w-40"
											>
												<Select
													options={limitTypeOptions}
													onChange={(value) => {
														if ('unlimited' === value) {
															form.setFieldValue(
																['auto_grant_courses', field.name, 'limit_value'],
																undefined,
															)
															form.setFieldValue(
																['auto_grant_courses', field.name, 'limit_unit'],
																undefined,
															)
														}

														if ('fixed' === value) {
															form.setFieldValue(
																['auto_grant_courses', field.name, 'limit_value'],
																form.getFieldValue([
																	'auto_grant_courses',
																	field.name,
																	'limit_value',
																]) || 30,
															)
															form.setFieldValue(
																['auto_grant_courses', field.name, 'limit_unit'],
																form.getFieldValue([
																	'auto_grant_courses',
																	field.name,
																	'limit_unit',
																]) || 'day',
															)
														}
													}}
												/>
											</Item>

											<Form.Item
												noStyle
												shouldUpdate={(prevValues, currentValues) => {
													const prevType =
														prevValues?.auto_grant_courses?.[field.name]?.limit_type
													const currentType =
														currentValues?.auto_grant_courses?.[field.name]
															?.limit_type
													return prevType !== currentType
												}}
											>
												{({ getFieldValue }) => {
													const limitType =
														getFieldValue([
															'auto_grant_courses',
															field.name,
															'limit_type',
														]) || 'unlimited'

													if ('fixed' !== limitType) {
														return null
													}

													return (
														<>
															<Item
																{...field}
																name={[field.name, 'limit_value']}
																label=" "
																className="w-full"
																rules={[
																	{
																		required: true,
																		message: '請填寫期限',
																	},
																]}
															>
																<InputNumber min={1} className="w-full" />
															</Item>
															<Item
																{...field}
																name={[field.name, 'limit_unit']}
																label=" "
																className="w-24"
																rules={[
																	{
																		required: true,
																		message: '請選擇單位',
																	},
																]}
															>
																<Select options={limitUnitOptions} />
															</Item>
														</>
													)
												}}
											</Form.Item>
										</Space.Compact>
									</div>

									<Button
										className="mt-[30px]"
										type="text"
										danger
										icon={<DeleteOutlined />}
										onClick={() => remove(field.name)}
									>
										刪除
									</Button>
								</Space>
								{index < fields.length - 1 && <Divider className="my-2" />}
							</div>
						))}

						<Button
							type="dashed"
							icon={<PlusOutlined />}
							onClick={() =>
								add({
									limit_type: 'unlimited',
									limit_value: undefined,
									limit_unit: undefined,
								})
							}
						>
							+ 新增課程
						</Button>
					</>
				)}
			</List>
		</div>
	)
}

export default memo(index)
