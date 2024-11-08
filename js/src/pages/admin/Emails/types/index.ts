export type TEmailListRecord = Omit<TEmailRecord, 'description'>

export type TEmailRecord = {
	id: string
	name: string
	status: string
	description: string
	action_name: string
	days: number
	operator: string
	date_created: string
	date_modified: string
}
