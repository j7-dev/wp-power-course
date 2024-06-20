import React, { FC } from 'react'
import { Divider, Typography, TypographyProps } from 'antd'

const { Title } = Typography

export const Heading: FC<{
  children: React.ReactNode
  titleProps?: TypographyProps['Title']
}> = ({ children, titleProps }) => {
  return (
    <Divider orientation="left" orientationMargin={0} plain>
      <Title
        level={2}
        className="border-blue-400 font-bold text-lg pl-2"
        style={{
          borderLeft: '4px solid',
          lineHeight: '1',
        }}
        {...titleProps}
      >
        {children}
      </Title>
    </Divider>
  )
}
