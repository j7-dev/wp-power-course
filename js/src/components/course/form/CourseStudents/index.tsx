import React, { Fragment } from 'react'
import UserSelector from './UserSelector'

export const CourseStudents = () => {
  return (
    <>
      <UserSelector />

      <p>默認排序:由新排到舊，最新的學員在最上方</p>
      <div className="bg-slate-100 w-full h-[40rem]">
        <div className="grid  gap-2 grid-cols-3 [&_div]:border [&_div]:border-solid [&_div]:border-gray-800">
          <div>學員名稱 + EMAIL</div>
          <div>觀看權限</div>
          <div>動作</div>

          {/* values */}
          {Array.from({ length: 10 }).map((_, i) => (
            <Fragment key={i}>
              <div>
                <p className="my-0 text-sm">j7devgg</p>
                <p className="my-0 text-xs text-gray-400">
                  j7.dev.gg@gmail.com
                </p>
              </div>
              <div>至 2024/12/31 (輸入框)</div>
              <div>[移除] </div>
            </Fragment>
          ))}
        </div>
      </div>
    </>
  )
}
