import { DrawerProps } from 'antd'
import { TTriggerAt, TriggerAt } from '@/components/emails/SendCondition/enum'

export type THistoryDrawerProps = {
	user_id?: string
	course_id?: string
	drawerProps?: DrawerProps
}

export const defaultHistoryDrawerProps: THistoryDrawerProps = {
	user_id: undefined,
	course_id: undefined,
	drawerProps: {
		open: false,
	},
}

export type TimelineSlug = Exclude<TTriggerAt, TriggerAt.FIELD_NAME>

export interface TimelineItemConfig {
	color: string
	icon: React.ReactNode
}
