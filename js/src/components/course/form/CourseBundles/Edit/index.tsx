import React, { memo, useEffect } from 'react'
import { Form, Switch } from 'antd'
import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { Edit, useForm } from '@refinedev/antd'
import { toFormData } from '@/utils'
import { ExclamationCircleFilled } from '@ant-design/icons'
import BundleForm from './BundleForm'
import dayjs, { Dayjs } from 'dayjs'
import { useAtom, useSetAtom, useAtomValue } from 'jotai'
import { selectedProductsAtom, courseAtom, bundleProductAtom } from './atom'

const EditBundleComponent = ({
	record,
	course,
}: {
	record: TBundleProductRecord
	course: TCourseRecord
}) => {
	const { id, name } = record

	const selectedProducts = useAtomValue(selectedProductsAtom)
	const [theCourse, setTheCourse] = useAtom(courseAtom)
	const setBundleProduct = useSetAtom(bundleProductAtom)

	// 初始化資料
	const { formProps, form, saveButtonProps, mutation, onFinish } =
		useForm<TBundleProductRecord>({
			action: 'edit',
			resource: 'bundle_products',
			id,
			redirect: false,
			queryOptions: {
				enabled: false,
			},
			invalidates: ['list'],
			warnWhenUnsavedChanges: true,
		})

	const watchStatus = Form.useWatch(['status'], form)

	useEffect(() => {
		form.setFieldsValue(record)
		setBundleProduct(record)
	}, [record])

	useEffect(() => {
		setTheCourse(course)
	}, [course])

	// 將 [] 轉為 '[]'，例如，清除原本分類時，如果空的，前端會是 undefined，轉成 formData 時會遺失
	const handleOnFinish = (
		values: Partial<TBundleProductRecord> & {
			bundle_type: 'bundle' | 'subscription'
			sale_date_range: [Dayjs | number, Dayjs | number]
		},
	) => {
		if (!selectedProducts?.length && values?.bundle_type === 'bundle') {
			return
		}
		form.validateFields().then(() => {
			const sale_date_range = values?.sale_date_range || [null, null]

			// 處理日期欄位 sale_date_range

			const date_on_sale_from =
				(sale_date_range[0] as any) instanceof dayjs
					? (sale_date_range[0] as Dayjs).unix()
					: sale_date_range[0]
			const date_on_sale_to =
				(sale_date_range[1] as any) instanceof dayjs
					? (sale_date_range[1] as Dayjs).unix()
					: sale_date_range[1]

			const formattedValues = {
				...values,

				// product_type: 'power_bundle_product', // 創建綑綁商品
				date_on_sale_from,
				date_on_sale_to,
				sale_date_range: undefined,
			}
			onFinish(toFormData(formattedValues))
		})
	}

	if (!theCourse) {
		return null
	}

	return (
		<Edit
			resource="bundle_products"
			recordItemId={id}
			breadcrumb={null}
			goBack={null}
			headerButtons={() => null}
			title={
				<>
					《編輯》 {name} <sub className="text-gray-500">#{id}</sub>
				</>
			}
			saveButtonProps={{
				...saveButtonProps,
				children: '儲存銷售方案',
				icon: null,
				loading: mutation?.isLoading,

				// disabled: !selectedProducts?.length,
			}}
			footerButtons={({ defaultButtons }) => (
				<>
					<div className="text-red-500 font-bold mr-8">
						<ExclamationCircleFilled /> 銷售方案是分開儲存的，編輯完成請記得儲存
					</div>

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
			<Form {...formProps} onFinish={handleOnFinish} layout="vertical">
				<BundleForm />
			</Form>
		</Edit>
	)
}

export const EditBundle = memo(EditBundleComponent)
