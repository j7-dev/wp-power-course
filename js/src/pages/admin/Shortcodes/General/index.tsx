import { memo } from 'react'
import { Typography } from 'antd'
import { SimpleImage, Heading } from '@/components/general'
import bunnyTutorial1 from '@/assets/images/bunny-tutorial-1.jpg'

const { Text } = Typography

const index = () => {
	return (
		<div className="grid grid-cols-3 gap-8">
			<div>
				<Heading className="mt-8">課程列表</Heading>
				<Text className="text-base" copyable code>
					[power_course_list]
				</Text>
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto">
				<p className="font-bold mb-4">說明</p>
				<div className="mb-4">
					<p>1. 前往 Bunny 後台，選擇 「Stream」 並進入 「Library」</p>
					<SimpleImage
						src={bunnyTutorial1}
						ratio="aspect-[2.1]"
						className="w-full"
					/>
				</div>
			</div>
		</div>
	)
}

export default memo(index)
