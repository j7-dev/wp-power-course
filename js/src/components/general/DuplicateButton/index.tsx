import { FC, memo } from 'react'
import { CopyOutlined } from '@ant-design/icons'
import { Button, Tooltip, TooltipProps } from 'antd'
import {
	useCustomMutation,
	useApiUrl,
	useInvalidate,
	UseInvalidateProp,
} from '@refinedev/core'

const DuplicateButtonComponent: FC<{
	id: string
	tooltipProps?: TooltipProps
	invalidateProps: Omit<UseInvalidateProp, 'invalidates'>
}> = ({ id, invalidateProps, tooltipProps }) => {
	const { mutate: duplicate, isLoading } = useCustomMutation()
	const apiUrl = useApiUrl()
	const invalidate = useInvalidate()

	const handleDuplicate = () => {
		duplicate(
			{
				url: `${apiUrl}/duplicate/${id}`,
				method: 'post',
				values: {},
			},
			{
				onSuccess: (data, variables, context) => {
					invalidate({
						invalidates: ['list'],
						...invalidateProps,
					})
				},
			},
		)
	}

	return (
		<>
			<Tooltip title="複製" {...tooltipProps}>
				<Button
					type="text"
					className="text-gray-500"
					icon={<CopyOutlined />}
					onClick={handleDuplicate}
					loading={isLoading}
				/>
			</Tooltip>
		</>
	)
}

export const DuplicateButton = memo(DuplicateButtonComponent)
