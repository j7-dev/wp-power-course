import { useEffect } from 'react'
import CourseSelector from './CourseSelector'
import { notification } from 'antd'
import { FileEncodeProgress, FileUploadProgress } from '@/components/general'
import { atom, useAtomValue } from 'jotai'
import { RcFile } from 'antd/lib/upload/interface'

export type TFileInQueue = {
	key: string
	file: RcFile
	status?: 'active' | 'normal' | 'exception' | 'success' | undefined
	videoId: string
	isEncoding: boolean
	encodeProgress: number
}

export const filesInQueueAtom = atom<TFileInQueue[]>([])
export const NOTIFICATION_API_KEY = 'upload-queue'

const index = () => {
	const filesInQueue = useAtomValue(filesInQueueAtom)

	const [notificationApi, contextHolder] = notification.useNotification({
		duration: 0,
		placement: 'bottomLeft',
		stack: { threshold: 1 },
	})

	useEffect(() => {
		if (!filesInQueue.length) {
			notificationApi.destroy(NOTIFICATION_API_KEY)
			return
		}
		notificationApi.info({
			key: NOTIFICATION_API_KEY,
			message: '上傳狀態',
			description: (
				<>
					{filesInQueue.map((fileInQueue) =>
						fileInQueue?.isEncoding ? (
							<FileEncodeProgress
								key={fileInQueue?.key}
								fileInQueue={fileInQueue}
							/>
						) : (
							<FileUploadProgress
								key={fileInQueue?.key}
								fileInQueue={fileInQueue}
							/>
						),
					)}
				</>
			),
		})
	}, [filesInQueue])

	return (
		<>
			{contextHolder}
			<CourseSelector />
		</>
	)
}

export default index
