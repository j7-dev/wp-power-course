import React from 'react'
import { Form, Input, Button, Alert, Divider } from 'antd'
import useOptions from './hooks/useOptions'
import useSave from './hooks/useSave'
import { FiSwitch } from '@/components/formItem'
import { SimpleImage, FileUpload } from '@/components/general'
import bunnyTutorial1 from '@/assets/images/bunny-tutorial-1.jpg'
import bunnyTutorial2 from '@/assets/images/bunny-tutorial-2.jpg'
import { RxExternalLink } from 'react-icons/rx'
import { useApiUrl } from '@refinedev/core'
import { siteUrl } from '@/utils'
import { DownloadOutlined } from '@ant-design/icons'
import { SiMicrosoftexcel } from 'react-icons/si'

const { Item } = Form

const index = () => {
	const apiUrl = useApiUrl()
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
								'開啟後，課程商品的 product/{slug} 連結將會被覆寫為 courses/{slug} (預設的 course permalink structure)',
							initialValue: 'yes',
						}}
						switchProps={{
							disabled: isGetLoading || isSaveLoading,
						}}
					/>

					<Item
						name={['course_permalink_structure']}
						label="修改課程商品的永久連結結構"
						tooltip="請先確保網址結構沒有與其他外掛、主題衝突"
					>
						<Input disabled={isGetLoading || isSaveLoading} allowClear />
					</Item>

					<Button
						type="primary"
						htmlType="submit"
						loading={isSaveLoading}
						disabled={isGetLoading}
					>
						儲存
					</Button>

					<Divider className="mt-12" plain>
						批次上傳學員權限
					</Divider>

					<Alert
						message="批次上傳注意事項"
						description={
							<ol className="pl-4">
								<li>
									<Button
										type="link"
										className="pl-0 ml-0"
										icon={<SiMicrosoftexcel />}
										iconPosition="end"
										href={`${siteUrl}/wp-content/plugins/power-course/sample.csv`}
									>
										下載範例 csv 檔案
									</Button>
								</li>
								<li>
									實測可上傳 5000
									筆資料以上等大型資料，會分批處理，不會造成伺服器阻塞
								</li>
								<li>
									處理完成後會寄信通知管理員，或者到{' '}
									<a
										target="_blank"
										rel="noopener noreferrer"
										href={`${siteUrl}/wp-admin/admin.php?page=wc-status&tab=action-scheduler&s=pc_batch_add_students_task`}
									>
										Action Scheduler
									</a>{' '}
									查看
								</li>
								<li>
									選擇檔案後會立即排程上傳，過程中會添加用戶成為課程學員(如果找不到會創建用戶並發送密碼重設信件)，上傳後無法
									rollback，建議上傳前備份資料庫
								</li>
							</ol>
						}
						type="info"
						showIcon
					/>

					<FileUpload
						uploadProps={{
							className: 'mt-4 block w-full [&_.ant-upload]:w-full',
							action: `${apiUrl}/users/upload-students`,
						}}
						buttonProps={{
							danger: true,
							type: 'primary',
							className: '!w-full',
							children: '上傳 CSV 檔案 (請先備份資料庫)',
						}}
					/>
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
