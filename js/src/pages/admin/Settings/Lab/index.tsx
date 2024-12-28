import { memo } from 'react'
import { FiSwitch } from '@/components/formItem'
import { Heading } from '@/components/general'

const index = () => {
	return (
		<div className="flex flex-col md:flex-row gap-8">
			<div className="w-full max-w-[400px]">
				<Heading className="mt-8">性能提升</Heading>
				<FiSwitch
					formItemProps={{
						name: ['pc_enable_api_booster'],
						label: '啟用後台 API 加速器，約提速 60% ~ 100%',
						tooltip:
							'開啟後，只會在 Power Course 後台加載 Woocommerce, Woocommerce Subscriptions, Power Course, Powerhouse4個外掛，其餘外掛不會加載，實現加速效果',
						initialValue: 'no',
						help: '如果啟用後發生錯誤，請與管理員聯繫，並先暫時停用此功能',
					}}
				/>
			</div>
			<div className="flex-1 h-auto md:h-[calc(100%-5.375rem)] md:overflow-y-auto">
				{/* <Heading className="mt-8">說明</Heading>
				<iframe
					className="max-w-[400px] w-full aspect-video"
					src="https://www.youtube.com/embed/OnDK8sV0rQg?si=CHf80HE8hd2k20Yh"
					title="YouTube video player"
					frameBorder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					referrerPolicy="strict-origin-when-cross-origin"
					allowFullScreen
				></iframe> */}
			</div>
		</div>
	)
}

export default memo(index)
