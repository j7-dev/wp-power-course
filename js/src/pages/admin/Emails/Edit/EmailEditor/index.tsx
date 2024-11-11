import React, { memo } from 'react'
import {
	BlockManager,
	BasicType,
	AdvancedType,
	IBlockData,
} from 'easy-email-core'
import {
	EmailEditor,
	EmailEditorProvider,
	IEmailTemplate,
} from 'easy-email-editor'
import type { FormApi, FormState } from 'final-form'
import { ExtensionProps, StandardLayout } from 'easy-email-extensions'
import type { HttpError } from '@refinedev/core'
import type { UseFormReturnType } from '@refinedev/antd'
import type { TEmailRecord } from '@/pages/admin/Emails/types'
import type { FormInstance } from 'antd'
import parseJson from 'parse-json'

import 'easy-email-editor/lib/style.css'
import 'easy-email-extensions/lib/style.css'

// theme, If you need to change the theme, you can make a duplicate in https://arco.design/themes/design/1799/setting/base/Color
import '@arco-themes/react-easy-email-theme/css/arco.css'

const initBlock = BlockManager.getBlockByType(BasicType.PAGE)!.create({})

function getInitContent(form: FormInstance, defaultBlock: IBlockData) {
	const initContentString = form.getFieldValue(['short_description'])

	let initContent = defaultBlock
	if (initContentString) {
		try {
			initContent = parseJson(initContentString) as any
		} catch (error) {
			console.log('parse JSON error: ', error)
		}
	}
	return initContent
}

/**
 * Easy Email Editor
 * @see https://github.com/zalify/easy-email-editor
 * @param {UseFormReturnType} props - UseFormReturnType
 * @return {JSX.Element}
 */
const CustomEmailEditor = (
	props: UseFormReturnType<
		TEmailRecord,
		HttpError,
		{},
		TEmailRecord,
		TEmailRecord,
		HttpError
	>,
) => {
	const { formProps, form, saveButtonProps, mutation, onFinish, query } = props

	const initialValues: IEmailTemplate = query?.isLoading
		? {
				subject: '',
				subTitle: '',
				content: initBlock,
			}
		: {
				subject: form.getFieldValue(['name']),
				subTitle: '',
				content: getInitContent(form, initBlock),
			}

	return (
		<EmailEditorProvider
			data={initialValues}
			height={'calc(100vh - 72px)'}
			dashed={false}
			onUploadImage={(file) => {
				console.log('⭐  onUploadImage file:', file)
				return Promise.resolve('')
			}}
		>
			{(
				formState: FormState<IEmailTemplate>,
				helper: FormApi<IEmailTemplate, Partial<IEmailTemplate>>,
			) => {
				form.setFieldValue(['name'], formState?.values?.subject || '')
				form.setFieldValue(['short_description'], formState?.values?.content)
				return (
					<>
						<StandardLayout showSourceCode={true}>
							<EmailEditor />
						</StandardLayout>
					</>
				)
			}}
		</EmailEditorProvider>
	)
}

export default memo(CustomEmailEditor)
