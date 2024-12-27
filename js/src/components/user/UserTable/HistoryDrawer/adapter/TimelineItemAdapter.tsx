import {
	PlusCircleOutlined,
	InfoCircleOutlined,
	PlayCircleOutlined,
	CheckCircleOutlined,
	CheckCircleFilled,
} from '@ant-design/icons'
import { TimelineSlug, TimelineItemConfig } from '../types'
import { TimelineItemProps } from 'antd'
import { TriggerAt } from '@/components/emails/SendCondition/enum'

const timelineItemMapper: Record<TimelineSlug, TimelineItemConfig> = {
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
	private static readonly mapper: Record<TimelineSlug, TimelineItemConfig> =
		timelineItemMapper

	constructor(
		public readonly slug: TimelineSlug,
		public readonly label: string,
	) {
		this.validateSlug(slug)
	}

	public get itemProps(): TimelineItemProps {
		return {
			color: this.color,
			dot: this.icon,
			children: this.label,
		}
	}

	get color(): string {
		return TimelineItemAdapter.mapper[this.slug].color
	}

	get icon(): React.ReactNode {
		return TimelineItemAdapter.mapper[this.slug].icon
	}

	private validateSlug(slug: TimelineSlug): void {
		if (!TimelineItemAdapter.mapper[slug]) {
			throw new Error(`Invalid timeline slug: ${slug}`)
		}
	}
}
