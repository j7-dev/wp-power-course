import { FC, useEffect, lazy, Suspense, memo } from 'react'
import { Button, Form, Drawer, Alert, Radio } from 'antd'
import { LoadingOutlined, ExportOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { useApiUrl, useUpdate, useInvalidate } from '@refinedev/core'
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
	resource?: string
	initEditor?: 'power-editor' | 'elementor' | ''
}
const DescriptionDrawerComponent: FC<TDescriptionDrawerProps> = ({
	name = ['description'],
	initEditor = 'power-editor',
	resource = 'courses',
}: TDescriptionDrawerProps) => {
	const itemLabel = resource === 'courses' ? '課程' : '章節'

	const apiUrl = useApiUrl()
	const form = Form.useFormInstance()
	const watchId = Form.useWatch(['id'], form)
	const watchShowDescriptionTab =
		Form.useWatch(['show_description_tab'], form) === 'yes'
	const watchEditor = Form.useWatch(['editor'], form)
	const invalidate = useInvalidate()

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

	const { mutate: update, isLoading } = useUpdate({
		resource: 'posts',
		dataProviderName: 'powerhouse',
	})

	const handleSaveContent = () => {
		update(
			{
				id: watchId,
				values: {
					description: html,
					editor: watchEditor,
				},
			},
			{
				onSuccess: () => {
					if (resource === 'courses') {
						invalidate({
							resource,
							invalidates: ['detail'],
							id: watchId,
						})
					} else {
						invalidate({
							resource,
							invalidates: ['list'],
						})
					}
					close()
				},
			},
		)
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
		<div style={{ maxWidth: '30rem' }}>
			<Item
				name={['editor']}
				label={`編輯${itemLabel === '課程' ? '課程完整介紹' : `${itemLabel}內容`}`}
				tooltip={getTooltipTitle(
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
					block
				/>
			</Item>

			<Button
				disabled={watchEditor !== initEditor && watchEditor === 'elementor'}
				className="w-full"
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
			{watchEditor !== initEditor && watchEditor === 'elementor' && (
				<p className="text-sm text-red-400">
					先儲存後就可以用 Elementor 編輯了
				</p>
			)}

			<Item name={name} hidden />
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
						<Button
							type="primary"
							onClick={handleSaveContent}
							loading={isLoading}
						>
							儲存
						</Button>
					</div>
				}
			>
				<Alert
					className="mb-4"
					message="注意事項"
					description={
						<ol className="pl-4">
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
}

export const DescriptionDrawer = memo(DescriptionDrawerComponent)
