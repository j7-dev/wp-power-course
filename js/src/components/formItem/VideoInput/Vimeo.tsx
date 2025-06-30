import { FormItemProps } from 'antd'
import { FC } from 'react'
import { getVimeoVideoId } from '@/utils'
import Iframe from './Iframe'

const Vimeo: FC<FormItemProps> = (formItemProps) => {
	const getVideoUrl = (videoId: string | null) =>
		videoId ? `https://vimeo.com/${videoId}` : ''
	const getEmbedVideoUrl = (videoId: string | null) => {
		if (!videoId) return ''

		// videoId 以 / 拆開
		const [vId, hash] = videoId.split('/')

		return `https://player.vimeo.com/video/${vId}?h=${hash}&color=a6a8a8&title=0&byline=0&portrait=0`
	}

	return (
		<Iframe
			type="vimeo"
			formItemProps={formItemProps}
			getVideoId={getVimeoVideoId}
			getEmbedVideoUrl={getEmbedVideoUrl}
			getVideoUrl={getVideoUrl}
			exampleUrl="https://vimeo.com/900151069"
		/>
	)
}

export default Vimeo
