import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance, Form } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import { toFormData } from '@/utils'
import dayjs, { Dayjs } from 'dayjs'
import { INCLUDED_PRODUCT_IDS_FIELD_NAME } from '@/components/course/form/CourseBundle/BundleForm'

export function useBundleFormDrawer({
  form,
  resource = 'products',
  drawerProps,
}: {
  form: FormInstance
  resource?: string
  drawerProps?: DrawerProps
}) {
  const [open, setOpen] = useState(false)
  const [record, setRecord] = useState<TProductRecord | undefined>(undefined)
  const isUpdate = !!record // 如果沒有傳入 record 就走新增，否則走更新
  const invalidate = useInvalidate()

  const show = (theRecord?: TProductRecord) => () => {
    setRecord(theRecord)
    setOpen(true)
  }

  const close = () => {
    setOpen(false)
  }

  const { mutate: create, isLoading: isLoadingCreate } = useCreate()
  const { mutate: update, isLoading: isLoadingUpdate } = useUpdate()

  const invalidateCourse = async () => {
    invalidate({
      resource: 'courses',
      invalidates: ['list'],
    })
    invalidate({
      resource: 'products',
      invalidates: ['list'],
    })
  }

  const handleSave = () => {
    form.validateFields().then(() => {
      const values = form.getFieldsValue()
      const sale_date_range = values?.sale_date_range || [null, null]

      // 處理日期欄位 sale_date_range

      const sale_from =
        sale_date_range[0] instanceof dayjs
          ? (sale_date_range[0] as Dayjs).unix()
          : sale_date_range[0]
      const sale_to =
        sale_date_range[1] instanceof dayjs
          ? (sale_date_range[1] as Dayjs).unix()
          : sale_date_range[1]

      const formattedValues = {
        ...values,
        product_type: 'power_bundle_product', // 創建綑綁商品
        sale_from,
        sale_to,
        sale_date_range: undefined,
      }

      const formData = toFormData(formattedValues)

      if (isUpdate) {
        update(
          {
            id: record?.id,
            resource,
            values: formData,
            meta: {
              headers: { 'Content-Type': 'multipart/form-data;' },
            },
          },
          {
            onSuccess: () => {
              invalidateCourse()
              close()
            },
          },
        )
      } else {
        create(
          {
            resource,
            values: formData,
            meta: {
              headers: { 'Content-Type': 'multipart/form-data;' },
            },
          },
          {
            onSuccess: () => {
              form.resetFields()
              invalidateCourse()
              close()
            },
          },
        )
      }
    })
  }

  const watchName = Form.useWatch(['name'], form) || '新方案'
  const watchId = Form.useWatch(['id'], form)
  const watchIncludedProductIds = (Form.useWatch(
    [INCLUDED_PRODUCT_IDS_FIELD_NAME],
    form,
  ) ?? []) as string[]
  const watchRegularPrice = Number(Form.useWatch(['regular_price'], form))
  const watchSalePrice = Number(Form.useWatch(['sale_price'], form))

  const mergedDrawerProps: DrawerProps = {
    title: `${isUpdate ? '編輯' : '新增'}銷售方案 - ${watchName} ${watchId ? `#${watchId}` : ''}`,
    forceRender: false,
    mask: true,
    placement: 'left',
    onClose: close,
    open,
    width: '50%',
    extra: (
      <Button
        type="primary"
        onClick={handleSave}
        loading={isUpdate ? isLoadingUpdate : isLoadingCreate}
        disabled={
          watchIncludedProductIds?.length < 2 ||
          watchSalePrice > watchRegularPrice
        }
      >
        儲存
      </Button>
    ),
    ...drawerProps,
  }

  useEffect(() => {
    if (record?.id) {
      form.setFieldsValue(record)
    } else {
      form.resetFields()
    }
  }, [record])

  return {
    record,
    open,
    setOpen,
    show,
    close,
    drawerProps: mergedDrawerProps,
  }
}
