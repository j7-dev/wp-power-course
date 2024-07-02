import { useState, useEffect } from 'react'
import { DrawerProps, Button, FormInstance } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import {
  TChapterRecord,
  TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { toFormData } from 'axios'
import { selectedRecordAtom } from '@/pages/admin/Courses/CourseSelector'
import { useAtom } from 'jotai'

export function useCourseFormDrawer({
  form,
  resource = 'courses',
  drawerProps,
}: {
  form: FormInstance
  resource?: string
  drawerProps?: DrawerProps
}) {
  const [record, setRecord] = useAtom(selectedRecordAtom)
  const [open, setOpen] = useState(false)
  const isUpdate = !!record // 如果沒有傳入 record 就走新增課程，否則走更新課程
  // const isChapter = resource === 'chapters'
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

      const formData = toFormData(values)

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
      form.setFieldsValue(record)
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
