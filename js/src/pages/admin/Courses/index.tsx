import CourseSelector from './CourseSelector'
import { mediaLibraryAtom } from '@/pages/admin/Courses/atom'
import { useAtom } from 'jotai'
import { MediaLibrary } from '@/bunny'
import { TVideo } from '@/bunny/MediaLibrary/types'
import { Modal } from 'antd'

const index = () => {
	const [mediaLibrary, setMediaLibrary] = useAtom(mediaLibraryAtom)
	const { modalProps, mediaLibraryProps, name, form } = mediaLibrary
	const { limit, selectedVideos } = mediaLibraryProps

	const selectedVideosSetter = (
		videosOrFunction: TVideo[] | ((_videos: TVideo[]) => TVideo[]),
	) => {
		if (typeof videosOrFunction === 'function') {
			const newVideos = videosOrFunction(selectedVideos)
			setMediaLibrary((prev) => ({
				...prev,
				mediaLibraryProps: {
					...prev.mediaLibraryProps,
					selectedVideos: newVideos,
				},
			}))
		} else {
			setMediaLibrary((prev) => ({
				...prev,
				mediaLibraryProps: {
					...prev.mediaLibraryProps,
					selectedVideos: videosOrFunction,
				},
			}))
		}
	}

	return (
		<>
			<CourseSelector />
			<Modal
				{...modalProps}
				onCancel={() => {
					setMediaLibrary((prev) => ({
						...prev,
						modalProps: {
							...prev.modalProps,
							open: false,
						},
					}))
				}}
			>
				<div className="max-h-[75vh] overflow-x-hidden overflow-y-auto pr-4">
					<MediaLibrary
						limit={limit}
						selectedVideos={selectedVideos}
						setSelectedVideos={selectedVideosSetter}
						selectButtonProps={{
							onClick: () => {
								setMediaLibrary((prev) => ({
									...prev,
									modalProps: {
										...prev.modalProps,
										open: false,
									},
								}))
								if (form) {
									form.setFieldValue(name, {
										type: 'bunny-stream-api',
										id: selectedVideos?.[0]?.guid || '',
										meta: {},
									})
								}
							},
						}}
					/>
				</div>
			</Modal>
		</>
	)
}

export default index
