import { FC } from 'react'
import defaultImage from '@/assets/images/defaultImage.jpg'

export const SimpleImage: FC<{
	className?: string
	src: string
}> = ({ className = 'w-full aspect-video', src = defaultImage }) => {
	return (
		<div className={`relative ${className}`}>
			<img
				src={src}
				loading="lazy"
				className="relative z-20 w-full h-full object-cover"
			/>
			<div className="absolute z-10 top-0 left-0 w-full h-full bg-gray-200 animate-pulse flex items-center justify-center text-2xl text-gray-500 font-bold">
				Loading...
			</div>
		</div>
	)
}
