import { FC } from 'react'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { Form, DatePicker } from 'antd'
import dayjs, { Dayjs } from 'dayjs'

export const UserWatchLimit: FC<{
  record: TUserRecord
}> = ({ record }) => {
  const { avl_courses } = record
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)

  const currentCourse = avl_courses.find((course) => course.id === watchId)

  if (!currentCourse) return <>出錯了！找不到課程</>

  const expireDate = currentCourse?.expire_date
    ? dayjs.unix(Number(currentCourse?.expire_date))
    : undefined

  const handleUpdate = (value: Dayjs, dateString: string | string[]) => {}

  return (
    <>
      <DatePicker
        value={expireDate}
        showTime
        format="YYYY-MM-DD HH:mm"
        onChange={handleUpdate}
      />
    </>
  )
}
