import { useState, useEffect, useRef } from 'react'
import {
	DrawerProps,
	Button,
	FormInstance,
	Popconfirm,
	Switch,
	Form,
	message,
} from 'antd'
import { useUpdate } from '@refinedev/core'
import { TChapterRecord, TCourseRecord } from '@/pages/admin/Courses/List/types'
import { toFormData } from '@/utils'
import { isEqual } from 'lodash-es'
import { useWindowSize } from '@uidotdev/usehooks'

// DELETE
export function useCourseFormDrawer({
	form,
	resource = 'courses',
	drawerProps,
}: {
	form: FormInstance
	resource?: string
	drawerProps?: DrawerProps
}) {
	const [record, setRecord] = useState<
		TCourseRecord | TChapterRecord | undefined
	>(undefined)
	const [open, setOpen] = useState(false)
	const closeRef = useRef<HTMLDivElement>(null)
	const [unsavedChangesCheck, setUnsavedChangesCheck] = useState(true) // 是否檢查有未儲存的變更
	const [publish, setPublish] = useState(true)
	const { width } = useWindowSize()

	const show = (theRecord?: TCourseRecord | TChapterRecord) => () => {
		setRecord({ ...theRecord } as TCourseRecord | TChapterRecord)
		setOpen(true)
	}

	const close = () => {
		if (!unsavedChangesCheck) {
			setOpen(false)
			return
		}

		// 與原本的值相比是否有變更
		const newValues = form.getFieldsValue()
		const fieldNames = Object.keys(newValues).filter(
			(fieldName) => !['files'].includes(fieldName),
		)
		const isEquals = fieldNames.every((fieldName) => {
			const originValue = record?.[fieldName as keyof typeof record]
			const newValue = newValues[fieldName]

			return isEqual(originValue, newValue)
		})

		if (!isEquals) {
			closeRef?.current?.click()
		} else {
			setOpen(false)
		}
	}

	const { mutate: update, isLoading: isLoadingUpdate } = useUpdate({
		resource,
		invalidates: ['list'],
		meta: {
			headers: { 'Content-Type': 'multipart/form-data;' },
		},
	})

	const handleSave = () => {
		form
			.validateFields()
			.then(() => {
				const values = form.getFieldsValue()
				const formData = toFormData(values)

				update(
					{
						id: record!.id,
						values: formData,
					},
					{
						onSuccess: () => {
							setUnsavedChangesCheck(false)
						},
					},
				)
			})
			.catch((error) => {
				const { errorFields } = error
				errorFields?.forEach((field: any) => {
					field?.errors?.forEach((msg: string) => {
						message.warning(msg)
					})
				})
			})
	}

	const itemLabel = getItemLabel(resource, record?.depth)
	const watchName = Form.useWatch(['name'], form) || '新課程'
	const watchId = Form.useWatch(['id'], form)

	const mergedDrawerProps: DrawerProps = {
		title: `編輯${itemLabel} - ${watchName} ${watchId ? `#${watchId}` : ''}`,
		forceRender: false,
		push: false,
		onClose: close,
		open,
		width: (width || 1980) > 1440 ? '50%' : 'calc(100% - 200px)',
		extra: (
			<div className="flex gap-6 items-center">
				<Switch
					checkedChildren="發佈"
					unCheckedChildren="草稿"
					value={publish}
					onChange={(checked) => setPublish(checked)}
				/>
				<div className="flex">
					<Popconfirm
						title="你儲存了嗎?"
						description="確認關閉後，你的編輯可能會遺失，請確認操作"
						placement="leftTop"
						okText="確認關閉"
						cancelText="取消"
						onConfirm={() => {
							setOpen(false)
						}}
					>
						<p ref={closeRef} className="">
							&nbsp;
						</p>
					</Popconfirm>
					<Button type="primary" onClick={handleSave} loading={isLoadingUpdate}>
						儲存
					</Button>
				</div>
			</div>
		),
		...drawerProps,
	}

	useEffect(() => {
		form.setFieldsValue(record)
		setUnsavedChangesCheck(true)
		setPublish(record?.status === 'publish')
	}, [record])

	useEffect(() => {
		form.setFieldValue(['status'], publish ? 'publish' : 'draft')
	}, [publish])

	return {
		open,
		setOpen,
		show,
		close,
		drawerProps: mergedDrawerProps,
	}
}

function getItemLabel(resource: string, depth: number | undefined) {
	if (resource === 'courses') return '課程'
	return depth === 0 ? '章節' : '單元'
}
