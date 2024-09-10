import { memo } from 'react'
import { Form, Input, Alert } from 'antd'
import { SimpleImage, Heading } from '@/components/general'
import bunnyTutorial1 from '@/assets/images/bunny-tutorial-1.jpg'
import bunnyTutorial2 from '@/assets/images/bunny-tutorial-2.jpg'
import { RxExternalLink } from 'react-icons/rx'

const { Item } = Form

const index = () => {
	return (
		<div className="flex flex-col md:flex-row gap-8">
			<div className="w-full max-w-[400px]">
				<Heading className="mt-8">Bunny Streaming API 設定</Heading>
				<Item name={['bunny_library_id']} label="Bunny Library ID">
					<Input allowClear placeholder="xxxxxxx" />
				</Item>
				<Item name={['bunny_cdn_hostname']} label="Bunny CDN Hostname">
					<Input allowClear placeholder="xx-xxxxxxxx-xxx.b-cdn.net" />
				</Item>
				<Item name={['bunny_stream_api_key']} label="Bunny Stream API Key">
					<Input
						allowClear
						placeholder="xxxxxxxx-xxxx-xxxx-xxxxxxxxxxxx-xxxx-xxxx"
					/>
				</Item>
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto">
				<p className="font-bold mb-4">說明</p>
				<Alert
					message="沒有 Bunny 帳號？"
					description={
						<>
							若還沒有 Bunny 帳號，可以
							<a
								href="https://bunny.net?ref=wd7c7lcrv4"
								target="_blank"
								rel="noopener noreferrer"
								className="ml-2 font-bold"
							>
								點此申請 <RxExternalLink className="relative top-0.5" />
							</a>
						</>
					}
					type="info"
					showIcon
				/>
				<div className="mb-4">
					<p>1. 前往 Bunny 後台，選擇 「Stream」 並進入 「Library」</p>
					<SimpleImage
						src={bunnyTutorial1}
						ratio="aspect-[2.1]"
						className="w-full"
					/>
				</div>
				<div className="mb-4">
					<p>
						2. 進入「API」分頁，複製 Library ID 、 CDN Hostname 和 Stream API
						Key
					</p>
					<SimpleImage
						src={bunnyTutorial2}
						ratio="aspect-[2.1]"
						className="w-full"
					/>
				</div>
			</div>
		</div>
	)
}

export default memo(index)