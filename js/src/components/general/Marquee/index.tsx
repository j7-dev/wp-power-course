import React, { memo, useState, useEffect } from 'react'
import { default as FastMarquee } from 'react-fast-marquee'
import { useWindowSize } from '@uidotdev/usehooks'
import { round } from 'lodash-es'

const baseSize = 1.5

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
	const { width } = useWindowSize()
	const fontSize = Math.max(round(baseSize * ((width || 1980) / 1980), 2), 0.75) // 至少 0.75rem

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
					play={isPlaying}
					speed={randomProps?.[index]?.[0]}
					style={{
						position: 'absolute',
						width: '100%',
						top: `${randomProps?.[index]?.[1]}%`,
						left: '0%',
						color: style?.color || 'rgba(255, 255, 255, 0.5)',
						fontSize: `${fontSize}rem`,
						fontWeight: 'bold',
						pointerEvents: 'none',
					}}
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
