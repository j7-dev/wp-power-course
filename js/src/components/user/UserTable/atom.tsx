import { atom } from 'jotai'

import { defaultHistoryDrawerProps } from './HistoryDrawer'
import { THistoryDrawerProps } from './HistoryDrawer/types'

export const selectedUserIdsAtom = atom<string[]>([])

export const historyDrawerAtom = atom<THistoryDrawerProps>(
	defaultHistoryDrawerProps
)
