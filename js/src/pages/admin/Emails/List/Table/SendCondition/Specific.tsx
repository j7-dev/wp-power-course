import React from 'react'
import { Form, Select, Button, Modal } from 'antd'
import { useModal } from '@refinedev/antd'
import {
	UserTable,
	selectedUserIdsAtom,
	SelectedUser,
} from '@/components/user/UserTable'
import { useAtom } from 'jotai'
import { ArrowsAltOutlined } from '@ant-design/icons'
import { useCustomMutation, useApiUrl } from '@refinedev/core'

const { Item } = Form

const Specific = ({ email_ids }: { email_ids: string[] }) => {
	const [selectedUserIds, setSelectedUserIds] = useAtom(selectedUserIdsAtom)
	const [form] = Form.useForm()
	const { show, modalProps } = useModal()
	const watchType = Form.useWatch(['trigger', 'type'], form)

	const apiUrl = useApiUrl('power-email')
	const { mutate: SendEmail, isLoading } = useCustomMutation()
	const handleSend = () => {
		SendEmail({
			url: `${apiUrl}/emails/send`,
			method: 'post',
			values: {
				email_ids,
				user_ids: selectedUserIds,
			},
		})
	}

	return (
		<>
			<Form form={form} layout="vertical" className="mb-8">
				<div className="w-full max-w-[20rem]">
					<Button
						onClick={show}
						icon={<ArrowsAltOutlined />}
						iconPosition="end"
					>
						選擇要發送的用戶
					</Button>
					<SelectedUser
						user_ids={selectedUserIds}
						onClick={() => setSelectedUserIds([])}
					/>
					<div>
						<Button
							type="primary"
							onClick={handleSend}
							loading={isLoading}
							disabled={email_ids.length !== 1}
						>
							立即發送
						</Button>
						<Button className="ml-2">排程</Button>
					</div>
				</div>
			</Form>
			<Modal
				{...modalProps}
				title="選擇用戶"
				width={1600}
				footer={null}
				centered
				zIndex={2000}
			>
				<UserTable
					cardProps={null}
					tableProps={{
						scroll: { y: 420 },
					}}
				/>
			</Modal>
		</>
	)
}

export default Specific
