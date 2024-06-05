import { FC } from 'react'
import { PopConfirmDelete } from '@/components/general'
import { TProductRecord } from '@/pages/admin/Courses/CourseSelector/types'

const DeleteProduct: FC<{
  record: TProductRecord
}> = ({ record }) => {
  return <PopConfirmDelete />
}

export default DeleteProduct
