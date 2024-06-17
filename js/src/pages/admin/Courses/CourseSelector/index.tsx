import Main from '@/pages/admin/Courses/CourseSelector/Main'
import { atom } from 'jotai'
import {
  TCourseRecord,
  TChapterRecord,
} from '@/pages/admin/Courses/CourseSelector/types'

export const selectedRecordAtom = atom<
  TCourseRecord | TChapterRecord | undefined
>(undefined)

const index = () => {
  return (
    <>
      <Main />
    </>
  )
}

export default index
