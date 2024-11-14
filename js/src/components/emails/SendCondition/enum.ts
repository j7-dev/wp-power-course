export enum TriggerAt {
	FIELD_NAME = 'trigger_at',
	COURSE_OPEN = 'course_open',
	COURSE_FINISH = 'course_finish',
	COURSE_SCHEDULE = 'course_schedule',
	CHAPTER_FINISH = 'chapter_finish',
	CHAPTER_ENTER = 'chapter_enter',
}

export enum TriggerCondition {
	FIELD_NAME = 'trigger_condition',
	EACH = 'each',
	ALL = 'all',
	QUANTITY_GREATER_THAN = 'qty_greater_than',
}

export enum SendingType {
	FIELD_NAME = 'type',
	NOW = 'send_now',
	LATER = 'send_later',
}

export enum SendingUnit {
	FIELD_NAME = 'unit',
	DAY = 'day',
	HOUR = 'hour',
	MINUTE = 'minute',
}
