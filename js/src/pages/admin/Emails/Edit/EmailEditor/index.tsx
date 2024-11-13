import React, { memo } from 'react'
import { BlockManager, BasicType, IBlockData } from 'j7-easy-email-core'
import {
	EmailEditor,
	EmailEditorProvider,
	IEmailTemplate,
} from 'j7-easy-email-editor'
import type { FormApi, FormState } from 'final-form'
import { StandardLayout } from 'j7-easy-email-extensions'
import { useApiUrl, HttpError } from '@refinedev/core'
import type { UseFormReturnType } from '@refinedev/antd'
import type { TEmailRecord, TFormValues } from '@/pages/admin/Emails/types'
import type { FormInstance } from 'antd'
import parseJson from 'parse-json'
import { axiosInstance } from '@/rest-data-provider/utils'

import 'j7-easy-email-editor/lib/style.css'
import 'j7-easy-email-extensions/lib/style.css'

// theme, If you need to change the theme, you can make a duplicate in https://arco.design/themes/design/1799/setting/base/Color
import '@arco-themes/react-easy-email-theme/css/arco.css'

const initBlock = BlockManager.getBlockByType(BasicType.PAGE)!.create({})

function getInitContent(form: FormInstance, defaultBlock: IBlockData) {
	// 有可能是 物件 也可能是 stringfy 的 字串 (初始化時)
	const initContentString = form.getFieldValue(['short_description'])

	let initContent = defaultBlock
	if (initContentString && 'string' === typeof initContentString) {
		try {
			initContent = parseJson(initContentString) as any
		} catch (error) {
			console.log('parse JSON error: ', error)
		}
	}

	if ('object' === typeof initContentString) {
		initContent = initContentString
	}
	return initContent
}

/**
 * TODO 模板套用
 */

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
		TFormValues,
		TEmailRecord,
		TEmailRecord,
		HttpError
	>,
) => {
	const { form, query } = props

	const initialValues: IEmailTemplate = query?.isSuccess
		? {
				subject: form.getFieldValue(['name']),
				subTitle: '',
				content: getInitContent(form, initBlock),
			}
		: {
				subject: '',
				subTitle: '',
				content: initBlock,
			}

	const apiUrl = useApiUrl()

	return (
		<EmailEditorProvider
			data={initialValues}
			height={'calc(100vh - 15rem)'}
			dashed={false}
			onUploadImage={async (file) => {
				const res = await axiosInstance.post(
					`${apiUrl}/upload`,
					{
						files: file,
					},
					{
						headers: {
							'Content-Type': 'multipart/form-data;',
						},
					},
				)
				return res?.data?.data?.url
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
						<StandardLayout showSourceCode={false}>
							{query?.isLoading ? (
								<div className="p-8">
									<div className="h-[calc(100vh-15rem)] w-full bg-gray-100 animate-pulse" />
								</div>
							) : (
								<EmailEditor />
							)}
						</StandardLayout>
					</>
				)
			}}
		</EmailEditorProvider>
	)
}

export default memo(CustomEmailEditor)
