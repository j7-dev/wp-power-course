import Table from '@/pages/admin/Courses/List/Table'
import { atom } from 'jotai'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'
import { List } from '@refinedev/antd'

export const coursesAtom = atom<TCourseRecord[]>([])

const CourseList = () => {
	return (
		<List>
			<Table />
		</List>
	)
}

export default CourseList
