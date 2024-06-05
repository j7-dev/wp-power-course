import React, { FC } from 'react'
import { Popconfirm, PopconfirmProps } from 'antd'
import { DeleteOutlined } from '@ant-design/icons'
import { AntdIconProps } from '@ant-design/icons/lib/components/AntdIcon'

export const PopConfirmDelete: FC<{
  popconfirmProps?: PopconfirmProps
  antdIconProps?: AntdIconProps
  children?: React.ReactNode
}> = ({ popconfirmProps, children, antdIconProps }) => {
  return (
    <>
      <Popconfirm
        title="確認執行刪除嗎?"
        okText="確認"
        cancelText="取消"
        {...popconfirmProps}
      >
        {children ? (
          children
        ) : (
          <DeleteOutlined className="text-red-500" {...antdIconProps} />
        )}
      </Popconfirm>
    </>
  )
}
