import { FC, useState, useEffect } from 'react'
import { FormItemProps, Select, Form } from 'antd'
import { TVideoType } from './types'
import Youtube from './Youtube'
import Vimeo from './Vimeo'
import Bunny from './Bunny'

export const VideoInput: FC<FormItemProps> = (formItemProps) => {
	const [videoType, setVideoType] = useState<TVideoType>('youtube')

	const handleChange = (value: string) => {
		setVideoType(value as TVideoType)
	}

	return (
		<>
			<Select
				defaultValue="youtube"
				value={videoType}
				className="w-full mb-1"
				size="small"
				onChange={handleChange}
				options={[
					{ value: 'youtube', label: 'Youtube 嵌入' },
					{ value: 'vimeo', label: 'Vimeo 嵌入' },
					{ value: 'bunny-stream-api', label: 'Bunny Stream API' },
				]}
			/>
			{videoType === 'youtube' && <Youtube {...formItemProps} />}
			{videoType === 'vimeo' && <Vimeo {...formItemProps} />}
			{videoType === 'bunny-stream-api' && <Bunny {...formItemProps} />}
		</>
	)
}
