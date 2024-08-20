import { useEffect, memo } from 'react'
import { Button } from 'antd'
import { mediaLibraryAtom } from '@/pages/admin/Courses/atom'
import { useAtom } from 'jotai'
import { ReactCustomBlockRenderProps } from '@blocknote/react'
import {
	CustomBlockConfig,
	DefaultInlineContentSchema,
	DefaultStyleSchema,
} from '@blocknote/core'
import { TbSwitchHorizontal } from 'react-icons/tb'
import { bunny_library_id } from '@/utils'

export type TMediaLibraryButton = ReactCustomBlockRenderProps<
	CustomBlockConfig,
	DefaultInlineContentSchema,
	DefaultStyleSchema
>

const MediaLibraryButton = (props: TMediaLibraryButton) => {
	const [mediaLibrary, setMediaLibrary] = useAtom(mediaLibraryAtom)

	const vId = mediaLibrary.confirmedSelectedVideos?.[0]?.guid

	useEffect(() => {
		if (!vId) {
			return
		}

		props.editor.updateBlock(props.block, {
			type: 'bunnyVideo',
			props: { vId: vId as any },
		})
	}, [vId])

	if (!vId) {
		const handleOpenMediaLibrary = () => {
			setMediaLibrary((prev) => ({
				...prev,
				modalProps: {
					...prev.modalProps,
					open: true,
				},
				mediaLibraryProps: {
					...prev.mediaLibraryProps,
					selectedVideos: [],
				},
				name: undefined,
				form: undefined,
				confirmedSelectedVideos: [],
			}))
		}

		return (
			<Button
				size="small"
				type="primary"
				className=""
				onClick={handleOpenMediaLibrary}
			>
				開啟 Bunny 媒體庫
			</Button>
		)
	}

	const videoUrl = `https://iframe.mediadelivery.net/embed/${bunny_library_id}/${vId}?autoplay=false&loop=false&muted=false&preload=true&responsive=true`

	const handleDelete = () => {
		setMediaLibrary((prev) => ({
			...prev,
			modalProps: {
				...prev.modalProps,
				open: true,
			},
			mediaLibraryProps: {
				...prev.mediaLibraryProps,
				selectedVideos: [],
			},
			name: undefined,
			form: undefined,
			confirmedSelectedVideos: [],
		}))
	}

	return (
		<div className="relative aspect-video rounded-lg border border-dashed border-gray-300">
			<div className="absolute w-full h-full top-0 left-0 p-2">
				<div className="w-full h-full rounded-xl overflow-hidden">
					<div
						className="rounded-xl bg-gray-200 block"
						style={{
							position: 'relative',
							paddingTop: '56.25%',
						}}
					>
						<iframe
							className="border-0 absolute top-0 left-0 w-full h-full rounded-xl"
							src={videoUrl}
							loading="lazy"
							allow="encrypted-media;picture-in-picture;"
							allowFullScreen={true}
						></iframe>

						<div
							onClick={handleDelete}
							className="group absolute top-4 right-4 rounded-md w-12 h-12 bg-white shadow-lg flex justify-center items-center transition duration-300 hover:bg-primary cursor-pointer"
						>
							<TbSwitchHorizontal className="text-primary group-hover:text-white" />
						</div>
					</div>
				</div>
			</div>
		</div>
	)
}

export default memo(MediaLibraryButton)
