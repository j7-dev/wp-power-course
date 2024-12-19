import { FC, memo } from 'react'
import { CopyOutlined } from '@ant-design/icons'
import { Button, Tooltip } from 'antd'
import { useCustomMutation, useApiUrl, useInvalidate } from '@refinedev/core'

const DuplicateButtonComponent: FC<{
	id: string
}> = ({ id }) => {
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
						resource: 'chapters',
						invalidates: ['list'],
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
