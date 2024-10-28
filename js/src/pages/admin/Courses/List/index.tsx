import Table from '@/pages/admin/Courses/List/Table'
import { List } from '@refinedev/antd'

// TODO  有空把 Item.*hidden 簡化一下

const CourseList = () => {
	return (
		<List>
			<Table />
		</List>
	)
}

export default CourseList
