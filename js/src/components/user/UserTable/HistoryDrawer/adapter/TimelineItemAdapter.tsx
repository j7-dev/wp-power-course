import {
	PlusCircleOutlined,
	InfoCircleOutlined,
	PlayCircleOutlined,
	CheckCircleOutlined,
	CheckCircleFilled,
} from '@ant-design/icons'
import { TimelineLogType, TimelineItemConfig } from '../types'
import { TimelineItemProps } from 'antd'
import { TriggerAt } from '@/components/emails/SendCondition/enum'

const timelineItemMapper: Record<TimelineLogType, TimelineItemConfig> = {
	[TriggerAt.ORDER_CREATED]: {
		color: '#91caff',
		icon: <PlusCircleOutlined />,
	},
	[TriggerAt.COURSE_GRANTED]: {
		color: '#91caff',
		icon: <InfoCircleOutlined />,
	},
	[TriggerAt.COURSE_LAUNCH]: {
		color: '#0958d9', // TODO
		icon: <CheckCircleFilled />, // TODO
	},
	[TriggerAt.CHAPTER_ENTER]: {
		color: '#4096ff',
		icon: <PlayCircleOutlined />,
	},
	[TriggerAt.CHAPTER_FINISH]: {
		color: '#0958d9',
		icon: <CheckCircleOutlined />,
	},
	[TriggerAt.COURSE_FINISH]: {
		color: '#0958d9',
		icon: <CheckCircleFilled />,
	},
}

export class TimelineItemAdapter {
	private static readonly mapper: Record<TimelineLogType, TimelineItemConfig> =
		timelineItemMapper

	constructor(
		public readonly log_type: TimelineLogType,
		public readonly title: string,
	) {
		this.validateLogType(log_type)
	}

	public get itemProps(): TimelineItemProps {
		return {
			color: this.color,
			dot: this.icon,
			children: this.title,
		}
	}

	get color(): string {
		return TimelineItemAdapter.mapper?.[this.log_type]?.color
	}

	get icon(): React.ReactNode {
		return TimelineItemAdapter.mapper?.[this.log_type]?.icon
	}

	private validateLogType(log_type: TimelineLogType): void {
		if (!TimelineItemAdapter.mapper?.[log_type]) {
			console.error(`Invalid timeline log_type: ${log_type}`)
		}
	}
}
