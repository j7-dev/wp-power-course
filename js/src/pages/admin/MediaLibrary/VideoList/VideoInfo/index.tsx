import React, { useState } from 'react'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import { bunny_cdn_hostname } from '@/utils'
import { SimpleImage } from '@/components/general'
import { ObjectTable, CopyText } from 'antd-toolkit'
import { Button } from 'antd'
import { CopyOutlined } from '@ant-design/icons'

const VideoInfo = ({ video }: { video: TVideo }) => {
	const { guid, videoLibraryId } = video
	const iframeText = `<div style="position:relative;padding-top:56.25%;"><iframe src="https://iframe.mediadelivery.net/embed/${videoLibraryId}/${guid}?autoplay=true&loop=false&muted=false&preload=true&responsive=true" loading="lazy" style="border:0;position:absolute;top:0;height:100%;width:100%;" allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" allowfullscreen="true"></iframe></div>`

	return (
		<>
			<SimpleImage
				className="w-full aspect-video rounded-md overflow-hidden"
				loadingClassName="text-sm text-gray-500 font-bold"
				src={`https://${bunny_cdn_hostname}/${guid}/preview.webp`}
			/>
			<CopyText text={iframeText}>
				<Button
					type="primary"
					className="my-4"
					icon={<CopyOutlined />}
					iconPosition="end"
				>
					複製 iframe 影片嵌入代碼
				</Button>
			</CopyText>
			<ObjectTable record={video} />
			<ObjectTable record={video} />
		</>
	)
}

export default VideoInfo
