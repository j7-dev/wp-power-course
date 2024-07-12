import { createStore, atom } from 'jotai' // or from 'jotai/vanilla'
import { SCREEN } from './utils'

export const store = createStore()
export const finishChapterAtom = atom({
  course_id: undefined,
  chapter_id: undefined,
  isError: false,
  isSuccess: false,
  isLoading: false,
  showDialog: false,
})

export const windowWidthAtom = atom(0)
export const isXSAtom = atom((get) => get(windowWidthAtom) < SCREEN.SM)
export const isSMAtom = atom(
  (get) =>
    get(windowWidthAtom) >= SCREEN.SM && get(windowWidthAtom) < SCREEN.MD,
)
export const isMDAtom = atom(
  (get) =>
    get(windowWidthAtom) >= SCREEN.MD && get(windowWidthAtom) < SCREEN.LG,
)
export const isLGAtom = atom(
  (get) =>
    get(windowWidthAtom) >= SCREEN.LG && get(windowWidthAtom) < SCREEN.XL,
)
export const isXLAtom = atom(
  (get) =>
    get(windowWidthAtom) >= SCREEN.XL && get(windowWidthAtom) < SCREEN.XXL,
)
export const isXXLAtom = atom((get) => get(windowWidthAtom) >= SCREEN.XXL)
