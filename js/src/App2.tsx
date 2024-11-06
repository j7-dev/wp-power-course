import React, { useState } from 'react'
import '@vidstack/react/player/styles/default/theme.css'
import '@vidstack/react/player/styles/default/layouts/video.css'
import { MediaPlayer, MediaProvider, Poster } from '@vidstack/react'
import {
	defaultLayoutIcons,
	DefaultVideoLayout,
} from '@vidstack/react/player/layouts/default'
import { Marquee } from '@/components/general'

const display_name = 'jerry liu'
const src = 'youtube/0rp3pP2Xwhs'
const title = 'Sprite Fight'
const thumbnail_url = 'https://i.ytimg.com/vi/pFk41rmWpRM/maxresdefault.jpg'
const MARQUEE_QTY = 3

const App2 = () => {
	const [isPlaying, setIsPlaying] = useState(false)
	return (
		<MediaPlayer
			src={src}
			viewType="video"
			streamType="on-demand"
			logLevel="warn"
			crossOrigin
			playsInline
			title={title}
			poster={thumbnail_url}
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
			<DefaultVideoLayout icons={defaultLayoutIcons} />

			<div
				className={`absolute h-full w-full top-0 left-0 ${isPlaying ? 'tw-block' : 'tw-hidden'}`}
			>
				<Marquee qty={MARQUEE_QTY} text={display_name} />
			</div>
		</MediaPlayer>
	)
}

export default App2
