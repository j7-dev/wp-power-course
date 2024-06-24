/**
 * TODO
 */

import React from 'react'
import { useList } from '@refinedev/core'

type TUseListVideoParams = {
  libraryId: number
}

export const useListVideo = ({ libraryId }: TUseListVideoParams) => {
  const result = useList({
    resource: `${libraryId}/videos`,
    dataProviderName: 'bunny-stream',
  })

  return result
}
