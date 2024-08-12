export type TVideo = {
	videoLibraryId: number
	guid: string
	title: string
	dateUploaded: string
	views: number
	isPublic: boolean
	length: number
	status: number
	framerate: number
	rotation: number
	width: number
	height: number
	availableResolutions: string // "1080p,720p"
	thumbnailCount: number
	encodeProgress: number
	storageSize: number
	captions: any[]
	hasMP4Fallback: boolean
	collectionId: string
	thumbnailFileName: string
	averageWatchTime: number
	totalWatchTime: number
	category: string
	chapters: any[]
	moments: any[]
	metaTags: any[]
	transcodingMessages: any[]
}

export type TGetVideosResponse = {
	totalItems: number
	currentPage: number
	itemsPerPage: number
	items: TVideo[]
}
