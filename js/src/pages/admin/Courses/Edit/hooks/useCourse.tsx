import { createContext, useContext } from 'react'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

export const CourseContext = createContext<TCourseRecord | undefined>(undefined)

export const useCourse = () => {
	const course = useContext(CourseContext)

	return course
}
