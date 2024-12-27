import { atom } from 'jotai'
import {
	THistoryDrawerProps,
	defaultHistoryDrawerProps,
} from './HistoryDrawer/types'

export const selectedUserIdsAtom = atom<string[]>([])

export const historyDrawerAtom = atom<THistoryDrawerProps>(
	defaultHistoryDrawerProps,
)
