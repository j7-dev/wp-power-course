import { useState, useEffect, useRef } from 'react'
import { DrawerProps, Button, FormInstance, Popconfirm } from 'antd'
import { useCreate, useUpdate, useInvalidate } from '@refinedev/core'
import {
  TChapterRecord,
  TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import { toFormData } from 'axios'
import { selectedRecordAtom } from '@/pages/admin/Courses/CourseSelector'
import { useAtom } from 'jotai'
import { isEqual } from 'lodash-es'

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
  const closeRef = useRef<HTMLDivElement>(null)
  const [unsavedChangesCheck, setUnsavedChangesCheck] = useState(true) // 是否檢查有未儲存的變更

  // const isChapter = resource === 'chapters'
  const invalidate = useInvalidate()

  const show = (theRecord?: TCourseRecord | TChapterRecord) => () => {
    setRecord(theRecord)
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
              setUnsavedChangesCheck(false)
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
              setOpen(false)
              form.resetFields()
              invalidateCourse()
              setUnsavedChangesCheck(false)
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
        <Button
          type="primary"
          onClick={handleSave}
          loading={isUpdate ? isLoadingUpdate : isLoadingCreate}
        >
          儲存
        </Button>
      </div>
    ),
    ...drawerProps,
  }

  useEffect(() => {
    if (record?.id) {
      // update
      form.setFieldsValue(record)
      setUnsavedChangesCheck(true)
    } else {
      // create
      form.resetFields()
      setUnsavedChangesCheck(false)
    }
  }, [record])

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
