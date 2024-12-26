<?php
/**
 * Email Trigger At
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Trigger;

use J7\PowerCourse\PowerEmail\Resources\Email;
use J7\PowerCourse\PowerEmail\Resources\Email\Email as EmailResource;
use J7\PowerCourse\Resources\Course\LifeCycle as CourseLifeCycle;
use J7\PowerCourse\Resources\Chapter\LifeCycle as ChapterLifeCycle;


/**
 * Class At 觸發發信時機點
 */
final class At {
	use \J7\WpUtils\Traits\SingletonTrait;

	const AS_GROUP = 'power_email';

	const SEND_USERS_HOOK            = 'power_email_send_users'; // 寄給用戶的 AS hook
	const SEND_COURSE_GRANTED_HOOK   = 'power_email_send_course_granted'; // 開通課程權限後寄送的 AS hook
	const SEND_COURSE_LAUNCH_HOOK    = 'power_email_send_course_launch'; // 課程開課時寄送的 AS hook
	const SEND_CHAPTER_ENTER_HOOK    = 'power_email_send_chapter_enter'; // 進入單元時寄送的 AS hook
	const SEND_CHAPTER_FINISHED_HOOK = 'power_email_send_chapter_finish'; // 完成單元時寄送的 AS hook

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
			'hook'  => self::SEND_COURSE_GRANTED_HOOK,
		],
		'course_launch' => [
			'label' => '課程開課時',
			'slug'  => 'course_launch',
			'hook'  => self::SEND_COURSE_LAUNCH_HOOK,
		],
		'chapter_enter' => [
			'label' => '進入單元時',
			'slug'  => 'chapter_enter',
			'hook'  => self::SEND_CHAPTER_ENTER_HOOK,
		],
		'chapter_finish' => [
			'label' => '完成單元時',
			'slug'  => 'chapter_finish',
			'hook'  => self::SEND_CHAPTER_FINISHED_HOOK,
		],
	];

	/**
	 * Constructor
	 */
	public function __construct() {

		// ---- 開通課程權限後 ----//
		\add_action( CourseLifeCycle::ADD_STUDENT_TO_COURSE_ACTION, [ $this, 'schedule_course_granted_email' ], 10, 3 );
		\add_action( self::SEND_COURSE_GRANTED_HOOK, [ $this, 'send_course_email' ], 10 );
		// ---- END 開通課程權限後 ----//

		// ---- 課程開課時 ----//
		\add_action( CourseLifeCycle::COURSE_LAUNCH_ACTION, [ $this, 'course_launch_email' ], 20, 2 );
		\add_action(self::SEND_COURSE_LAUNCH_HOOK, [ $this, 'send_course_email' ], 10 );
		// ---- END 課程開課時 ----//

		// ---- 進入單元時 ----//
		\add_action( ChapterLifeCycle::CHAPTER_ENTER_ACTION, [ $this, 'chapter_enter_email' ], 10, 2 );
		\add_action( self::SEND_CHAPTER_ENTER_HOOK, [ $this, 'send_course_email' ], 10 );
		// ---- END 進入單元時 ----//

		// ---- 完成單元時 ----//
		\add_action( ChapterLifeCycle::CHAPTER_FINISHED_ACTION, [ $this, 'chapter_finish_email' ], 10, 3 );
		\add_action( self::SEND_CHAPTER_FINISHED_HOOK, [ $this, 'send_course_email' ], 10 );
		// ---- END 完成單元時 ----//

		\add_filter( 'power_email_can_send', [ $this, 'trigger_condition' ], 20, 5 );

		// ---- 寄送指定用戶 emails ----//
		\add_action( self::SEND_USERS_HOOK, [ $this, 'send_users_callback' ], 10, 2 );
	}

	/**
	 * 安排 觸發開通課程權限 的寄送時機
	 *
	 * @param int        $user_id 用戶ID
	 * @param int        $course_id 課程ID
	 * @param int|string $expire_date 課程到期日
	 *
	 * @return void
	 */
	public function schedule_course_granted_email( int $user_id, int $course_id, int|string $expire_date ): void {
		$current_avl_course_ids = \get_user_meta( $user_id, 'avl_course_ids' );
		if (!\is_array($current_avl_course_ids)) {
			$current_avl_course_ids = [];
		}
		// 因為直接更新用戶課程權限時，會帶全部的課程 id，所以需要檢查課程 id 是否已經在原本客戶的權限裡面
		// 我們只需要對新增的課程權限部分寄送課程開通信件
		if (!\in_array($course_id, $current_avl_course_ids)) {
			return;
		}

		$this->schedule_email(
			'course_granted',
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
			]
			);
	}

	/**
	 * 觸發條件
	 * 根據觸發條件不同決定要不要寄 Email
	 *
	 * @param bool          $can_send 是否可以寄信
	 * @param EmailResource $email 信件
	 * @param int           $user_id 用戶 ID
	 * @param int           $course_id 課程 ID
	 * @param int           $chapter_id 章節 ID
	 * @return bool
	 */
	public function trigger_condition( bool $can_send, EmailResource $email, int $user_id, int $course_id, int $chapter_id ): bool {
		// 如果原本就不能寄信，就不用檢查觸發條件
		// 如果不存在 課程 id，也直接返回就好
		// 沒有 course_id 的情況是對用戶直接寄信
		if (!$can_send || !$course_id) {
			return $can_send;
		}

		$post_id = $chapter_id ? $chapter_id : $course_id;
		$is_sent = $email->is_sent($post_id, $user_id, (int) $email->id);

		return $is_sent ? false : $email->condition->can_trigger($user_id, $course_id, $chapter_id);
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


	/**
	 * 課程開課時的 Email
	 *
	 * @param int $user_id 用戶 ID
	 * @param int $course_id 課程 ID
	 */
	public function course_launch_email( int $user_id, int $course_id ): void {
		$this->schedule_email(
			'course_launch',
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
			]
			);
	}

	/**
	 * 進入章節時的 Email
	 *
	 * @param \WP_Post    $chapter 章節文章物件
	 * @param \WC_Product $product 課程
	 */
	public function chapter_enter_email( $chapter, $product ): void {
		$user_id   = \get_current_user_id();
		$course_id = $product->get_id();

		$this->schedule_email(
			'chapter_enter',
			[
				'user_id'    => $user_id,
				'course_id'  => $course_id,
				'chapter_id' => $chapter->ID,
			]
			);
	}

	/**
	 * 完成章節時的 Email
	 *
	 * @param int $chapter_id 章節 ID
	 * @param int $course_id 課程 ID
	 * @param int $user_id 用戶 ID
	 */
	public function chapter_finish_email( int $chapter_id, int $course_id, int $user_id ): void {
		$this->schedule_email(
			'chapter_finish',
			[
				'user_id'    => $user_id,
				'course_id'  => $course_id,
				'chapter_id' => $chapter_id,
			]
			);
	}



	/**
	 * 發送課程的 Email
	 *
	 * @param array{email_id: int, user_id: int, course_id: int, chapter_id: ?int, context: string} $args 參數
	 */
	public function send_course_email( $args ): void {
		$email = new EmailResource(  $args['email_id'] );
		$email->send_course_email(  $args['user_id'], $args['course_id'], $args['chapter_id'] ?? 0 );
	}


	/**
	 * 找出觸發時機的 Email，排程
	 *
	 * @param string $context 觸發條件
	 * @param array  $args 參數
	 */
	private function schedule_email( string $context, ?array $args ): void {
		$email_ids = \get_posts(
			[
				'post_type'      => Email\CPT::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_key'       => 'trigger_at',
				'meta_value'     => $this->trigger_at[ $context ]['slug'],
			]
			);

		$hook = $this->trigger_at[ $context ]['hook'];

		foreach ( $email_ids as $email_id ) {
			$email = new EmailResource( (int) $email_id );

			// 如果不該發信，就不該排程
			$can_send = $email->can_send( (int) $args['user_id'], (int) $args['course_id'], (int) ( $args['chapter_id'] ?? 0 ));

			if (!$can_send) {
				continue;
			}

			$timestamp = $email->get_sending_timestamp();

			if ( null === $timestamp ) {
				continue;
			}

			$default_args = [
				'email_id' => $email_id,
				'context'  => $context,
			];

			$args = \wp_parse_args($args, $default_args   );

			if (0 === $timestamp) { // 立即寄送
				\as_enqueue_async_action( $hook, [ $args ], self::AS_GROUP);
				continue;
			}

			\as_schedule_single_action( $timestamp, $hook, [ $args ], self::AS_GROUP );
		}
	}
}
