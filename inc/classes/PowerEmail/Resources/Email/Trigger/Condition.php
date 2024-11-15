<?php
/**
 * Email Trigger Condition
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Trigger;

use J7\PowerCourse\PowerEmail\Resources\Email\Trigger\At;

/**
 * Class Condition 觸發發信時機點
 */
final class Condition {


	/**
	 * @var string 觸發時機點
	 * course_granted | course_finish | course_schedule | chapter_finish | chapter_enter
	 * 開通課程時       | 完成課程時     | 課程開課時        | 完成單元時       | 進入單元時
	 */
	public string $trigger_at = '';

	/**
	 * @var array<string> 課程 ID 陣列
	 */
	public array $course_ids = [];

	/**
	 * @var array<string> 章節/單元 ID 陣列
	 */
	public array|null $chapter_ids = null;


	/**
	 * @var string 觸發條件
	 * each         | all      | qty_greater_than
	 * 任何一個達成時 | 全部達成時 | 達成指定數量時
	 */
	public string $trigger_condition = 'each';

	/**
	 * @var int|null 數量
	 */
	public int|null $qty = null;

	/**
	 * @var string 發送方式
	 * send_now | send_later
	 * 立即寄送  | 延遲寄送
	 */
	public string $sending_type = 'send_now';

	/**
	 * @var string|null 延遲寄送數量 N 天/時/分
	 * send_now | send_later
	 * 立即寄送  | 延遲寄送
	 */
	public string|null $sending_value = null;


	/**
	 * @var string|null 延遲寄送單位 天/時/分
	 * day | hour | minute
	 * 天   | 時   | 分
	 */
	public string|null $sending_unit = null;

	/**
	 * @var array|null 延遲寄送範圍 開始時間、結束時間 HH:MM
	 */
	public array|null $sending_range = null;

	/**
	 * Constructor
	 *
	 * @param array $condition 觸發條件
	 */
	public function __construct( $condition ) {
		$at                      = At::instance();
		$this->trigger_at        = $condition['trigger_at'] ?? $at->trigger_at['course_granted']['slug'];
		$this->course_ids        = ( (array) $condition['course_ids'] ?? null );
		$this->chapter_ids       = $condition['chapter_ids'] ?? null;
		$this->trigger_condition = $condition['trigger_condition'];
		$this->qty               = $condition['qty'] ?? null;
		$this->sending_type      = $condition['sending']['type'] ?? 'send_now';
		$this->sending_value     = $condition['sending']['value'] ?? null;
		$this->sending_unit      = $condition['sending']['unit'] ?? null;
		$this->sending_range     = $condition['sending']['range'] ?? null;
	}
}
