import { UploadProps } from "antd"

export type TUseUploadParams = {
  uploadProps?: UploadProps
}

export type TCreateVideoResponse = {
  videoLibraryId: number
  guid: string
  title: string
  dateUploaded: string
  views: number
  isPublic: boolean
  length: number
  status: number
  framerate: number
  rotation: null //TODO
  width: number
  height: number
  availableResolutions: null //TODO
  thumbnailCount: number
  encodeProgress: number
  storageSize: number
  captions: Array<any> //TODO
  hasMP4Fallback: boolean
  collectionId: string
  thumbnailFileName: string
  averageWatchTime: number
  totalWatchTime: number
  category: string
  chapters: Array<any> //TODO
  moments: Array<any> //TODO
  metaTags: Array<any> //TODO
  transcodingMessages: Array<any> //TODO
}

export type TUploadVideoResponse = {
  success: boolean
  message: string
  statusCode: number
}
