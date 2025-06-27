import AnalyticsPage from '@/pages/admin/Analytics'
import { useCourse } from '@/pages/admin/Courses/Edit/hooks'

export const CourseAnalysis = () => {
	const course = useCourse()

	if (!course) return null

	return (
		<div className="py-8">
			<AnalyticsPage
				context="detail"
				initialQuery={{ product_includes: [course.id] }}
			/>
		</div>
	)
}
