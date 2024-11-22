import { memo, useState } from 'react'
import {
	Typography,
	Form,
	Card,
	InputNumber,
	Slider,
	SliderSingleProps,
	Select,
} from 'antd'
import { Heading } from '@/components/general'
import { useCourseSelect } from '@/hooks'
import useOptions from '@/components/product/ProductTable/hooks/useOptions'
import {
	keyLabelMapper,
	termToOptions,
} from '@/components/product/ProductTable/utils'
import { defaultSelectProps } from '@/utils'
import CourseCard, { EXAMPLES } from './CourseCard'

const { Item } = Form
const { Text } = Typography

const marks: SliderSingleProps['marks'] = {
	2: '2',
	3: '3',
	4: '4',
}

const General = () => {
	const [shortcode, setShortcode] = useState('[pc_courses]')
	const { selectProps } = useCourseSelect()
	const { options } = useOptions({
		endpoint: 'courses/options',
	})
	const { product_cats = [], product_tags = [] } = options
	const [form] = Form.useForm()

	const handleValuesChange = () => {
		const values = form.getFieldsValue()
		const valuesToShortCodeString = Object.keys(values).reduce((acc, key) => {
			const value = values[key]

			if (!value) {
				// 如果 undefined 就不加上
				return acc
			}

			if (Array.isArray(value) && value?.length === 0) {
				// 如果是 [] 也不加上
				return acc
			}

			if ('columns' === key && value === 3) {
				// 如果 columns 是 3 就不加上
				return acc
			}

			if ('limit' === key && value === 12) {
				// 如果 limit 是 12 就不加上
				return acc
			}

			acc += ` ${key}="${value}"`
			return acc
		}, '')
		setShortcode(`[pc_courses${valuesToShortCodeString}]`)
	}

	const watchLimit = Form.useWatch(['limit'], form) || 12
	const watchColumns = Form.useWatch(['columns'], form) || 3
	const gridClass = () => {
		if (watchColumns === 2) return 'lg:grid-cols-2'
		if (watchColumns === 3) return 'lg:grid-cols-3'
		if (watchColumns === 4) return 'lg:grid-cols-4'
	}

	return (
		<Card>
			<Heading className="mt-8">課程列表</Heading>
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
							name={['limit']}
							label="顯示數量"
							tooltip="預設 12"
							initialValue={12}
						>
							<InputNumber className="w-full" min={0} max={100} />
						</Item>
						<Item
							name={['columns']}
							label="欄位"
							tooltip="預設 3"
							initialValue={3}
						>
							<Slider marks={marks} min={2} max={4} />
						</Item>
						<Item name={['include']} label="只包含指定課程">
							<Select {...selectProps} />
						</Item>
						<Item name={['orderby']} label="排序依據">
							<Select
								className="w-full"
								allowClear
								options={[
									{
										value: 'ID',
										label: 'ID',
									},
									{
										value: 'name',
										label: '名稱',
									},
									{
										value: 'rand',
										label: '隨機',
									},
									{
										value: 'date',
										label: '發布時間',
									},
									{
										value: 'modified',
										label: '修改時間',
									},
								]}
							/>
						</Item>
						<Item name={['order']} label="排序調整">
							<Select
								className="w-full"
								allowClear
								options={[
									{
										value: 'ASC',
										label: '升序 (ASC)',
									},
									{
										value: 'DESC',
										label: '降序 (ASC)',
									},
								]}
							/>
						</Item>
						<Item
							name={['product_category_id']}
							label={keyLabelMapper('product_category_id')}
						>
							<Select
								{...defaultSelectProps}
								options={termToOptions(product_cats)}
								placeholder="可多選"
							/>
						</Item>

						<Item
							name={['product_tag_id']}
							label={keyLabelMapper('product_tag_id')}
						>
							<Select
								{...defaultSelectProps}
								options={termToOptions(product_tags)}
								placeholder="可多選"
							/>
						</Item>
					</Form>
					<Text key="copy" code className="m-0 text-base" copyable>
						{shortcode}
					</Text>
				</div>
				<div>
					{/* 預覽 */}
					<div className={`grid grid-cols-2 gap-x-5 gap-y-14 ${gridClass()}`}>
						{new Array(watchLimit).fill(null).map((_, index) => {
							const example_index = index % EXAMPLES.length
							return <CourseCard key={index} {...EXAMPLES[example_index]} />
						})}
					</div>
				</div>
			</div>
		</Card>
	)
}

export default memo(General)
