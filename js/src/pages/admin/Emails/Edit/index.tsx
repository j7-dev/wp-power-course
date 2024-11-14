import { Switch, Form, Empty } from 'antd'
import { Edit, useForm } from '@refinedev/antd'
import { useParsed, HttpError } from '@refinedev/core'
import EmailEditor from './EmailEditor'
import type { TEmailRecord, TFormValues } from '@/pages/admin/Emails/types'
import mjml2html from 'mjml-browser'
import { JsonToMjml, IBlockData } from 'j7-easy-email-core'
import { SendCondition } from '@/components/emails'

// import { EmailEditorProvider } from './EasyEmail/components/Provider/EmailEditorProvider'

const { Item } = Form

const EmailsEdit = () => {
	const { id } = useParsed()

	// 初始化資料
	const formReturn = useForm<TEmailRecord, HttpError, TFormValues>({
		action: 'edit',
		resource: 'emails',
		dataProviderName: 'power-email',
		id,
		redirect: false,
		invalidates: ['list', 'detail'],
		warnWhenUnsavedChanges: true,
	})
	const { formProps, form, saveButtonProps, mutation, query, onFinish } =
		formReturn
	const record = query?.data?.data
	const watchStatus = Form.useWatch(['status'], form)

	if (!record && query?.isSuccess) {
		return <Empty className="mt-[10rem]" description="找不到 Email" />
	}
	const { name = '' } = record || {}

	const handleSubmit = (values: TFormValues) => {
		// 要存的時候才將 json 轉成 html

		onFinish({
			...values,
			short_description: JSON.stringify(values.short_description),
			description: mjml2html(
				JsonToMjml({
					data: values.short_description as IBlockData<any, any>,
					mode: 'production',
					context: values.short_description as IBlockData<any, any>,
				}),
				{
					minify: true,
				},
			)?.html,
		} as TFormValues)
	}

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
				<Form {...formProps} layout="vertical" onFinish={handleSubmit}>
					<Item hidden name={['name']} />
					<Item name={['status']} hidden />
					{/* 存 json ， 才不會跑版 */}
					<Item hidden name={['short_description']} />

					<SendCondition email_ids={[id] as string[]} />

					<EmailEditor {...formReturn} />
				</Form>
			</Edit>
		</>
	)
}
export default EmailsEdit
