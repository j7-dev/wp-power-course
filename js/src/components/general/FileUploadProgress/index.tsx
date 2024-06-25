import React, { FC, useEffect, useState } from 'react'
import { BiMoviePlay } from 'react-icons/bi'
import { Typography, Progress } from 'antd'
import { RcFile } from 'antd/lib/upload/interface'
import { getFileExtension, getEstimateUploadTimeInSeconds } from '@/utils'

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
  file: RcFile
  status?: 'active' | 'normal' | 'exception' | 'success' | undefined
}> = ({ file, status = 'active' }) => {
  const [percent, setPercent] = useState(0)

  // 估計上傳時間
  const estimatedTimeInSeconds = getEstimateUploadTimeInSeconds(file.size)

  // 每 3 秒增加 XX %
  const step = (100 / estimatedTimeInSeconds) * 3

  // TEST
  console.log('⭐  file:', {
    size: file.size,
    percent,
    step,
    estimatedTimeInSeconds,
  })

  useEffect(() => {
    // 用來模擬上傳進度

    if (['success'].includes(status)) {
      setPercent(100)
    }

    if (!file.size) {
      return
    }

    // 新的百分比
    const newPercent = percent + step

    // 如果新的百分比 >= 100 則返回
    if (newPercent >= 100) {
      return
    }

    // 設定定時器新的百分比
    const timer = setInterval(() => {
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
        {file.name}
      </Paragraph>
      <Progress percent={percent} status={status} />
    </>
  )
}
