import Main from '@/pages/admin/Courses/CourseSelector/Main'
import { atom } from 'jotai'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'

export const coursesAtom = atom<TCourseRecord[]>([])

const index = () => {
	return (
		<>
			<Main />
		</>
	)
}

export default index
