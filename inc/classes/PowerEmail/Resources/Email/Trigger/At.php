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
use J7\PowerCourse\Resources\Course\LifeCycle as CourseLifeCycle;

/**
 * Class At 觸發發信時機點
 */
final class At {
	use \J7\WpUtils\Traits\SingletonTrait;

	const SEND_USERS_ACTION          = 'power_email_send_users'; // 寄給用戶的 AS hook
	const SEND_COURSE_GRANTED_ACTION = 'power_email_send_course_granted'; // 開通課程權限後寄送的 AS hook

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
		\add_action( CourseLifeCycle::ADD_STUDENT_TO_COURSE_ACTION, [ $this, 'schedule_course_granted_email' ], 10, 3 );
		\add_action( self::SEND_COURSE_GRANTED_ACTION, [ $this, 'send_course_granted_callback' ], 10, 3 );

		// 寄送指定用戶 emails
		\add_action( self::SEND_USERS_ACTION, [ $this, 'send_users_callback' ], 10, 2 );
	}

	/**
	 * 安排 觸發開通課程權限 的寄送時機
	 *
	 * @param int $user_id 用戶ID
	 * @param int $course_id 課程ID
	 * @param int $expire_date 課程到期日
	 *
	 * @return void
	 */
	public function schedule_course_granted_email( int $user_id, int $course_id, int $expire_date ): void {
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
			$email = new EmailResource( (int) $course_granted_email_id );

			$timestamp = $email->get_sending_timestamp();

			if ( null === $timestamp ) {
				continue;
			}

			if (0 === $timestamp) { // 立即寄送
				\as_enqueue_async_action( self::SEND_COURSE_GRANTED_ACTION, [ (int) $course_granted_email_id, $user_id, $course_id ] );
				continue;
			}

			\as_schedule_single_action( $timestamp, self::SEND_COURSE_GRANTED_ACTION, [ (int) $course_granted_email_id, $user_id, $course_id ] );
		}
	}

	/**
	 * 寄送開通課程權限後的 Email
	 *
	 * @param int $email_id 電子郵件 ID
	 * @param int $user_id 用戶 ID
	 * @param int $course_id 課程 ID
	 */
	public function send_course_granted_callback( int $email_id, int $user_id, int $course_id ): void {
		$email = new EmailResource( (int) $email_id );
		$email->send_course_email( (int) $user_id, (int) $course_id );
	}


	/**
	 * Send users callback
	 * 指定用戶的發信
	 *
	 * @param array $email_ids 電子郵件 ID 陣列
	 * @param array $user_ids 使用者 ID 陣列
	 */
	public function send_users_callback( array $email_ids, array $user_ids ): void {
		foreach ( $email_ids as $email_id ) {
			$email = new EmailResource( (int) $email_id );
			foreach ( $user_ids as $user_id ) {
				$email->send_email( (int) $user_id );
			}
		}
	}
}
