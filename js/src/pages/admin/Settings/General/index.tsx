import { memo } from 'react'
import { Form, Input } from 'antd'
import { FiSwitch } from '@/components/formItem'
import { Heading } from '@/components/general'
const { Item } = Form

const index = () => {
	return (
		<div className="flex flex-col md:flex-row gap-8">
			<div className="w-full max-w-[400px]">
				<Heading className="mt-8">課程商品永久連結設定</Heading>
				<FiSwitch
					formItemProps={{
						name: ['override_course_product_permalink'],
						label: '改寫課程商品的永久連結',
						tooltip:
							'開啟後，課程商品的 product/{slug} 連結將會被覆寫為 courses/{slug} (預設的 course permalink structure)',
						initialValue: 'yes',
					}}
				/>

				<Item
					name={['course_permalink_structure']}
					label="修改課程商品的永久連結結構"
					tooltip="請先確保網址結構沒有與其他外掛、主題衝突"
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
