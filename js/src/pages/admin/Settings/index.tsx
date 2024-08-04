import React from 'react'
import { Form, Input, Button, Alert } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'
import { FiSwitch } from '@/components/formItem'
import { SimpleImage } from '@/components/general'
import bunnyTutorial1 from '@/assets/images/bunny-tutorial-1.jpg'
import bunnyTutorial2 from '@/assets/images/bunny-tutorial-2.jpg'
import { RxExternalLink } from 'react-icons/rx'

const { Item } = Form

const index = () => {
	const [form] = Form.useForm()
	const { handleSave, mutation } = useSave({ form })
	const { isLoading: isSaveLoading } = mutation
	const { isLoading: isGetLoading } = useOptions({ form })

	return (
		<Form layout="vertical" form={form} onFinish={handleSave}>
			<div className="flex flex-col md:flex-row gap-8">
				<div className="w-full max-w-[400px]">
					<Item name={['bunny_library_id']} label="Bunny Library ID">
						<Input
							disabled={isGetLoading || isSaveLoading}
							allowClear
							placeholder="xxxxxxx"
						/>
					</Item>
					<Item name={['bunny_stream_api_key']} label="Bunny Stream API Key">
						<Input
							disabled={isGetLoading || isSaveLoading}
							allowClear
							placeholder="xxxxxxxx-xxxx-xxxx-xxxxxxxxxxxx-xxxx-xxxx"
						/>
					</Item>
					<FiSwitch
						formItemProps={{
							name: ['override_course_product_permalink'],
							label: '改寫課程商品的永久連結',
							tooltip:
								'開啟後，課程商品的 product/{slug} 連結將會被覆寫為 courses/{slug}',
							initialValue: 'yes',
						}}
					/>

					<Button
						type="primary"
						htmlType="submit"
						loading={isSaveLoading}
						disabled={isGetLoading}
					>
						儲存
					</Button>
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
						<SimpleImage src={bunnyTutorial1} className="w-full aspect-[2.1]" />
					</div>
					<div className="mb-4">
						<p>2. 進入「API」分頁，複製 Library ID 和 Stream API Key</p>
						<SimpleImage src={bunnyTutorial2} className="w-full aspect-[2.1]" />
					</div>
				</div>
			</div>
		</Form>
	)
}

export default index
