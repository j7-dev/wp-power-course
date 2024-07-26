import { FC } from 'react'
import { FormItemProps, Select, Form } from 'antd'
import Youtube from './Youtube'
import Vimeo from './Vimeo'
import Bunny from './Bunny'

const { Item } = Form

export const VideoInput: FC<FormItemProps> = (formItemProps) => {
	const { name, ...restFormItemProps } = formItemProps
	const form = Form.useFormInstance()
	const watchVideoType = Form.useWatch([...name, 'type'], form)

	const handleChange = () => {
		form.setFieldValue([...name, 'id'], '')
		form.setFieldValue([...name, 'meta'], {})
	}

	return (
		<>
			<Item
				{...restFormItemProps}
				name={[
					...name,
					'type',
				]}
				className="mb-1"
			>
				<Select
					className="w-full"
					size="small"
					onChange={handleChange}
					options={[
						{ value: 'youtube', label: 'Youtube 嵌入' },
						{ value: 'vimeo', label: 'Vimeo 嵌入' },
						{ value: 'bunny-stream-api', label: 'Bunny Stream API' },
					]}
				/>
			</Item>
			{watchVideoType === 'youtube' && <Youtube {...formItemProps} />}
			{watchVideoType === 'vimeo' && <Vimeo {...formItemProps} />}
			{watchVideoType === 'bunny-stream-api' && <Bunny {...formItemProps} />}
		</>
	)
}
