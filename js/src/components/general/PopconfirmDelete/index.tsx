import React, { FC } from 'react'
import { Button, Popconfirm, PopconfirmProps, ButtonProps } from 'antd'
import { DeleteOutlined } from '@ant-design/icons'

type PopconfirmDeleteProps = {
	popconfirmProps: Omit<PopconfirmProps, 'title'> & { title?: React.ReactNode }
	type?: 'icon' | 'button'
	buttonProps?: ButtonProps
}

const DEFAULT_PROPS: PopconfirmProps = {
	title: '確認刪除嗎?',
	okText: '確認',
	cancelText: '取消',
}

export const PopconfirmDelete: FC<PopconfirmDeleteProps> = ({
	popconfirmProps,
	type = 'icon',
	buttonProps,
}) => {
	const mergedPopconfirmProps: PopconfirmProps = {
		...DEFAULT_PROPS,
		...popconfirmProps,
	}

	return (
		<Popconfirm {...mergedPopconfirmProps}>
			{'icon' === type && (
				<Button danger type="link" icon={<DeleteOutlined />} {...buttonProps} />
			)}

			{'button' === type && (
				<Button type="primary" danger {...buttonProps}>
					{buttonProps?.children ?? '刪除'}
				</Button>
			)}
		</Popconfirm>
	)
}
