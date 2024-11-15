<?php
/**
 * Email Trigger At
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Trigger;

use J7\PowerCourse\Utils\AVLCourseMeta;
use J7\PowerCourse\Bootstrap;
use J7\PowerCourse\PowerEmail\Resources\Email;
use J7\PowerCourse\PowerEmail\Resources\Email\Email as EmailResource;
use J7\WpUtils\Classes\WP;

/**
 * Class At 觸發發信時機點
 */
final class At {
	use \J7\WpUtils\Traits\SingletonTrait;

	const SEND_SCHEDULE_ACTION = 'power_email_send_schedule'; // 用戶排程發信


	/**
	 * 觸發發信時機點
	 *
	 * @var array<string, array<string, string>>
	 * {slug}_at 為達成條件的時間點
	 * {slug}_sent_at 為發信時間點，如果沒有代表沒發過
	 */
	public $trigger_at = [
		'course_granted' => [
			'label' => '開通課程權限後',
			'slug'  => 'course_granted',
		],
	];

	/**
	 * Constructor
	 */
	public function __construct() {

		// 開通課程權限後
		\add_action( Bootstrap::CRON_ACTION, [ $this, "send_{$this->trigger_at['course_granted']['slug']}_emails" ] );

		// 排程寄送指定用戶 emails
		\add_action( self::SEND_SCHEDULE_ACTION, [ $this, 'send_schedule_callback' ], 10, 2 );
	}

	/**
	 * 觸發開通課程權限後
	 */
	public function send_course_granted_emails(): void {

		$course_granted_email_ids = \get_posts(
			[
				'post_type'      => Email\CPT::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_key'       => 'trigger_at',
				'meta_value'     => $this->trigger_at['course_granted']['slug'],
			]
			);

		foreach ( $course_granted_email_ids as $course_granted_email_id ) {
			$email  = new EmailResource( (int) $course_granted_email_id );
			$offset = $email->get_offset_seconds(); // 偏移量
			if ( null ===$offset ) {
				continue;
			}
			/**
			 * 取得已經開通課程的紀錄
			 *
			 * @var array<int, array{user_id: string, course_id: string, course_granted_sent_at:string}>
			 */
			$records = $this->get_course_granted_records( $offset );

			foreach ( $records as $record ) {
				// 檢查必要參數
				$include_required_params = WP::include_required_params( $record, [ 'user_id', 'course_id', 'course_granted_sent_at' ] );
				if ( $include_required_params !== true ) {
					\J7\WpUtils\Classes\ErrorLog::info( 'send_course_granted_emails 缺少必要參數：' . $include_required_params->get_error_message() );
					continue;
				}

				[
					'user_id' => $user_id,
					'course_id' => $course_id,
				] = $record;

				// 檢查是否發信過
				$has_sent = AVLCourseMeta::get( (int) $record['user_id'], (int) $record['course_id'], "{$this->trigger_at['course_granted']['slug']}_sent_at" );

				if ( $has_sent ) {
					continue;
				}

				$send_success = $email->send_course_granted_email( (int) $user_id, (int) $course_id );

				if ( $send_success ) {
					AVLCourseMeta::update( (int) $user_id, (int) $course_id, "{$this->trigger_at['course_granted']['slug']}_sent_at", time() );
				}
			}
		}
	}

	/**
	 * 取得已經開通課程的紀錄，包含已經寄送的
	 *
	 * @param int $offset 偏移量
	 * @return array<int, array{user_id: string, course_id: string, course_granted_sent_at:string}>
	 */
	private function get_course_granted_records( int $offset = 0 ): array {
		global $wpdb;
		if ( !$offset ) {
			/**
		 * 取得已經開通課程的紀錄
		 *
		 * @var array<int, array{user_id: string, course_id: string}>
		 */
			$records = $wpdb->get_results(
			$wpdb->prepare(
			"SELECT
					course_id,
					user_id,
					meta_value AS course_granted_sent_at
					FROM {$wpdb->prefix}pc_avl_coursemeta
					WHERE meta_key = '%1\$s'",
					"{$this->trigger_at['course_granted']['slug']}_at",
			),
				\ARRAY_A
			);
			return $records;
		}

		/**
		 * 取得已經開通 N天 課程的紀錄
		 * course_granted_at + offset >= current_timestamp
		 *
		 * @var array<int, array{user_id: string, course_id: string}>
		 */
		$records = $wpdb->get_results(
		$wpdb->prepare(
		"SELECT course_id, user_id
				FROM {$wpdb->prefix}pc_avl_coursemeta
				WHERE meta_key = '%1\$s'
				AND meta_value >= %2\$d",
				"{$this->trigger_at['course_granted']['slug']}_at",
				time() - $offset
		),
			\ARRAY_A
		);

		return $records;
	}


	/**
	 * Send schedule callback
	 * 指定用戶的排程發信，時間到後的觸發動作
	 *
	 * @param array $email_ids 電子郵件 ID 陣列
	 * @param array $user_ids 使用者 ID 陣列
	 */
	public function send_schedule_callback( $email_ids, $user_ids ) {
		foreach ( $email_ids as $email_id ) {
			$email = new EmailResource( (int) $email_id );
			foreach ( $user_ids as $user_id ) {
				$email->send_email( (int) $user_id );
			}
		}
	}
}
