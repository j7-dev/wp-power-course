import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import { toFormData } from 'axios'
import dayjs, { Dayjs } from 'dayjs'

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

  // const invalidateCourse = () => {
  //   if (resource === 'chapters') {
  //     invalidate({
  //       resource: 'courses',
  //       invalidates: ['list'],
  //     })
  //   }
  // }

  const handleSave = () => {
    form.validateFields().then(() => {
      const values = form.getFieldsValue()
      const sale_date_range = values?.sale_date_range || [null, null]

      const sale_from =
        sale_date_range[0] instanceof dayjs
          ? (sale_date_range[0] as Dayjs).unix()
          : sale_date_range[0]
      const sale_end =
        sale_date_range[1] instanceof dayjs
          ? (sale_date_range[1] as Dayjs).unix()
          : sale_date_range[1]

      const formattedValues = {
        ...values,
        status: values.status ? 'publish' : 'draft',
        product_type: 'power_bundle_product', // 創建綑綁商品
        sale_from,
        sale_end,
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
              // invalidateCourse()
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
              close()
              form.resetFields()

              // invalidateCourse()
            },
          },
        )
      }
    })
  }

  const mergedDrawerProps: DrawerProps = {
    title: `${isUpdate ? '編輯' : '新增'}銷售方案`,
    forceRender: false,
    mask: false,
    placement: 'left',
    onClose: close,
    open,
    width: '50%',
    extra: (
      <Button
        type="primary"
        onClick={handleSave}
        loading={isUpdate ? isLoadingUpdate : isLoadingCreate}
      >
        儲存
      </Button>
    ),
    ...drawerProps,
  }

  useEffect(() => {
    if (record?.id) {
      const { status, meta_data, ...rest } = record
      form.setFieldsValue(rest)
      form.setFieldValue(['status'], status === 'publish')

      Object.keys(meta_data || {}).forEach((key) => {
        // boolean 要特別處理

        if (['is_free'].includes(key)) {
          form.setFieldValue([key], meta_data?.[key] === 'yes')
        } else {
          form.setFieldValue([key], meta_data?.[key])
        }
      })
    } else {
      form.resetFields()
    }
  }, [record?.id])

  return {
    open,
    setOpen,
    show,
    close,
    drawerProps: mergedDrawerProps,
  }
}
