import React, { useState } from 'react'
import '@vidstack/react/player/styles/default/theme.css'
import '@vidstack/react/player/styles/default/layouts/video.css'
import { MediaPlayer, MediaProvider, Poster } from '@vidstack/react'
import {
	defaultLayoutIcons,
	DefaultVideoLayout,
	DefaultAudioLayout,
} from '@vidstack/react/player/layouts/default'
import { Marquee } from '@/components/general'

export type TApp2Props = {
	src: string
	marquee_text: string
	thumbnail_url: string
	marquee_qty: string
	marquee_color: string
}

const App2 = ({
	src,
	marquee_text,
	thumbnail_url,
	marquee_qty,
	marquee_color,
}: TApp2Props) => {
	const [isPlaying, setIsPlaying] = useState(false)
	return (
		<MediaPlayer
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
			}}
			onPause={() => {
				setIsPlaying(false)
			}}
		>
			<MediaProvider>
				<Poster className="vds-poster" />
			</MediaProvider>
			<DefaultAudioLayout icons={defaultLayoutIcons} colorScheme="dark" />
			<DefaultVideoLayout icons={defaultLayoutIcons} colorScheme="dark" />

			<div
				className={`absolute h-full w-full top-0 left-0 ${isPlaying ? 'tw-block' : 'tw-hidden'}`}
			>
				<Marquee
					qty={Number(marquee_qty)}
					text={marquee_text}
					style={{
						color: marquee_color,
					}}
				/>
			</div>
		</MediaPlayer>
	)
}

export default App2
