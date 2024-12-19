import { FC, memo } from 'react'
import { CopyOutlined } from '@ant-design/icons'
import { Button, Tooltip } from 'antd'
import {
	useCustomMutation,
	useApiUrl,
	useInvalidate,
	UseInvalidateProp,
} from '@refinedev/core'

const DuplicateButtonComponent: FC<{
	id: string
	invalidateProps: UseInvalidateProp
}> = ({ id, invalidateProps }) => {
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
						// @ts-ignore
						invalidates: ['list'],
						...invalidateProps,
					})
				},
			},
		)
	}

	return (
		<>
			<Tooltip title="複製">
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
