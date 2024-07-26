import { FormItemProps } from 'antd'
import { FC } from 'react'
import { getYoutubeVideoId } from '@/utils'
import Iframe from './Iframe'

const Youtube: FC<FormItemProps> = (formItemProps) => {
	const getVideoUrl = (videoId: string | null) =>
		videoId ? `https://www.youtube.com/watch?v=${videoId}` : ''
	const getEmbedVideoUrl = (videoId: string | null) =>
		videoId ? `https://www.youtube.com/embed/${videoId}` : ''

	return (
		<Iframe
			type="youtube"
			formItemProps={formItemProps}
			getVideoId={getYoutubeVideoId}
			getEmbedVideoUrl={getEmbedVideoUrl}
			getVideoUrl={getVideoUrl}
			exampleUrl="https://www.youtube.com/watch?v=fqcPIPczRVA"
		/>
	)
}

export default Youtube
