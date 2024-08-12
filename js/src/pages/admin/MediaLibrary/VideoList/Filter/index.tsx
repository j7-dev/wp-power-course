import React, { useState } from 'react'
import { Input, InputProps, Button, Popconfirm, message } from 'antd'
import { TVideo } from '@/pages/admin/MediaLibrary/types'
import { useDelete, useInvalidate } from '@refinedev/core'
import { bunny_library_id } from '@/utils'

const { Search } = Input

const Filter = ({
	selectedVideos,
	setSelectedVideos,
	setSearch,
	loading,
	...inputProps
}: {
	selectedVideos: TVideo[]
	setSelectedVideos: React.Dispatch<React.SetStateAction<TVideo[]>>
	setSearch: React.Dispatch<React.SetStateAction<string>>
	loading?: boolean
} & InputProps) => {
	const [value, setValue] = useState('')
	const [isLoading, setIsLoading] = useState(false)
	const { mutate: deleteVideo } = useDelete()
	const invalidate = useInvalidate()

	const handleBulkDelete = () => {
		setIsLoading(true)
		selectedVideos.forEach((video, index) => {
			deleteVideo(
				{
					dataProviderName: 'bunny-stream',
					resource: `${bunny_library_id}/videos`,
					id: video.guid,
				},
				{
					onSuccess: () => {
						if (index === selectedVideos.length - 1) {
							message.success('影片已經全部刪除成功')
							setSelectedVideos([])
						}
					},
					onError: () => {
						message.error(`影片 ${video.title} #${video.guid} 刪除失敗`)
					},
					onSettled: () => {
						setIsLoading(false)
						if (index === selectedVideos.length - 1) {
							invalidate({
								dataProviderName: 'bunny-stream',
								resource: `${bunny_library_id}/videos`,
								invalidates: ['list'],
							})
						}
					},
				},
			)
		})
	}

	return (
		<div className="flex items-center justify-between">
			<Search
				placeholder="搜尋關鍵字"
				className="w-60 mb-4"
				value={value}
				onChange={(e) => setValue(e.target.value)}
				allowClear
				onSearch={() => setSearch(value)}
				enterButton
				loading={loading}
				{...inputProps}
			/>

			<div className="flex items-center gap-2">
				<p className="text-sm m-0 text-gray-500">
					已經選取 {selectedVideos?.length} 個影片
				</p>
				<Popconfirm
					title="確定要刪除這些影片嗎？"
					onConfirm={handleBulkDelete}
					okText="刪除"
					cancelText="取消"
				>
					<Button
						disabled={!selectedVideos?.length}
						loading={isLoading}
						type="primary"
						danger
					>
						批量刪除 ({selectedVideos?.length})
					</Button>
				</Popconfirm>
			</div>
		</div>
	)
}

export default Filter
