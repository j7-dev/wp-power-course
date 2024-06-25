import React, { FC, useEffect, useState } from 'react'
import { BiMoviePlay } from 'react-icons/bi'
import { Typography, Progress } from 'antd'
import { getEstimateUploadTimeInSeconds } from '@/utils'
import { TFileInQueue } from '@/pages/admin/Courses'

const { Paragraph } = Typography

/**
  ⭐  file:
  uid: "rc-upload-1719294531448-7",
  name: "chrome_mhvwHxR3VI.mp4",
  lastModified: 1717750071360,
  webkitRelativePath: "",
  size: 3284342,
  type: "video/mp4"
 */

export const FileUploadProgress: FC<{
  fileInQueue: TFileInQueue
}> = ({ fileInQueue }) => {
  const [percent, setPercent] = useState(0)
  const { file, status = 'active', encodeProgress } = fileInQueue

  // 估計上傳時間
  const estimatedTimeInSeconds = getEstimateUploadTimeInSeconds(file.size)

  // 每 3 秒增加 XX %
  const step = (100 / estimatedTimeInSeconds) * 3

  useEffect(() => {
    // 用來模擬上傳進度
    let timer: any = null

    if (['success'].includes(status)) {
      setPercent(100)
      return () => {
        clearInterval(timer)
      }
    }

    if (!file.size) {
      return () => {
        clearInterval(timer)
      }
    }

    // 新的百分比
    const newPercent = percent + step

    // 如果新的百分比 >= 100 則返回
    if (newPercent >= 100) {
      return () => {
        clearInterval(timer)
      }
    }

    // 設定定時器新的百分比
    timer = setInterval(() => {
      setPercent(Number(newPercent.toFixed(1)))
    }, 3000)

    // 清除定時器
    return () => {
      clearInterval(timer)
    }
  }, [percent, file.size, status])

  return (
    <>
      <Paragraph className="mb-0 text-xs" ellipsis>
        <BiMoviePlay className="relative top-[2px] mr-1" />
        {100 === encodeProgress ? '影片已處理完畢' : '影片上傳中'} {file.name}
      </Paragraph>
      <Progress percent={percent} status={status} />
    </>
  )
}
