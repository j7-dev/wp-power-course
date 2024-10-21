import Table from '@/pages/admin/Courses/CourseTable/Table'
import { atom } from 'jotai'
import { TCourseRecord } from '@/pages/admin/Courses/CourseTable/types'

export const coursesAtom = atom<TCourseRecord[]>([])

const index = () => {
	return <Table />
}

export default index
