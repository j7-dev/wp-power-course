import React from 'react'
import { UserTable } from '@/components/user'

const index = () => {
	return (
		<>
			<UserTable canGrantCourseAccess={true} />
		</>
	)
}

export default index
