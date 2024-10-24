import { memo } from 'react'
import { Form, Select, Input, Switch } from 'antd'
import { RangePicker } from '@/components/formItem'
import BundleTypes from './BundleTypes'
import { OPTIONS, INCLUDED_PRODUCT_IDS_FIELD_NAME } from './utils'
import { useAtomValue } from 'jotai'
import { courseAtom } from './atom'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

// TODO 目前只支援簡單商品
// TODO 如何結合可變商品?

// dayjs.extend(customParseFormat)

const { Item } = Form

const BundleForm = () => {
	const course = useAtomValue(courseAtom)
	const { id } = course as TCourseRecord

	return (
		<>
			<Item name={['link_course_ids']} initialValue={[id]} hidden />
			<Item
				name={['bundle_type']}
				label="銷售方案種類"
				initialValue={OPTIONS[0].value}
			>
				<Select options={OPTIONS} />
			</Item>
			<Item
				name={['bundle_type_label']}
				label="銷售方案種類顯示文字"
				tooltip="銷售方案名稱上方的紅色小字"
			>
				<Input />
			</Item>
			<Item
				name={['name']}
				label="銷售方案名稱"
				rules={[
					{
						required: true,
						message: '請輸入銷售方案名稱',
					},
				]}
			>
				<Input />
			</Item>
			<Item name={['purchase_note']} label="銷售方案說明">
				<Input.TextArea rows={8} />
			</Item>

			<Item name={[INCLUDED_PRODUCT_IDS_FIELD_NAME]} initialValue={[]} hidden />

			<BundleTypes />

			<RangePicker
				formItemProps={{
					name: ['sale_date_range'],
					label: '銷售期間',
				}}
			/>

			<div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
				<Item name={['virtual']} label="虛擬商品" initialValue={true}>
					<Switch />
				</Item>

				<Item name={['status']} hidden />
			</div>
		</>
	)
}

export default memo(BundleForm)
