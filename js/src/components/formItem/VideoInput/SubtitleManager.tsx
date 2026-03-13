import { UploadOutlined } from '@ant-design/icons'
import { useApiUrl, useCustom, useCustomMutation } from '@refinedev/core'
import {
	Button,
	Empty,
	List,
	Select,
	Space,
	Upload,
	UploadFile,
	message,
} from 'antd'
import { useState } from 'react'

import { PopconfirmDelete } from '@/components/general'

import { TSubtitleTrack } from './types'

type TSubtitleManagerProps = {
	chapterId: string
	videoType: string
}

type TSubtitleResponse = TSubtitleTrack[] | { data?: TSubtitleTrack[] }

const LANGUAGE_OPTIONS = [
	{ value: 'zh-TW', label: '繁體中文' },
	{ value: 'zh-CN', label: '简体中文' },
	{ value: 'en', label: 'English' },
	{ value: 'ja', label: '日本語' },
	{ value: 'ko', label: '한국어' },
	{ value: 'th', label: 'ไทย' },
	{ value: 'vi', label: 'Tiếng Việt' },
	{ value: 'fr', label: 'Français' },
	{ value: 'de', label: 'Deutsch' },
	{ value: 'es', label: 'Español' },
	{ value: 'pt', label: 'Português' },
	{ value: 'id', label: 'Bahasa Indonesia' },
	{ value: 'ms', label: 'Bahasa Melayu' },
	{ value: 'ar', label: 'العربية' },
]

const SubtitleManager = ({ chapterId, videoType }: TSubtitleManagerProps) => {
	const shouldRender = videoType === 'bunny-stream-api' && !!chapterId
	const apiUrl = useApiUrl('power-course')
	const [selectedLang, setSelectedLang] = useState<string>()
	const [file, setFile] = useState<File>()
	const [fileList, setFileList] = useState<UploadFile[]>([])

	const { data, isLoading, refetch } = useCustom<TSubtitleResponse>({
		url: `${apiUrl}/chapters/${chapterId}/subtitles`,
		method: 'get',
		queryOptions: {
			enabled: shouldRender,
		},
	})
	const { mutate, isLoading: isMutating } = useCustomMutation()

	const payload = data?.data
	const subtitles = Array.isArray(payload) ? payload : payload?.data || []
	const uploadedLangSet = new Set(subtitles.map((track) => track.srclang))
	const availableLanguages = LANGUAGE_OPTIONS.filter(
		(option) => !uploadedLangSet.has(option.value)
	)

	if (!shouldRender) {
		return null
	}

	const handleUpload = () => {
		if (!selectedLang || !file) {
			message.warning('請先選擇語言與字幕檔案')
			return
		}

		const formData = new FormData()
		formData.append('file', file)
		formData.append('srclang', selectedLang)

		mutate(
			{
				url: `${apiUrl}/chapters/${chapterId}/subtitles`,
				method: 'post',
				values: formData,
			},
			{
				onSuccess: () => {
					message.success('字幕上傳成功')
					setSelectedLang(undefined)
					setFile(undefined)
					setFileList([])
					refetch()
				},
				onError: () => {
					message.error('字幕上傳失敗')
				},
			}
		)
	}

	const handleDelete = (srclang: string) => {
		mutate(
			{
				url: `${apiUrl}/chapters/${chapterId}/subtitles/${srclang}`,
				method: 'delete',
				values: {},
			},
			{
				onSuccess: () => {
					message.success('字幕刪除成功')
					refetch()
				},
				onError: () => {
					message.error('字幕刪除失敗')
				},
			}
		)
	}

	return (
		<div className="mt-4 rounded-lg border border-gray-300 p-4">
			<p className="mb-3 text-sm font-medium">字幕管理</p>
			<List
				loading={isLoading}
				locale={{
					emptyText: (
						<Empty
							image={Empty.PRESENTED_IMAGE_SIMPLE}
							description="尚無字幕"
						/>
					),
				}}
				dataSource={subtitles}
				renderItem={(subtitle) => (
					<List.Item
						actions={[
							<PopconfirmDelete
								key={`delete-${subtitle.srclang}`}
								popconfirmProps={{
									title: `確認刪除 ${subtitle.label} 字幕嗎？`,
									onConfirm: () => handleDelete(subtitle.srclang),
								}}
								buttonProps={{
									size: 'small',
								}}
							/>,
						]}
					>
						{subtitle.label} ({subtitle.srclang})
					</List.Item>
				)}
			/>

			<Space className="mt-3" wrap>
				<Select
					size="small"
					className="min-w-48"
					placeholder="選擇字幕語言"
					value={selectedLang}
					options={availableLanguages}
					onChange={(value) => setSelectedLang(value)}
				/>
				<Upload
					maxCount={1}
					accept=".srt,.vtt"
					beforeUpload={(uploadFile) => {
						setFile(uploadFile)
						setFileList([uploadFile])
						return false
					}}
					fileList={fileList}
					onRemove={() => {
						setFile(undefined)
						setFileList([])
						return true
					}}
				>
					<Button size="small" icon={<UploadOutlined />}>
						選擇字幕檔案
					</Button>
				</Upload>
				<Button
					size="small"
					type="primary"
					loading={isMutating}
					onClick={handleUpload}
					disabled={!selectedLang || !file}
				>
					上傳字幕
				</Button>
			</Space>
		</div>
	)
}

export default SubtitleManager
