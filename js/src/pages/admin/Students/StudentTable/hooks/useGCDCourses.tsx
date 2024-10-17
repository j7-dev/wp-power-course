import React, { useState, useEffect, FC } from 'react'
import { CheckCircleOutlined } from '@ant-design/icons'
import { Tag } from 'antd'
import { TAVLCourse } from '@/pages/admin/Courses/CourseTable/types'
import { getGCDCourse } from '@/utils'

const useGCDCourses = ({
	allUsersAVLCourses,
}: {
	allUsersAVLCourses: TAVLCourse[][]
}) => {
	const [selectedGCDs, setSelectedGCDs] = useState<string[]>([])

	// 取得最大公約數的課程
	const gcdCourses = getGCDCourse(allUsersAVLCourses)

	useEffect(() => {
		setSelectedGCDs([])
	}, [allUsersAVLCourses.length])

	const GcdCoursesTags: FC = () =>
		gcdCourses.map((course: TAVLCourse) => {
			const isSelected = selectedGCDs.includes(course.id)
			return (
				<Tag
					icon={isSelected ? <CheckCircleOutlined /> : undefined}
					color={isSelected ? 'processing' : 'default'}
					key={course.id}
					className="cursor-pointer"
					onClick={() => {
						if (isSelected) {
							setSelectedGCDs(selectedGCDs.filter((id) => id !== course.id))
						} else {
							setSelectedGCDs([...selectedGCDs, course.id])
						}
					}}
				>
					{course.name || '未知的課程名稱'}
				</Tag>
			)
		})
	return {
		selectedGCDs,
		setSelectedGCDs,
		gcdCourses,
		GcdCoursesTags,
	}
}

export default useGCDCourses
