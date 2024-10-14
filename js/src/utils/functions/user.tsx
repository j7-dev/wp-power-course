import { TAVLCourse } from '@/pages/admin/Courses/CourseSelector/types'

/**
 * 取得最大公約數的 TAVLCourse
 *
 * @param {TAVLCourse[][]} items
 * @return TAVLCourse[]
 */
export const getGCDCourse = (items: TAVLCourse[][]): TAVLCourse[] => {
	if (items.length === 0) return []

	// sort by items length asc
	const sortedItems = items.sort((a, b) => a.length - b.length)
	if (sortedItems[0].length === 0) return []
	const firstItemCourseIds = sortedItems[0].map((course) => course.id)

	const gcdCourseIds: string[] = []
	firstItemCourseIds.forEach((courseId) => {
		if (
			sortedItems.every((item) => item.some((course) => course.id === courseId))
		) {
			gcdCourseIds.push(courseId)
		}
	})
	const gcdCourses = gcdCourseIds
		.map((id) => {
			return sortedItems[0].find((course) => course.id === id)
		})
		.filter((course) => course !== undefined)

	return gcdCourses
}
