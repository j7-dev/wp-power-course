import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import {
  TChapterRecord,
  TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { head } from 'lodash-es'
import { toFormData } from 'axios'

export function useCourseFormDrawer({
  form,
  resource = 'courses',
  drawerProps,
}: {
  form: FormInstance
  resource?: string
  drawerProps?: DrawerProps
}) {
  const [open, setOpen] = useState(false)
  const [record, setRecord] = useState<
    TCourseRecord | TChapterRecord | undefined
  >(undefined)
  const isUpdate = !!record // 如果沒有傳入 record 就走新增課程，否則走更新課程
  const isChapter = resource === 'chapters'
  const invalidate = useInvalidate()

  const show = (theRecord?: TCourseRecord | TChapterRecord) => () => {
    setRecord(theRecord)
    setOpen(true)
  }

  const close = () => {
    setOpen(false)
  }

  const { mutate: create, isLoading: isLoadingCreate } = useCreate()
  const { mutate: update, isLoading: isLoadingUpdate } = useUpdate()

  const invalidateCourse = () => {
    if (resource === 'chapters') {
      invalidate({
        resource: 'courses',
        invalidates: ['list'],
      })
    }
  }

  const handleSave = () => {
    form.validateFields().then(() => {
      const values = form.getFieldsValue()
      const formattedValues = {
        ...values,
        status: values.status ? 'publish' : 'draft',
        is_free: values.is_free ? 'yes' : 'no',
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
              invalidateCourse()
            },
          },
        )
      }
    })
  }

  const itemLabel = getItemLabel(resource, record?.depth)

  const mergedDrawerProps: DrawerProps = {
    title: `${isUpdate ? '編輯' : '新增'}${itemLabel}`,
    forceRender: true,
    push: false,
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

function getItemLabel(resource: string, depth: number | undefined) {
  if (resource === 'courses') return '課程'
  return depth === 0 ? '章節' : '段落'
}
