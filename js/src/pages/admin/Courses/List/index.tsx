import Table from '@/pages/admin/Courses/List/Table'
import { atom } from 'jotai'
import { TCourseBaseRecord } from '@/pages/admin/Courses/List/types'
import { List } from '@refinedev/antd'

// TODO 有需要這個嗎?
// TODO  有空把 Item.*hidden 簡化一下
export const coursesAtom = atom<TCourseBaseRecord[]>([])

const CourseList = () => {
	return (
		<List>
			<Table />
		</List>
	)
}

export default CourseList
