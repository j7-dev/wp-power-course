import { FC, useEffect, lazy, Suspense, memo } from 'react'
import { Button, Form, Drawer, Input, Alert, Radio } from 'antd'
import { LoadingOutlined, ExportOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { useApiUrl } from '@refinedev/core'
import { useBlockNote } from '@/components/general'
import { siteUrl, ELEMENTOR_ENABLED } from '@/utils'

const { Item } = Form

const BlockNote = lazy(() =>
	import('@/components/general').then((module) => ({
		default: module.BlockNote,
	})),
)

type TDescriptionDrawerProps = {
	name?: string | string[]
	itemLabel?: string
}
const DescriptionDrawerComponent: FC<TDescriptionDrawerProps | undefined> = (
	props,
) => {
	const name = props?.name || ['description']
	const itemLabel = props?.itemLabel || '課程'
	const apiUrl = useApiUrl()
	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)
	const watchShowDescriptionTab =
		Form.useWatch(['show_description_tab'], form) === 'yes'
	const watchEditor = Form.useWatch(['editor'], form)

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

	const disableElementor =
		!ELEMENTOR_ENABLED || (itemLabel === '課程' && !watchShowDescriptionTab)

	return (
		<div className="max-w-[20rem]">
			<Item
				name={['editor']}
				label={`編輯${itemLabel === '課程' ? '課程完整介紹' : `${itemLabel}內容`}`}
				tooltip="切換編輯器可能導致資料遺失，請謹慎使用"
				help={getTooltipTitle(
					ELEMENTOR_ENABLED,
					watchShowDescriptionTab,
					itemLabel,
				)}
				className="mb-2"
			>
				<Radio.Group
					options={[
						{
							label: '使用 Power 編輯器',
							value: 'power-editor',
						},
						{
							label: '使用 Elementor 編輯器',
							value: 'elementor',
							disabled: disableElementor,
						},
					]}
					defaultValue="power-editor"
					optionType="button"
					buttonStyle="solid"
				/>
			</Item>

			<Button
				className="w-[20rem]"
				icon={<ExportOutlined />}
				iconPosition="end"
				onClick={() => {
					if ('power-editor' === watchEditor) {
						show()
						return
					}

					if ('elementor' === watchEditor) {
						window.open(
							`${siteUrl}/wp-admin/post.php?post=${watchId}&action=elementor`,
							'_blank',
						)
					}
				}}
				color="primary"
				variant="filled"
			>
				開始編輯
			</Button>

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
		</div>
	)
}

function getTooltipTitle(
	elementorInstalled: boolean,
	watchShowDescriptionTab: boolean,
	itemLabel: string,
) {
	if (!elementorInstalled) {
		return '您必須安裝並啟用 Elementor 外掛才可以使用 Elementor 編輯'
	}

	// 為課程時，必須要有啟用 ShowDescriptionTab 才可以用
	if (itemLabel === '課程' && !watchShowDescriptionTab) {
		return '您必須啟用「其他設定 》 顯示介紹」才可以使用 Elementor 編輯'
	}

	// 非課程不受限制
	return
}

export const DescriptionDrawer = memo(DescriptionDrawerComponent)
