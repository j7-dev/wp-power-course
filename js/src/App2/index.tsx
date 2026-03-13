import Player, { TPlayerProps } from '@/App2/Player'
import { TSubtitleTrack } from '@/components/formItem/VideoInput/types'

const isSubtitleTrack = (value: unknown): value is TSubtitleTrack => {
	if (!value || typeof value !== 'object') {
		return false
	}

	const track = value as Record<string, unknown>
	return (
		typeof track.srclang === 'string' &&
		typeof track.label === 'string' &&
		typeof track.url === 'string' &&
		typeof track.attachment_id === 'number'
	)
}

const parseSubtitles = (value?: string): TSubtitleTrack[] => {
	if (!value) {
		return []
	}

	try {
		const parsed = JSON.parse(value) as unknown
		if (!Array.isArray(parsed)) {
			return []
		}
		return parsed.filter(isSubtitleTrack)
	} catch {
		return []
	}
}

function App2(dataset: TPlayerProps) {
	const parsedSubtitles = parseSubtitles(dataset?.subtitles)
	return <Player {...dataset} subtitles={parsedSubtitles} />
}

export default App2
