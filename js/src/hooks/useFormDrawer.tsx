import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import {
  TChapterRecord,
  TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'

export function useFormDrawer({
  form,
  resource = 'courses',
}: {
  form: FormInstance
  resource?: string
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
      }

      if (isUpdate) {
        update(
          {
            id: record?.id,
            resource,
            values: formattedValues,
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
            values: formattedValues,
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

  const drawerProps: DrawerProps = {
    title: `${isUpdate ? '編輯' : '新增'}${itemLabel}`,
    forceRender: true,
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
  }

  useEffect(() => {
    if (record?.id) {
      const { status, ...rest } = record
      form.setFieldsValue(rest)
      form.setFieldValue(['status'], status === 'publish')
    } else {
      form.resetFields()
    }
  }, [record?.id])

  return {
    open,
    setOpen,
    show,
    close,
    drawerProps,
  }
}

function getItemLabel(resource: string, depth: number | undefined) {
  if (resource === 'courses') return '課程'
  return depth === 0 ? '章節' : '段落'
}
