import { Switch, Form, Empty } from 'antd'
import { Edit, useForm } from '@refinedev/antd'
import { useParsed, HttpError } from '@refinedev/core'
import EmailEditor from './EmailEditor'
import type { TEmailRecord } from '@/pages/admin/Emails/types'
import mjml2html from 'mjml-browser'
import { JsonToMjml, IBlockData } from 'easy-email-core'

// import { EmailEditorProvider } from './EasyEmail/components/Provider/EmailEditorProvider'

const { Item } = Form

type TFormValues = {
	name: string
	short_description: IBlockData
}

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

	if (!record) {
		return <Empty className="mt-[10rem]" description="找不到 Email" />
	}
	const { name } = record

	const handleSubmit = (values: {
		name: string
		short_description: IBlockData
	}) => {
		// 要存的時候才將 json 轉成 html

		onFinish({
			...values,
			short_description: JSON.stringify(values.short_description),
			description: mjml2html(
				JsonToMjml({
					data: values.short_description,
					mode: 'production',
				}),
				{
					minify: true,
				},
			)?.html,
		})
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
					{/* 存 html ， 這樣 php 可以直接用 */}

					{/* 存 json ， 才不會跑版 */}
					<Item hidden name={['short_description']} />

					<EmailEditor {...formReturn} />
				</Form>
			</Edit>
		</>
	)
}
export default EmailsEdit
