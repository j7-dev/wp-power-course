export type TVideoType = 'youtube' | 'vimeo' | 'bunny-stream-api'

export type TVideo = {
	type: TVideoType
	id: string
	meta: {
		[key: string]: any
	}
}
