import { FC, useEffect, lazy, Suspense, useMemo } from 'react'
import { Button, Form, Drawer, Input, Alert } from 'antd'
import { EditOutlined, LoadingOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { useApiUrl } from '@refinedev/core'
import { useBlockNote } from '@/components/general'

const { Item } = Form

type TDescriptionDrawerProps = {
	name?: string | string[]
	itemLabel?: string
}
export const DescriptionDrawer: FC<TDescriptionDrawerProps | undefined> = (
	props,
) => {
	const BlockNote = useMemo(
		() =>
			lazy(() =>
				import('@/components/general').then((module) => ({
					default: module.BlockNote,
				})),
			),
		[],
	)

	const name = props?.name || ['description']
	const itemLabel = props?.itemLabel || '課程'
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

	const { drawerProps, show, close, open } = useEditorDrawer({
		itemLabel,
	})

	const handleConfirm = () => {
		form.setFieldValue(name, html)
		close()
	}

	useEffect(() => {
		if (watchId && open) {
			const description = form.getFieldValue(name)

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
				編輯{itemLabel === '課程' ? '課程完整介紹' : `${itemLabel}內容`}
			</Button>
			<Item name={name} label={`${itemLabel}完整介紹`} hidden>
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
								確認變更只是確認內文有沒有變更，您還是需要儲存才會存進
								{itemLabel}
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
