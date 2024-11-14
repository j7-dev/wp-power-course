<?php
/**
 * Email
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email;

use J7\PowerCourse\PowerEmail\Resources\Email\Replace\User as UserReplace;

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
	 * @var array Email 寄送條件
	 */
	public $condition;


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
		'condition',
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
	}

	/**
	 * 立即寄送 Email
	 *
	 * @param array $user_ids 使用者 ID 陣列
	 */
	public function send_now( $user_ids ) {
		$html    = $this->description;
		$subject = $this->subject;
		foreach ( $user_ids as $user_id ) {
			$user           = \get_user_by( 'ID', $user_id );
			$user_email     = $user->user_email;
			$formatted_html = UserReplace::get_formatted_html( $html, $user );
			\wp_mail( $user_email, $subject, $formatted_html, CPT::$email_headers );
		}
	}
}
