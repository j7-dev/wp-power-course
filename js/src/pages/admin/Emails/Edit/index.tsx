import { Switch, Form, Empty } from 'antd'
import { Edit, useForm } from '@refinedev/antd'
import { useParsed } from '@refinedev/core'
import EmailEditor from './EmailEditor'

// import { EmailEditorProvider } from './EasyEmail/components/Provider/EmailEditorProvider'

const EmailsEdit = () => {
	const { id } = useParsed()

	// 初始化資料
	const { formProps, form, saveButtonProps, mutation, onFinish, query } =
		useForm({
			action: 'edit',
			resource: 'emails',
			dataProviderName: 'power-email',
			id,
			redirect: false,
			invalidates: ['list'],
			warnWhenUnsavedChanges: true,
		})
	const record = query?.data?.data
	const watchStatus = Form.useWatch(['status'], form)

	if (!record) {
		return <Empty className="mt-[10rem]" description="找不到 Email" />
	}
	const { name } = record

	return (
		<>
			<Edit
				resource="emails"
				recordItemId={id}
				headerButtons={() => null}
				title={
					<>
						《編輯》 {name} <sub className="text-gray-500">#{id}</sub>
					</>
				}
				saveButtonProps={{
					...saveButtonProps,
					children: '儲存 Email',
					icon: null,
					loading: mutation?.isLoading,
				}}
				footerButtons={({ defaultButtons }) => (
					<>
						<Switch
							className="mr-4"
							checkedChildren="發佈"
							unCheckedChildren="草稿"
							value={watchStatus === 'publish'}
							onChange={(checked) => {
								form.setFieldValue(['status'], checked ? 'publish' : 'draft')
							}}
						/>
						{defaultButtons}
					</>
				)}
			>
				<EmailEditor />
			</Edit>
		</>
	)
}
export default EmailsEdit
