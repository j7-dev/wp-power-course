import React, { memo } from 'react'
import { default as FastMarquee, MarqueeProps } from 'react-fast-marquee'

const getMarqueeProps = () =>
	({
		speed: getRandom(20, 180),
		style: {
			position: 'absolute',
			width: '100%',
			top: `${getRandom(10, 90)}%`,
			left: '0%',
			color: 'rgba(255, 255, 255, 0.25)',
			fontSize: '1.5rem',
			fontWeight: 'bold',
		},
	}) as MarqueeProps

export const MarqueeComponent = ({
	qty,
	text,
}: {
	qty: number
	text: string
}) => {
	return new Array(qty).fill(0).map((_, index) => (
		<FastMarquee key={index} {...getMarqueeProps()}>
			{text}
		</FastMarquee>
	))
}

export const Marquee = memo(MarqueeComponent)

function getRandom(min = 20, max = 100) {
	return Math.floor(Math.random() * (max - min + 1)) + min
}
