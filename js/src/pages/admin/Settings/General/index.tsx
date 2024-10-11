import { memo } from 'react'
import { Form, Input } from 'antd'
import { FiSwitch } from '@/components/formItem'
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

				<Heading className="mt-8">My Account 設定</Heading>
				<FiSwitch
					formItemProps={{
						name: ['hide_myaccount_courses'],
						label: '隱藏 My Account 我的學習選單',
						tooltip:
							'還沒準備好對外公布你的課程網站? 可以隱藏 My Account 我的學習選單',
						initialValue: 'no',
					}}
				/>
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto"></div>
		</div>
	)
}

export default memo(index)
