<?php

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email;

use J7\PowerCourse\PowerEmail\Resources\Email\Replace;
use J7\PowerCourse\PowerEmail\Resources\EmailRecord\CRUD as EmailRecord;
use J7\WpUtils\Classes\WP;


/**
 * Class Email
 */
final class Email {

	/** @var string Email ID */
	public string $id;

	/** @var string|null Email 狀態 */
	public string|null $status;

	/** @var string Email 名稱 */
	public string $name;

	/** @var string Email 內容，存放 email html @see https://mjml.io/ */
	public string $description = '';

	/** @var string Email 內容，存放 json 格式 @see https://github.com/zalify/easy-email-editor */
	public string $short_description;

	/** @var string Email 主旨 */
	public string $subject = '';

	/** @var string Email 主旨 */
	public string $formatted_subject = '';

	/** @var string Email 寄送條件 */
	public string $trigger_at = '';

	/** @var Trigger\Condition|array|null Email 寄送條件 */
	public Trigger\Condition|array|null $condition = null;


	/** @var string Email 建立時間 */
	public string $date_created;

	/** @var string Email 修改時間 */
	public string $date_modified;

	/** @var array Email post meta 欄位 */
	public static array $meta_keys = [
		'subject',
	];

	/**
	 * Constructor
	 *
	 * @param \WP_Post|int $post Post object or post ID.
	 * @param bool         $show_description 是否顯示 Email 內容
	 * @param bool         $api_format 是否為 API 格式，true 直接回傳 array 不是的話會 new Condition
	 */
	public function __construct( $post, $show_description = true, $api_format = false ) {
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

		$this->trigger_at              = \get_post_meta( $this->id, 'trigger_at', true );
		$condition_array['trigger_at'] = $this->trigger_at;
		if ( $condition_array ) {
			$this->condition = $api_format ? $condition_array : new Trigger\Condition( $condition_array );
		}

		$replace_classes = [
			Replace\User::class,
			Replace\Course::class,
			Replace\Chapter::class,
		];
		foreach ($replace_classes as $key => $replace_class) {
			\add_filter('power_email_course_subject', [ $replace_class, 'replace_string' ], $key * 10, 4);
			\add_filter('power_email_course_html', [ $replace_class, 'replace_string' ], $key * 10, 4);
		}
	}

	/**
	 * 立即寄送 Email
	 *
	 * @param int $user_id 使用者 ID
	 * @return bool 是否寄送成功
	 */
	public function send_email( int $user_id ): bool {
		$user       = \get_user_by( 'ID', $user_id );
		$user_email = $user->user_email;

		$subject = \apply_filters('power_email_course_subject', $this->subject, $user_id, 0, 0);
		$html    = \apply_filters('power_email_course_html', $this->description, $user_id, 0, 0);
		$sent    = \wp_mail( $user_email, $subject, $html, CPT::$email_headers );
		if ($sent) {
			\do_action('power_email_after_send_email', $this, $user_id, 0, 0);
		}
		return $sent;
	}

	/**
	 * 是否可以寄送
	 *
	 * @param int  $user_id 使用者 ID
	 * @param ?int $course_id 課程 ID
	 * @param ?int $chapter_id 章節 ID
	 * @return bool
	 */
	public function can_send( int $user_id, ?int $course_id = 0, ?int $chapter_id = 0 ): bool {
		$can_send = true;
		if (!$this->condition) {
			return false; // 沒有條件就不用判斷了，就是不能寄信
		}

		// 目前先判斷 each 就好，其他條件 all, qty_greater_than 再用 filter 過濾
		return \apply_filters( 'power_email_can_send', $can_send, $this, $user_id, $course_id, $chapter_id );
	}


	/**
	 * 是否在範圍內
	 *
	 * @deprecated
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
		$wp_timezone = \wp_timezone();

		// 建立今天 18:15 的 DateTime 物件
		$start = new \DateTime("today {$condition->sending_range[0]}:00", $wp_timezone);
		$end   = new \DateTime("today {$condition->sending_range[1]}:00", $wp_timezone);

		$current_timestamp = time();
		return $current_timestamp >= $start->getTimestamp() && $current_timestamp < $end->getTimestamp();
	}


	/**
	 * 寄送課程 Email
	 *
	 * @param int  $user_id 使用者 ID
	 * @param int  $course_id 課程 ID
	 * @param ?int $chapter_id 章節 ID
	 * @return bool 是否寄送成功
	 */
	public function send_course_email( int $user_id, int $course_id, int $chapter_id = 0 ): bool {
		// 檢查是否該發信
		if (!$this->can_send( $user_id, $course_id, $chapter_id )) {
			return false;
		}

		$user = \get_user_by( 'ID', $user_id );
		if (!$user) {
			return false;
		}
		$user_email = $user->user_email;
		$subject    = \apply_filters('power_email_course_subject', $this->subject, $user_id, $course_id, $chapter_id);
		$html       = \apply_filters('power_email_course_html', $this->description, $user_id, $course_id, $chapter_id);

		$sent                    = \wp_mail( $user_email, $subject, $html, CPT::$email_headers );
		$this->formatted_subject = $subject;
		if ($sent) {
			\do_action('power_email_after_send_email', $this, $user_id, $course_id, $chapter_id);
		}
		return $sent;
	}

	/**
	 * 取得 秒 偏移量
	 * 例如: 開通課程 7天 後寄送，這個 7天，86400 * 7 就是偏移量
	 * 這邊只要先判斷 延遲 N 天|小時|分鐘 就好，指定時間區段寄送，等個別事件發生時再來判斷
	 *
	 * @return int 偏移量
	 */
	private function get_offset_seconds(): int {
		$condition = $this->condition;

		$value = (int) $condition->sending_value ?? 0;
		$unit  = $condition->sending_unit ?? 'day';

		// 這邊只要先判斷 延遲 N 天|小時|分鐘 就好，指定時間區段寄送，等個別事件發生時再來判斷
		return match ($unit) {
			'day' => DAY_IN_SECONDS * $value,
			'hour' => HOUR_IN_SECONDS * $value,
			'minute' => MINUTE_IN_SECONDS * $value,
		};
	}

	/**
	 * 取得寄送時間戳記
	 *
	 * @return int|null 0 表示立即寄送，null 表示不寄送
	 */
	public function get_sending_timestamp(): int|null {
		$condition = $this->condition;
		if (!$condition) {
			return null;
		}

		$now = time();

		if ('send_now' === $condition->sending_type) {
			return 0;
		}
		$offset = $this->get_offset_seconds();

		$unit          = $condition->sending_unit ?? 'day';
		$sending_range = $condition->sending_range;

		$day_timestamp = $now + $offset; // 延遲 N 天後的 timestamp
		if ($sending_range) {
			$start         = $sending_range[0]; // 開始時間 HH:MM
			$day_timestamp = self::get_target_local_timestamp($day_timestamp, $start);
		}

		return match ($unit) {
			'day' => $day_timestamp,
			default => $now + $offset,
		};
	}

	/**
	 * 取得距離 $timestamp 最近的下一個本地時間 HH:MM 的 timestamp
	 *
	 * @param int    $timestamp 時間戳記
	 * @param string $hh_mm_str 時間 HH:MM
	 * @return int
	 */
	public static function get_target_local_timestamp( int $timestamp, string $hh_mm_str ): int {
		// 本地時間的 timestamp
		$input_date_string = \wp_date('Y-m-d', $timestamp);
		$next_timestamp    = WP::wp_strtotime("{$input_date_string} {$hh_mm_str}"); // 先用同一天時間算 timestamp

		if ($next_timestamp < $timestamp) {
			$next_timestamp = WP::wp_strtotime('+1 day', $next_timestamp);
		}

		return $next_timestamp;
	}


	/**
	 * 是否已寄送
	 *
	 * @param int $post_id 文章/課程/章節 ID
	 * @param int $user_id 使用者 ID
	 * @param int $email_id 信件 ID
	 * @return bool
	 */
	public function is_sent( int $post_id, int $user_id, int $email_id ): bool {
		$find_record = EmailRecord::get(
			[
				'post_id'      => $post_id,
				'user_id'      => $user_id,
				'email_id'     => $email_id,
				'trigger_at'   => $this->trigger_at,
				'mark_as_sent' => 1,
			]
			);

		return (bool) $find_record;
	}
}
