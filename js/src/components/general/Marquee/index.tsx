import React, { memo, useState, useEffect } from 'react'
import { default as FastMarquee, MarqueeProps } from 'react-fast-marquee'

const getMarqueeProps = (
	style: React.CSSProperties,
	isPlaying: boolean,
	speed: number,
	top: number,
) => {
	const color = style?.color || 'rgba(255, 255, 255, 0.5)'
	return {
		play: isPlaying,
		speed,
		style: {
			position: 'absolute',
			width: '100%',
			top: `${top}%`,
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
	isPlaying,
}: {
	qty: number
	text: string
	style: React.CSSProperties
	isPlaying: boolean
}) => {
	const [randomProps, setRandomProps] = useState<[number, number][]>([]) // [speed, top][]

	useEffect(() => {
		// 初始化時，隨機出 qty 個 [speed, top]
		new Array(qty).fill(0).forEach((_, index) => {
			setRandomProps((prev) => [
				...prev,
				[getRandom(20, 180), getRandom(10, 90)],
			])
		})
	}, [])

	if (!text || !qty) {
		return null
	}

	return (
		<>
			{new Array(qty).fill(0).map((_, index) => (
				<FastMarquee
					key={index}
					{...getMarqueeProps(
						style,
						isPlaying,
						randomProps?.[index]?.[0],
						randomProps?.[index]?.[1],
					)}
				>
					{text}
				</FastMarquee>
			))}
		</>
	)
}

export const Marquee = memo(MarqueeComponent)

function getRandom(min = 20, max = 100) {
	return Math.floor(Math.random() * (max - min + 1)) + min
}
