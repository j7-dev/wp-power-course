import { FC, useEffect, useState } from 'react'
import { Progress, Tooltip } from 'antd'
import { filesInQueueAtom, TFileInQueue } from '@/pages/admin/MediaLibrary'
import { useGetVideo } from '@/bunny'
import { useSetAtom } from 'jotai'
import { useInvalidate } from '@refinedev/core'
import { bunny_library_id } from '@/utils'

const REFETCH_INTERVAL = 10000

export const FileEncodeProgress: FC<{
	fileInQueue: TFileInQueue
}> = ({ fileInQueue }) => {
	const { status = 'active', videoId = '' } = fileInQueue
	const [enabled, setEnabled] = useState(true)
	const { data } = useGetVideo({
		videoId,
		queryOptions: {
			enabled: !!fileInQueue?.videoId && enabled,
			refetchInterval: REFETCH_INTERVAL,
		},
	})
	const invalidate = useInvalidate()

	const encodeProgress = data?.data?.encodeProgress || 0

	const setFilesInQueue = useSetAtom(filesInQueueAtom)

	useEffect(() => {
		if (encodeProgress >= 100) {
			setEnabled(false)

			setFilesInQueue((prev) =>
				prev.filter((theFileInQueue) => videoId !== theFileInQueue.videoId),
			)

			invalidate({
				dataProviderName: 'bunny-stream',
				resource: `${bunny_library_id}/videos`,
				invalidates: ['list'],
			})
		}
	}, [encodeProgress])

	const bunnyUrl = `https://dash.bunny.net/stream/244459/library/videos?videoId=${videoId}&page=1&search=${videoId}#noscroll`

	return (
		<>
			<Tooltip
				title={
					<>
						影片已上傳至 Bunny，Bunny 正在編碼中...您可以離開去做其他事情了，或
						<a href={bunnyUrl} target="_blank" rel="noreferrer">
							前往 Bunny
						</a>{' '}
						查看編碼狀態
					</>
				}
				className="m-2 text-xs"
			>
				Bunny 編碼中...
			</Tooltip>
			<Progress
				percent={encodeProgress}
				percentPosition={{ align: 'center', type: 'outer' }}
				status={status}
			/>
		</>
	)
}
