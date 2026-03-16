import React, { useState } from 'react'
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
import Ended from './Ended'
import { stringToBool } from 'antd-toolkit/wp'

let showWatermark = false

export type TPlayerProps = {
	src: string
	thumbnail_url: string
	watermark_text: string
	watermark_qty: string
	watermark_color: string
	watermark_interval: string
	next_post_url: string
	autoplay: 'yes' | 'no'
}

const index = ({
	src,
	thumbnail_url,
	watermark_text,
	watermark_qty,
	watermark_color,
	watermark_interval,
	next_post_url,
	autoplay,
}: TPlayerProps) => {
	const [isPlaying, setIsPlaying] = useState(false)
	const [isEnded, setIsEnded] = useState(false)
	// const playerRef = useRef<MediaPlayerInstance>(null)
	// const { playing } = useStore(MediaPlayerInstance, playerRef)

	return (
		<>
			<MediaPlayer
				// ref={playerRef}
				// onTouchEnd={(e) => {
				// 	if (!playerRef?.current) {
				// 		return
				// 	}
				// 	e.preventDefault()
				// 	e.stopPropagation()

				// 	const remoteControl = playerRef.current.remoteControl
				// 	remoteControl.togglePaused()
				// }}
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
				onEnded={() => setIsEnded(true)}
				autoPlay={stringToBool(autoplay)}
			>
				<MediaProvider>
					<Poster className="vds-poster" />
				</MediaProvider>
				<DefaultAudioLayout
					smallLayoutWhen={true}
					icons={defaultLayoutIcons}
					colorScheme="dark"
				/>

				<DefaultVideoLayout
					smallLayoutWhen={true}
					icons={defaultLayoutIcons}
					colorScheme="dark"
				/>

				{isEnded && <Ended next_post_url={next_post_url} />}

				{!isEnded && (
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
				)}
			</MediaPlayer>
		</>
	)
}

export default index
