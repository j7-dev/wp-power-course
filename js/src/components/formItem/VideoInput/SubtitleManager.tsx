import { DeleteOutlined, UploadOutlined } from '@ant-design/icons'
import { useApiUrl } from '@refinedev/core'
import {
	Button,
	Select,
	Upload,
	message,
	Tag,
	Spin,
	Popconfirm,
	Empty,
} from 'antd'
import { FC, useState, useEffect, useCallback } from 'react'

import { TVideoSlot } from './types'

/** 字幕軌道資料 */
type TSubtitleTrack = {
	srclang: string
	label: string
	url: string
	attachment_id: number
}

/** SubtitleManager 元件 Props */
type TSubtitleManagerProps = {
	/** Post ID，用於呼叫字幕相關 API */
	postId: number
	/** Video slot 名稱，例如 chapter_video, feature_video, trial_video */
	videoSlot?: TVideoSlot
}

/** 可選語言列表 */
const SUBTITLE_LANGUAGES = [
	{ value: 'zh-TW', label: '繁體中文' },
	{ value: 'zh-CN', label: '简体中文' },
	{ value: 'en', label: 'English' },
	{ value: 'ja', label: '日本語' },
	{ value: 'ko', label: '한국어' },
	{ value: 'vi', label: 'Tiếng Việt' },
	{ value: 'th', label: 'ไทย' },
	{ value: 'id', label: 'Bahasa Indonesia' },
	{ value: 'ms', label: 'Bahasa Melayu' },
	{ value: 'fr', label: 'Français' },
	{ value: 'de', label: 'Deutsch' },
	{ value: 'es', label: 'Español' },
	{ value: 'pt', label: 'Português' },
	{ value: 'ru', label: 'Русский' },
	{ value: 'ar', label: 'العربية' },
	{ value: 'hi', label: 'हिन्दी' },
] as const

/**
 * 字幕管理元件
 * 提供字幕上傳、列表顯示與刪除功能
 * 用於 Bunny Stream 影片的字幕管理
 */
const SubtitleManager: FC<TSubtitleManagerProps> = ({
	postId,
	videoSlot = 'chapter_video',
}) => {
	const apiUrl = useApiUrl('power-course')

	const [subtitles, setSubtitles] = useState<TSubtitleTrack[]>([])
	const [loading, setLoading] = useState(false)
	const [uploading, setUploading] = useState(false)
	const [deletingLang, setDeletingLang] = useState<string | null>(null)
	const [selectedLang, setSelectedLang] = useState<string | undefined>(
		undefined
	)

	/** 字幕 API 的基礎路徑 */
	const subtitleBaseUrl = `${apiUrl}/posts/${postId}/subtitles/${videoSlot}`

	/** 取得 WP REST API Nonce */
	const getNonce = (): string => window?.wpApiSettings?.nonce || ''

	/** 取得已上傳的字幕列表，回傳最新資料供呼叫端使用 */
	const fetchSubtitles = useCallback(async (): Promise<TSubtitleTrack[]> => {
		setLoading(true)
		try {
			const response = await fetch(subtitleBaseUrl, {
				headers: {
					'X-WP-Nonce': getNonce(),
				},
				credentials: 'include',
			})

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`)
			}

			const data: TSubtitleTrack[] = await response.json()
			setSubtitles(data)
			return data
		} catch (_error) {
			message.error('無法載入字幕列表')
			return []
		} finally {
			setLoading(false)
		}
	}, [subtitleBaseUrl])

	/** 初始載入字幕列表 */
	useEffect(() => {
		fetchSubtitles()
	}, [fetchSubtitles])

	/** 已上傳語言的 Set，用於過濾可選語言 */
	const uploadedLangs = new Set(subtitles.map((s) => s.srclang))

	/** 過濾已上傳的語言，只顯示未上傳的語言選項 */
	const availableLanguages = SUBTITLE_LANGUAGES.filter(
		(lang) => !uploadedLangs.has(lang.value)
	)

	/** 根據最新字幕列表計算下一個可用語言 */
	const getNextAvailableLang = (
		currentSubtitles: TSubtitleTrack[]
	): string | undefined => {
		const uploaded = new Set(currentSubtitles.map((s) => s.srclang))
		const next = SUBTITLE_LANGUAGES.find((lang) => !uploaded.has(lang.value))
		return next?.value
	}

	/** 根據語言代碼取得語言 label */
	const getLangLabel = (srclang: string): string => {
		return (
			SUBTITLE_LANGUAGES.find((lang) => lang.value === srclang)?.label ||
			srclang
		)
	}

	/** 上傳字幕檔案 */
	const handleUpload = async (file: File) => {
		if (!selectedLang) return false

		setUploading(true)
		try {
			const formData = new FormData()
			formData.append('file', file)
			formData.append('srclang', selectedLang)

			const response = await fetch(subtitleBaseUrl, {
				method: 'POST',
				headers: {
					'X-WP-Nonce': getNonce(),
				},
				credentials: 'include',
				body: formData,
			})

			if (!response.ok) {
				const errorData = await response.json().catch(() => null)
				throw new Error(
					errorData?.message || `上傳失敗 (HTTP ${response.status})`
				)
			}

			message.success(`${getLangLabel(selectedLang)} 字幕上傳成功`)

			// 取得最新字幕列表，並自動切換至下一個可用語言
			const latestSubtitles = await fetchSubtitles()
			const nextLang = getNextAvailableLang(latestSubtitles)
			setSelectedLang(nextLang)
		} catch (error) {
			const errorMessage =
				error instanceof Error ? error.message : '字幕上傳失敗'
			message.error(errorMessage)
		} finally {
			setUploading(false)
		}

		// 回傳 false 阻止 Ant Design Upload 的預設上傳行為
		return false
	}

	/** 刪除指定語言的字幕 */
	const handleDelete = async (srclang: string) => {
		setDeletingLang(srclang)
		try {
			const response = await fetch(`${subtitleBaseUrl}/${srclang}`, {
				method: 'DELETE',
				headers: {
					'X-WP-Nonce': getNonce(),
				},
				credentials: 'include',
			})

			if (!response.ok) {
				throw new Error(`刪除失敗 (HTTP ${response.status})`)
			}

			message.success(`${getLangLabel(srclang)} 字幕已刪除`)
			await fetchSubtitles()
		} catch (_error) {
			message.error('字幕刪除失敗')
		} finally {
			setDeletingLang(null)
		}
	}

	return (
		<div className="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
			<h4 className="mb-3 text-sm font-semibold text-gray-700">字幕管理</h4>

			{/* 上傳區塊 */}
			<div className="mb-3 flex items-center gap-2">
				<Select
					className="w-40"
					size="small"
					value={selectedLang}
					onChange={setSelectedLang}
					options={availableLanguages}
					placeholder="請選擇字幕語言"
					disabled={availableLanguages.length === 0}
				/>
				<Upload
					accept=".srt,.vtt"
					showUploadList={false}
					beforeUpload={(file) => {
						handleUpload(file)
						return false
					}}
					disabled={
						uploading || availableLanguages.length === 0 || !selectedLang
					}
				>
					<Button
						size="small"
						icon={<UploadOutlined />}
						loading={uploading}
						disabled={availableLanguages.length === 0 || !selectedLang}
					>
						上傳字幕
					</Button>
				</Upload>
			</div>

			{/* 已上傳字幕列表 */}
			{loading && <Spin size="small" />}
			{!loading && subtitles.length > 0 && (
				<div className="flex flex-wrap gap-2">
					{subtitles.map((track) => (
						<Tag
							key={track.srclang}
							className="flex items-center gap-1 px-2 py-1"
						>
							<span className="text-xs">{getLangLabel(track.srclang)}</span>
							<Popconfirm
								title="確定要刪除此字幕嗎？"
								onConfirm={() => handleDelete(track.srclang)}
								okText="確定"
								cancelText="取消"
							>
								<Button
									type="text"
									size="small"
									danger
									icon={<DeleteOutlined />}
									loading={deletingLang === track.srclang}
									className="ml-1 h-auto p-0"
								/>
							</Popconfirm>
						</Tag>
					))}
				</div>
			)}
			{!loading && subtitles.length === 0 && (
				<Empty
					image={Empty.PRESENTED_IMAGE_SIMPLE}
					description="尚未上傳字幕"
					className="my-2"
				/>
			)}
		</div>
	)
}

export default SubtitleManager
