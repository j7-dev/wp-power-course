export type TAutoGrantCourseItem = {
	course_id?: number
	limit_type: 'unlimited' | 'fixed' | 'assigned'
	limit_value: number | null
	limit_unit: 'day' | 'month' | 'year' | 'timestamp' | null
}

export type TSettings = {
	auto_grant_courses?: TAutoGrantCourseItem[]
	[key: string]: any
}
