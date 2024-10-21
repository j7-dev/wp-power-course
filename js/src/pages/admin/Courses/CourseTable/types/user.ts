type TChapter = {
	id: string
	name: string
	chapter_video: string
	is_finished: boolean
}

export type TAVLCourse = {
	id: string
	name: string
	expire_date: number
	chapters?: TChapter[]
}

export type TUserRecord = {
	id: string
	user_login: string
	user_email: string
	display_name: string
	user_registered: string
	user_registered_human: string
	user_avatar_url: string
	avl_courses: TAVLCourse[]
	is_teacher: boolean
}
