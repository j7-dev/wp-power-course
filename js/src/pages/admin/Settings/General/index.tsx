import { memo } from 'react'
import { Form, Input } from 'antd'
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
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto"></div>
		</div>
	)
}

export default memo(index)
