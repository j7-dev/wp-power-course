import { useList } from '@refinedev/core'
import { bunny_library_id } from '@/utils'

export const useListVideo = () => {
	const result = useList({
		resource: `${bunny_library_id}/videos`,
		dataProviderName: 'bunny-stream',
	})

	return result
}
