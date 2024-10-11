import { FC, useEffect, lazy, Suspense, useMemo, memo } from 'react'
import { Button, Form, Drawer, Input, Alert, Dropdown } from 'antd'
import { LoadingOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { useApiUrl } from '@refinedev/core'
import { useBlockNote } from '@/components/general'
import { siteUrl } from '@/utils'

const { Item } = Form

type TDescriptionDrawerProps = {
	name?: string | string[]
	itemLabel?: string
}
const DescriptionDrawerComponent: FC<TDescriptionDrawerProps | undefined> = (
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
	const { blockNoteViewProps, html, setHTML } = useBlockNote({
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

		if (!watchId && open) {
			setHTML('')
			editor.removeBlocks(editor.document)
		}
	}, [watchId, open])

	return (
		<>
			<p className="mb-2">
				編輯{itemLabel === '課程' ? '課程完整介紹' : `${itemLabel}內容`}
			</p>
			{itemLabel === '課程' ? (
				<Dropdown.Button
					trigger={['click']}
					placement="bottomLeft"
					menu={{
						items: [
							{
								key: 'elementor',
								label: (
									<a
										href={`${siteUrl}/wp-admin/post.php?post=${watchId}&action=elementor`}
										target="_blank"
										rel="noreferrer"
									>
										或 使用 Elementor 編輯器
									</a>
								),
							},
						],
					}}
					onClick={show}
				>
					使用 Notion 編輯器
				</Dropdown.Button>
			) : (
				<Button type="default" onClick={show}>
					使用 Notion 編輯器
				</Button>
			)}

			<Item name={name} label={`${itemLabel}完整介紹`} hidden>
				<Input.TextArea rows={8} disabled />
			</Item>
			<Drawer
				{...drawerProps}
				extra={
					<div className="flex gap-x-4">
						<Button
							type="default"
							danger
							onClick={() => {
								setHTML('')
								editor.removeBlocks(editor.document)
							}}
						>
							一鍵清空內容
						</Button>
						<Button type="primary" onClick={handleConfirm}>
							確認變更
						</Button>
					</div>
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

export const DescriptionDrawer = memo(DescriptionDrawerComponent)
