import { useList } from '@refinedev/core'
import { useVideoLibrary } from '@/bunny/hooks'

export const useListVideo = () => {
  const { libraryId } = useVideoLibrary()
  const result = useList({
    resource: `${libraryId}/videos`,
    dataProviderName: 'bunny-stream',
  })

  return result
}
