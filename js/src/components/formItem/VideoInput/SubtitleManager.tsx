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

/** 字幕軌道資料 */
type TSubtitleTrack = {
	srclang: string
	label: string
	url: string
	attachment_id: number
}

/** SubtitleManager 元件 Props */
type TSubtitleManagerProps = {
	/** 章節 ID，用於呼叫字幕相關 API */
	chapterId: number
}

/** 可選語言列表 */
const LANGUAGES = [
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
const SubtitleManager: FC<TSubtitleManagerProps> = ({ chapterId }) => {
	const apiUrl = useApiUrl('power-course')

	const [subtitles, setSubtitles] = useState<TSubtitleTrack[]>([])
	const [loading, setLoading] = useState(false)
	const [uploading, setUploading] = useState(false)
	const [deletingLang, setDeletingLang] = useState<string | null>(null)
	const [selectedLang, setSelectedLang] = useState<string>('zh-TW')

	/** 字幕 API 的基礎路徑 */
	const subtitleBaseUrl = `${apiUrl}/chapters/${chapterId}/subtitles`

	/** 取得 WP REST API Nonce */
	const getNonce = (): string => window?.wpApiSettings?.nonce || ''

	/** 取得已上傳的字幕列表 */
	const fetchSubtitles = useCallback(async () => {
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
		} catch (_error) {
			message.error('無法載入字幕列表')
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
	const availableLanguages = LANGUAGES.filter(
		(lang) => !uploadedLangs.has(lang.value)
	)

	/** 根據語言代碼取得語言 label */
	const getLangLabel = (srclang: string): string => {
		return LANGUAGES.find((lang) => lang.value === srclang)?.label || srclang
	}

	/** 上傳字幕檔案 */
	const handleUpload = async (file: File) => {
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
			await fetchSubtitles()

			// 上傳成功後，自動選擇下一個可用語言
			const nextAvailable = LANGUAGES.find(
				(lang) => !uploadedLangs.has(lang.value) && lang.value !== selectedLang
			)
			if (nextAvailable) {
				setSelectedLang(nextAvailable.value)
			}
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
					placeholder="選擇語言"
					disabled={availableLanguages.length === 0}
				/>
				<Upload
					accept=".srt,.vtt"
					showUploadList={false}
					beforeUpload={(file) => {
						handleUpload(file)
						return false
					}}
					disabled={uploading || availableLanguages.length === 0}
				>
					<Button
						size="small"
						icon={<UploadOutlined />}
						loading={uploading}
						disabled={availableLanguages.length === 0}
					>
						上傳字幕
					</Button>
				</Upload>
			</div>

			{/* 已上傳字幕列表 */}
			{loading ? (
				<Spin size="small" />
			) : subtitles.length > 0 ? (
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
			) : (
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
