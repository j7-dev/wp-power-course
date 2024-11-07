<?php
/**
 * Email
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email;

/**
 * Class Email
 */
final class Email {
	/**
	 * @var string Email 欄位前綴
	 */
	public static string $prefix = 'pe_';


	/**
	 * @var int Email ID
	 */
	public int $ID;

	/**
	 * @var string Email 狀態
	 */
	public string $status;

	/**
	 * @var string Email 主旨
	 */
	public string $subject;

	/**
	 * @var string Email 內容
	 */
	public string $content;

	/**
	 * @var string Email Hook 動作名稱
	 */
	public string $action_name;

	/**
	 * @var string Email N 天{前後}發送
	 */
	public string $days;

	/**
	 * @var string Email 操作 'after' | 'before'
	 */
	public string $operator;


	/**
	 * @var array Email post meta 欄位
	 */
	public static array $meta_keys = [
		'action_name',
		'days',
		'operator',
	];
	/**
	 * Constructor
	 *
	 * @param \WP_Post|int $post Post object or post ID.
	 */
	public function __construct( $post ) {
		$post          = $post instanceof \WP_Post ? $post : \get_post( $post );
		$this->ID      = $post->ID;
		$this->status  = $post->post_status;
		$this->subject = $post->post_title;
		$this->content = $post->post_content;

		$prefix = self::$prefix;
		foreach ( self::$meta_keys as $key ) {
			$this->$key = \get_post_meta( $this->ID, "{$prefix}{$key}", true );
		}
	}
}
