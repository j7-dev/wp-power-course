import React, { useState, useRef } from 'react'
import '@vidstack/react/player/styles/default/theme.css'
import '@vidstack/react/player/styles/default/layouts/video.css'
import {
	MediaPlayer,
	MediaProvider,
	Poster,
	MediaPlayerInstance,
	// useStore,
} from '@vidstack/react'

import {
	defaultLayoutIcons,
	DefaultVideoLayout,
	DefaultAudioLayout,
} from '@vidstack/react/player/layouts/default'
import { WaterMark } from '@/components/general'

let showWatermark = false

export type TPlayerProps = {
	src: string
	thumbnail_url: string
	watermark_text: string
	watermark_qty: string
	watermark_color: string
	watermark_interval: string
}

const index = ({
	src,
	thumbnail_url,
	watermark_text,
	watermark_qty,
	watermark_color,
	watermark_interval,
}: TPlayerProps) => {
	const [isPlaying, setIsPlaying] = useState(false)
	const playerRef = useRef<MediaPlayerInstance>(null)
	// const { playing } = useStore(MediaPlayerInstance, playerRef)

	return (
		<>
			<MediaPlayer
				ref={playerRef}
				onTouchEnd={(e) => {
					if (!playerRef?.current) {
						return
					}
					e.preventDefault()
					e.stopPropagation()

					const remoteControl = playerRef.current.remoteControl
					remoteControl.togglePaused()
				}}
				src={src}
				viewType="video"
				streamType="on-demand"
				logLevel="warn"
				crossOrigin
				playsInline
				poster={thumbnail_url || undefined}
				posterLoad="eager"
				onPlaying={() => {
					setIsPlaying(true)
					showWatermark = true
				}}
				onPause={() => {
					setIsPlaying(false)
				}}
			>
				<MediaProvider>
					<Poster className="vds-poster" />
				</MediaProvider>
				<DefaultAudioLayout
					// smallLayoutWhen={false}
					icons={defaultLayoutIcons}
					colorScheme="dark"
				/>

				<DefaultVideoLayout
					// smallLayoutWhen={false}
					icons={defaultLayoutIcons}
					colorScheme="dark"
				/>

				<div
					className={`absolute w-full h-full top-0 left-0 ${showWatermark ? 'tw-block' : 'tw-hidden'}`}
				>
					<WaterMark
						interval={Number(watermark_interval)}
						qty={Number(watermark_qty)}
						text={watermark_text}
						isPlaying={isPlaying}
						style={{
							color: watermark_color,
						}}
					/>
				</div>
			</MediaPlayer>
		</>
	)
}

export default index
