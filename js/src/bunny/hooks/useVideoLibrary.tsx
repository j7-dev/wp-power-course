/**
 * TODO 邏輯後補
 * 目的: 讓人在同一個介面就可以選擇 VideoLibrary
 * 1. 先列出所有的 VideoLibrary，下拉選擇
 * 2. 如果沒有，就創建一個新的 VideoLibrary
 * 3. 如果有，就使用現有的 VideoLibrary
 */

export const useVideoLibrary = () => {
  return {
    libraryId: 244459,
    name: 'cloud luke',
    apiKey: '192d0f46-75b7-4148-8645a8530673-9081-40fb',
    enabledResolutions: ['1080p', '720p', '480p', '360p'],
  }
}
