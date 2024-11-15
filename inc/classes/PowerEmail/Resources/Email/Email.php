<?php
/**
 * Email
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email;

use J7\PowerCourse\PowerEmail\Resources\Email\Replace\User as UserReplace;
use J7\PowerCourse\PowerEmail\Resources\Email\Replace\Course as CourseReplace;


/**
 * Class Email
 */
final class Email {

	/**
	 * @var string Email ID
	 */
	public string $id;

	/**
	 * @var string Email 狀態
	 */
	public string $status;

	/**
	 * @var string Email 主旨
	 */
	public string $name;

	/**
	 * @var string Email 內容，存放 email html
	 * @see https://mjml.io/
	 */
	public string $description = '';

	/**
	 * @var string Email 內容，存放 json 格式
	 * @see https://github.com/zalify/easy-email-editor
	 */
	public string $short_description;

	/**
	 * @var string Email 主旨
	 */
	public string $subject = '';

	/**
	 * @var Trigger\Condition|null Email 寄送條件
	 */
	public Trigger\Condition|null $condition = null;


	/**
	 * @var string Email 建立時間
	 */
	public string $date_created;

	/**
	 * @var string Email 修改時間
	 */
	public string $date_modified;

	/**
	 * @var array Email post meta 欄位
	 */
	public static array $meta_keys = [
		'subject',
	];

	/**
	 * Constructor
	 *
	 * @param \WP_Post|int $post Post object or post ID.
	 * @param bool         $show_description 是否顯示 Email 內容
	 */
	public function __construct( $post, $show_description = true ) {
		$post         = $post instanceof \WP_Post ? $post : \get_post( $post );
		$this->id     = (string) $post->ID;
		$this->status = $post->post_status;
		$this->name   = $post->post_title;
		if ($show_description) {
			$this->short_description = $post->post_excerpt;
			$this->description       = $post->post_content;
		}
		$this->date_created  = $post->post_date;
		$this->date_modified = $post->post_modified;

		foreach ( self::$meta_keys as $key ) {
			$this->$key = \get_post_meta( $this->id, $key, true );
		}

		$condition_array = \get_post_meta( $this->id, 'condition', true );
		if ( !$condition_array ) {
			$this->condition = null;
			return;
		}

		$condition_array['trigger_at'] = \get_post_meta( $this->id, 'trigger_at', true );
		if ( $condition_array ) {
			$this->condition = new Trigger\Condition( $condition_array );
		}
	}

	/**
	 * 立即寄送 Email
	 *
	 * @param int $user_id 使用者 ID
	 * @return bool 是否寄送成功
	 */
	public function send_email( int $user_id ): bool {
		$html       = $this->description;
		$subject    = $this->subject;
		$user       = \get_user_by( 'ID', $user_id );
		$user_email = $user->user_email;
		$html       = UserReplace::get_formatted_html( $html, $user );
		return \wp_mail( $user_email, $subject, $html, CPT::$email_headers );
	}

	/**
	 * 寄送課程授權後 Email
	 *
	 * @param int $user_id 使用者 ID
	 * @param int $course_id 課程 ID
	 * @return bool 是否寄送成功
	 */
	public function send_course_granted_email( int $user_id, int $course_id ): bool {
		if ( !$this->can_send($user_id, $course_id) ) {
			return false;
		}
		return $this->send_course_email( $user_id, $course_id );
	}

	/**
	 * 是否可以寄送
	 *
	 * @param int $user_id 使用者 ID
	 * @param int $course_id 課程 ID
	 * @return bool
	 */
	public function can_send( int $user_id, int $course_id ): bool {
		$condition = $this->condition;
		if (!$condition) {
			return false;
		}

		// 是否在開始 & 結束範圍內
		if ( !$this->is_in_range() ) {
			return false;
		}

		$course_ids = $this->condition->course_ids; // 要發的課程 ID
		if ( !in_array( $course_id, $course_ids ) && !empty( $course_ids ) ) {
			return false;
		}

		// 目前先判斷 each 就好
		// TODO 其他條件 all, qty_greater_than 再慢慢加
		// if ('each' === $condition->trigger_condition) {
		return true;
		// }
	}


	/**
	 * 是否在範圍內
	 *
	 * @return bool
	 */
	public function is_in_range(): bool {
		$condition = $this->condition;
		if (!$condition) {
			return false;
		}

		if ('day' !== $condition->sending_unit) {
			return false;
		}

		if (empty($condition->sending_range)) {
			return false;
		}

		// 取得 WordPress 時區
		$wp_timezone = wp_timezone();

		// 建立今天 18:15 的 DateTime 物件
		$start = new \DateTime("today {$condition->sending_range[0]}:00", $wp_timezone);
		$end   = new \DateTime("today {$condition->sending_range[1]}:00", $wp_timezone);

		$current_timestamp = time();
		return $current_timestamp >= $start->getTimestamp() && $current_timestamp < $end->getTimestamp();
	}


	/**
	 * 寄送課程 Email
	 *
	 * @param int $user_id 使用者 ID
	 * @param int $course_id 課程 ID
	 * @return bool 是否寄送成功
	 */
	private function send_course_email( int $user_id, int $course_id ): bool {
		$html    = $this->description;
		$subject = $this->subject;

		$user = \get_user_by( 'ID', $user_id );
		if (!$user) {
			return false;
		}
		$user_email = $user->user_email;
		$html       = UserReplace::get_formatted_html( $html, $user );

		$course_product = \wc_get_product($course_id);
		if (!$course_product) {
			return false;
		}
		$html = CourseReplace::get_formatted_html( $html, $course_product );
		return \wp_mail( $user_email, $subject, $html, CPT::$email_headers );
	}

	/**
	 * 取得 秒 偏移量
	 * 例如: 開通課程 7天 後寄送，這個 7天，86400 * 7 就是偏移量
	 *
	 * @return int|null 偏移量
	 */
	public function get_offset_seconds(): int|null {
		$condition = $this->condition;
		if (!$condition) {
			return null;
		}

		if ('send_now' === $condition->sending_type) {
			return 0;
		}

		// 目前先判斷 course_granted 課程開通 就好
		// TODO 其他條件 course_finish | course_schedule | chapter_finish | chapter_enter 再慢慢加
		return match ( $condition->trigger_at ) {
			'course_granted' => $this->get_course_granted_offset_seconds(),
			default => null,
		};
	}


	/**
	 * 取得課程開通後寄送的秒偏移量
	 *
	 * @return int|null 偏移量
	 */
	private function get_course_granted_offset_seconds(): int|null {
		$condition = $this->condition;

		$value = (int) $condition->sending_value ?? 0;
		$unit  = $condition->sending_unit ?? 'day';

		return match ($unit) {
			'day' => DAY_IN_SECONDS * $value,
			'hour' => HOUR_IN_SECONDS * $value,
			'minute' => MINUTE_IN_SECONDS * $value,
		};
	}
}
