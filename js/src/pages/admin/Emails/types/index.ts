import { IBlockData } from 'j7-easy-email-core'

export type TEmailListRecord = Omit<TEmailRecord, 'description'>

export type TEmailRecord = {
	id: string
	name: string
	status: string
	short_description: string
	action_name: string
	days: number
	operator: string
	date_created: string
	date_modified: string
}

export type TFormValues = {
	status: 'publish' | 'draft'
	name: string
	short_description: IBlockData | string
	description: string
}
