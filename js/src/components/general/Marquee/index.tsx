import React, { memo } from 'react'
import { default as FastMarquee, MarqueeProps } from 'react-fast-marquee'

const getMarqueeProps = (style?: React.CSSProperties) => {
	const color = style?.color || 'rgba(255, 255, 255, 0.5)'
	return {
		speed: getRandom(20, 180),
		style: {
			position: 'absolute',
			width: '100%',
			top: `${getRandom(10, 90)}%`,
			left: '0%',
			color,
			fontSize: '1.5rem',
			fontWeight: 'bold',
			pointerEvents: 'none',
		},
	} as MarqueeProps
}

export const MarqueeComponent = ({
	qty,
	text,
	style,
}: {
	qty: number
	text: string
	style?: React.CSSProperties
}) => {
	if (!text || !qty) {
		return null
	}

	return new Array(qty).fill(0).map((_, index) => (
		<FastMarquee key={index} {...getMarqueeProps(style)}>
			{text}
		</FastMarquee>
	))
}

export const Marquee = memo(MarqueeComponent)

function getRandom(min = 20, max = 100) {
	return Math.floor(Math.random() * (max - min + 1)) + min
}
