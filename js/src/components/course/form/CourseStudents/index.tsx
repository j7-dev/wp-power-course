import React, { Fragment } from 'react'
import UserSelector from './UserSelector'
import StudentTable from './StudentTable'
import { Alert } from 'antd'

export const CourseStudents = () => {
  return (
    <>
      <div className="mb-12">
        <div className="max-w-[30rem]">
          <Alert
            className="mb-4"
            message="注意事項"
            description={
              <ol>
                <li>請搜尋關鍵字尋找用戶(每次顯示20筆結果)</li>
                <li>此處的變更不需要點按儲存，變更立即生效</li>
              </ol>
            }
            type="warning"
            showIcon
          />
          <UserSelector />
        </div>
      </div>
      <StudentTable />
    </>
  )
}
