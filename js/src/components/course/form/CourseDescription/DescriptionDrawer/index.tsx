import { useEffect, lazy, Suspense } from 'react'
import { Button, Form, Drawer, Input, Alert } from 'antd'
import { EditOutlined, LoadingOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { useApiUrl } from '@refinedev/core'
import { useBlockNote } from 'antd-toolkit'

const { Item } = Form
const BlockNote = lazy(() =>
	import('antd-toolkit').then((module) => ({
		default: module.BlockNote,
	})),
)

const DescriptionDrawer = () => {
	const apiUrl = useApiUrl()
	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)
	const { blockNoteViewProps, html } = useBlockNote({
		apiConfig: {
			apiEndpoint: `${apiUrl}/upload`,
			headers: new Headers({
				'X-WP-Nonce': window?.wpApiSettings?.nonce,
			}),
		},
	})
	const { editor } = blockNoteViewProps

	const { drawerProps, show, close, open } = useEditorDrawer()

	const handleConfirm = () => {
		form.setFieldValue(['description'], html)
		close()
	}

	useEffect(() => {
		if (watchId && open) {
			const description = form.getFieldValue(['description'])

			async function loadInitialHTML() {
				const blocks = await editor.tryParseHTMLToBlocks(description)
				editor.replaceBlocks(editor.document, blocks)
			}
			loadInitialHTML()
		}
	}, [watchId, open])

	return (
		<>
			<Button type="primary" icon={<EditOutlined />} onClick={show}>
				編輯課程重點介紹
			</Button>
			<Item name={['description']} label="課程重點介紹" hidden>
				<Input.TextArea rows={8} disabled />
			</Item>
			<Drawer
				{...drawerProps}
				extra={
					<Button type="primary" onClick={handleConfirm}>
						確認變更
					</Button>
				}
			>
				<Alert
					className="mb-4"
					message="注意事項"
					description={
						<ol className="pl-4">
							<li>
								確認變更只是確認內文有沒有變更，您還是需要儲存才會存進課程
							</li>
							<li>可以使用 WordPress shortcode</li>
							<li>圖片在前台顯示皆為 100% ，縮小圖片並不影響前台顯示</li>
							<li>未來有新功能持續擴充</li>
						</ol>
					}
					type="warning"
					showIcon
					closable
				/>
				<Suspense
					fallback={
						<Button type="text" icon={<LoadingOutlined />}>
							Loading...
						</Button>
					}
				>
					<BlockNote {...blockNoteViewProps} />
				</Suspense>
			</Drawer>
		</>
	)
}

export default DescriptionDrawer
