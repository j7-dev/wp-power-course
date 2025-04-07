import React from 'react'
import { Card as AntdCard, CardProps } from 'antd'

/**
 * @deprecated 從 antd-toolkit 1.3.107 版本開始已提供 Card 組件，建議改用該組件
 */
const Card = ({
	children,
	showCard = true,
	...props
}: CardProps & { showCard?: boolean }) => {
	return showCard ? <AntdCard {...props}>{children}</AntdCard> : children
}

export default Card
