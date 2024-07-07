import { createStore, atom } from 'jotai' // or from 'jotai/vanilla'

export const store = createStore()
export const finishChapterAtom = atom({
  course_id: undefined,
  chapter_id: undefined,
  isFinished: false,
  isSuccess: false,
  isLoading: false,
  showDialog: false,
})