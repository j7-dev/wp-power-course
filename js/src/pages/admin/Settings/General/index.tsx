import { memo } from 'react'
import { Form, Input, InputNumber, ColorPicker, Color, Alert } from 'antd'
import { Heading } from '@/components/general'
const { Item } = Form

const index = () => {
	return (
		<div className="flex flex-col md:flex-row gap-8">
			<div className="w-full max-w-[400px]">
				<Heading className="mt-8">擴展課程銷售頁永久連結設定</Heading>
				<Item
					name={['course_permalink_structure']}
					label="擴充課程銷售頁的永久連結結構"
					tooltip="例如: courses/{slug} 當用戶前往 courses/{slug} 時，也能看到課程銷售頁"
				>
					<Input allowClear />
				</Item>

				<Heading className="mt-8">教室影片浮水印設定</Heading>
				<Alert
					className="mb-4"
					message="防止自己努力錄製的心血被盜錄"
					description={
						<ol className="pl-4">
							<li>浮水印顯示當前用戶的 Email</li>
							<li>開啟動態浮水印，嚇阻有心人士盜錄</li>
							<li>只有教室影片才會顯示動態浮水印，銷售頁影片不會顯示</li>
						</ol>
					}
					type="info"
					showIcon
				/>
				<Item
					name={['pc_marquee_qty']}
					label="教室影片浮水印數量"
					help="填 0 就不顯示浮水印，建議數量 3~10，太多會影響觀影體驗"
				>
					<InputNumber min={0} max={30} className="w-full" />
				</Item>
				<Item
					name={['pc_marquee_color']}
					label="影片浮水印顏色"
					normalize={(value: Color) => value.toRgbString()}
				>
					<ColorPicker
						defaultFormat="rgb"
						presets={[
							{
								label: '預設',
								colors: [
									'rgba(255, 255, 255, 0.5)',
									'rgba(200, 200, 200, 0.5)',
								],
							},
						]}
					/>
				</Item>
			</div>
			<div className="flex-1 h-auto md:h-[calc(100%-5.375rem)] md:overflow-y-auto"></div>
		</div>
	)
}

export default memo(index)
